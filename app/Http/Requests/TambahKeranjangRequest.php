<?php

namespace App\Http\Requests;

use App\Enums\SatuanSewa;
use App\Enums\StatusAktif;
use App\Models\Fasilitas;
use App\Models\TarifSewa;
use App\Services\CartService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TambahKeranjangRequest extends FormRequest
{
    private ?TarifSewa $tarifCache = null;
    private bool $tarifLoaded = false;

    public function authorize(): bool
    {
        return true; // Alur publik, tanpa auth.
    }

    /**
     * Convention Hall hanya sewa harian SATU hari (8 jam pemakaian) —
     * tanggal_selesai otomatis disamakan dengan tanggal_mulai, apa pun input user.
     * Sewa Per Jam: jam_mulai/jam_selesai diisi manual oleh pemesan (lewat pilih-jam.blade.php
     * di form), dibatasi jam operasional gedung 08.00–16.00 lewat withValidator() di bawah.
     */
    protected function prepareForValidation(): void
    {
        $tarif = $this->tarif();

        if (
            $tarif
            && $tarif->fasilitas?->kategori_fasilitas === 'Convention Hall'
            && $tarif->jenisSewa?->satuan === SatuanSewa::Hari
            && $this->filled('tanggal_mulai')
        ) {
            $this->merge(['tanggal_selesai' => $this->input('tanggal_mulai')]);
        }
    }

    public function rules(): array
    {
        $satuan = $this->tarif()?->jenisSewa?->satuan;

        $rules = [
            'id_fasilitas' => [
                'required', 'integer',
                Rule::exists('fasilitas', 'id_fasilitas')->where('status_aktif', StatusAktif::Aktif->value),
            ],
            'id_tarif_sewa' => [
                'required', 'integer',
                Rule::exists('tarif_sewa', 'id_tarif_sewa')->where('status_aktif', StatusAktif::Aktif->value),
            ],
            'tanggal_mulai'   => ['required', 'date', 'after_or_equal:today'],
            'jumlah_pengguna' => ['required', 'integer', 'min:1'],
            'keperluan'       => ['required', 'string', 'min:5', 'max:1000'],
            'edit_index'      => ['nullable', 'integer', 'min:0'],

            // Data diri Pemesan — diisi di form yang sama supaya keranjang sudah "siap" sebelum checkout.
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'alamat'       => ['required', 'string', 'max:500'],
            'usia'         => ['required', 'integer', 'min:17', 'max:120'],
            'pekerjaan'    => ['required', 'string', 'max:100'],
            'no_telepon'   => ['required', 'string', 'regex:/^\+?[0-9]{8,20}$/'],
        ];

        // Aturan jadwal tergantung satuan sewa.
        if ($satuan === SatuanSewa::Jam) {
            $rules['jam_mulai']   = ['required', 'date_format:H:i'];
            $rules['jam_selesai'] = ['required', 'date_format:H:i', 'after:jam_mulai'];
        } elseif ($satuan === SatuanSewa::Hari) {
            $rules['tanggal_selesai'] = ['required', 'date', 'after_or_equal:tanggal_mulai'];
        } elseif ($satuan === SatuanSewa::Bulan) {
            $rules['tanggal_selesai'] = ['required', 'date', 'after:tanggal_mulai'];

            // Dokumen persyaratan — satu lampiran berlaku untuk semua ruangan Bulan di
            // keranjang (lihat CartService::dokumen()). dokumen[] = berkas baru yang
            // diunggah sekarang, dokumen_pertahankan[] = path dokumen lama (yang sudah
            // tersimpan) yang TIDAK dihapus pemesan di form "modern" ini.
            $rules['dokumen']                = ['array'];
            $rules['dokumen.*']              = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $rules['dokumen_pertahankan']    = ['array'];
            $rules['dokumen_pertahankan.*']  = ['string'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $tarif = $this->tarif();
            if (! $tarif) {
                return;
            }

            // Tarif harus milik fasilitas yang dipilih.
            if ((int) $this->input('id_fasilitas') !== (int) $tarif->id_fasilitas) {
                $v->errors()->add('id_tarif_sewa', 'Tarif tidak sesuai dengan fasilitas yang dipilih.');
            }

            // Sewa Jam mengikuti jam operasional gedung: 08.00–16.00.
            if ($tarif->jenisSewa?->satuan === SatuanSewa::Jam) {
                if ($this->filled('jam_mulai') && $this->input('jam_mulai') < '08:00') {
                    $v->errors()->add('jam_mulai', 'Jam mulai paling cepat 08.00 (jam operasional gedung).');
                }
                if ($this->filled('jam_selesai') && $this->input('jam_selesai') > '16:00') {
                    $v->errors()->add('jam_selesai', 'Jam selesai paling lambat 16.00 (jam operasional gedung).');
                }

                // Tanggal hari ini tapi jam mulai sudah lewat waktu sekarang — tidak boleh,
                // 'after_or_equal:today' di atas cuma memeriksa tanggal, bukan jam.
                if (
                    $this->filled('tanggal_mulai') && $this->filled('jam_mulai')
                    && Carbon::parse($this->input('tanggal_mulai'))->isToday()
                    && $this->input('jam_mulai') < now()->format('H:i')
                ) {
                    $v->errors()->add('jam_mulai', 'Jam mulai sudah lewat, pilih jam yang akan datang.');
                }
            }

            // Sewa Hari/Bulan: kalau tanggal mulai hari ini tapi gedung sudah tutup (lewat jam
            // operasional 16.00), tanggal itu sudah tidak bisa dipakai lagi — pemesan harus
            // pilih tanggal berikutnya.
            if (
                in_array($tarif->jenisSewa?->satuan, [SatuanSewa::Hari, SatuanSewa::Bulan], true)
                && $this->filled('tanggal_mulai')
                && Carbon::parse($this->input('tanggal_mulai'))->isToday()
                && now()->format('H:i') >= '16:00'
            ) {
                $v->errors()->add('tanggal_mulai', 'Gedung sudah tutup untuk hari ini (jam operasional berakhir 16.00), pilih tanggal mulai berikutnya.');
            }

            // Gedung tidak beroperasi Sabtu & Minggu — berlaku untuk Sewa Jam & Hari (pemakaian
            // ruangan pada tanggal itu sendiri). Sewa Bulan dikecualikan dari cek "seluruh
            // rentang" karena masa sewa memang wajar melewati akhir pekan, tapi tanggal
            // mulainya tetap tidak boleh jatuh di akhir pekan.
            if ($tarif->jenisSewa?->satuan === SatuanSewa::Jam && $this->filled('tanggal_mulai') && Carbon::parse($this->input('tanggal_mulai'))->isWeekend()) {
                $v->errors()->add('tanggal_mulai', 'Gedung tidak melayani reservasi pada hari Sabtu & Minggu, pilih hari kerja (Senin–Jumat).');
            }

            if ($tarif->jenisSewa?->satuan === SatuanSewa::Hari && $this->filled('tanggal_mulai') && $this->filled('tanggal_selesai')) {
                $adaAkhirPekan = false;
                $d = Carbon::parse($this->input('tanggal_mulai'));
                $batas = Carbon::parse($this->input('tanggal_selesai'));
                for ($i = 0; $i <= 400 && $d->lte($batas); $i++, $d->addDay()) {
                    if ($d->isWeekend()) {
                        $adaAkhirPekan = true;
                        break;
                    }
                }
                if ($adaAkhirPekan) {
                    $v->errors()->add('tanggal_mulai', 'Gedung tidak melayani reservasi pada hari Sabtu & Minggu, pilih rentang tanggal yang hanya mencakup hari kerja (Senin–Jumat).');
                }
            }

            if ($tarif->jenisSewa?->satuan === SatuanSewa::Bulan && $this->filled('tanggal_mulai') && Carbon::parse($this->input('tanggal_mulai'))->isWeekend()) {
                $v->errors()->add('tanggal_mulai', 'Tanggal mulai sewa tidak boleh jatuh di hari Sabtu & Minggu, pilih hari kerja (Senin–Jumat).');
            }

            // Jumlah pengguna tidak boleh melebihi kapasitas — untuk multi-ruangan (antrian)
            // batasnya kapasitas TERKECIL di antara ruangan terpilih, karena satu jumlah
            // pengguna berlaku untuk semua ruangan tersebut.
            $kapasitas = $this->kapasitasMaksimal();
            if ($kapasitas !== null && $this->filled('jumlah_pengguna') && (int) $this->input('jumlah_pengguna') > $kapasitas) {
                $v->errors()->add('jumlah_pengguna', "Jumlah pengguna melebihi kapasitas maksimal fasilitas ({$kapasitas} orang).");
            }

            // Sewa Bulan: durasi_minimum (mis. 3 bulan) adalah batas MINIMUM — periode yang
            // lebih panjang diperbolehkan tanpa batas maksimum.
            if (
                $tarif->jenisSewa?->satuan === SatuanSewa::Bulan
                && $this->filled('tanggal_mulai') && $this->filled('tanggal_selesai')
                && ! $v->errors()->hasAny(['tanggal_mulai', 'tanggal_selesai'])
            ) {
                $bulan = CartService::hitungBulan($this->input('tanggal_mulai'), $this->input('tanggal_selesai'))['penuh'];
                $min = max(1, (int) $tarif->jenisSewa->durasi_minimum);
                if ($bulan < $min) {
                    $palingCepat = Carbon::parse($this->input('tanggal_mulai'))->addMonthsNoOverflow($min)->translatedFormat('j F Y');
                    $v->errors()->add('tanggal_selesai', "Masa sewa bulanan minimal {$min} bulan. Tanggal berakhir paling cepat {$palingCepat}.");
                }
            }

            // Sewa Bulan: minimal 1 dokumen tersisa (gabungan dokumen lama yang dipertahankan
            // + berkas baru yang baru diunggah) — dihitung di sini (bukan cuma cek array
            // 'dokumen' langsung) karena dokumen boleh sudah ada dari item Bulan lain di
            // keranjang, jadi tidak wajib upload ulang tiap kali menambah ruangan Bulan.
            if ($tarif->jenisSewa?->satuan === SatuanSewa::Bulan) {
                $dipertahankan = count($this->input('dokumen_pertahankan', []));
                $baru = count(array_filter(is_array($this->file('dokumen', [])) ? $this->file('dokumen', []) : [$this->file('dokumen')]));
                if ($dipertahankan + $baru < 1) {
                    $v->errors()->add('dokumen', 'Wajib melampirkan minimal 1 dokumen persyaratan (Company Profile / legalitas / KTP penanggung jawab) untuk sewa bulanan.');
                }
            }
        });
    }

    /** Kapasitas terkecil di antara ruangan utama + ruangan antrian (multi-pilih denah). */
    public function kapasitasMaksimal(): ?int
    {
        $ids = array_filter(array_merge(
            [(int) $this->input('id_fasilitas')],
            array_map('intval', explode(',', (string) $this->input('antrian'))),
        ));

        $kapasitas = Fasilitas::whereIn('id_fasilitas', $ids)->min('kapasitas');

        return $kapasitas === null ? null : (int) $kapasitas;
    }

    /** Tarif (beserta jenis & fasilitas) dari input, di-cache sekali. */
    public function tarif(): ?TarifSewa
    {
        if (! $this->tarifLoaded) {
            $this->tarifLoaded = true;
            $id = $this->input('id_tarif_sewa');
            $this->tarifCache = $id ? TarifSewa::with(['jenisSewa', 'fasilitas'])->find($id) : null;
        }

        return $this->tarifCache;
    }

    public function attributes(): array
    {
        return [
            'id_fasilitas'    => 'fasilitas',
            'id_tarif_sewa'   => 'tarif sewa',
            'tanggal_mulai'   => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
            'jam_mulai'       => 'jam mulai',
            'jam_selesai'     => 'jam selesai',
            'jumlah_pengguna' => 'jumlah pengguna',
            'keperluan'       => 'keperluan',
            'nama_lengkap'    => 'nama lengkap',
            'alamat'          => 'alamat',
            'usia'            => 'usia',
            'pekerjaan'       => 'pekerjaan',
            'no_telepon'      => 'nomor telepon',
        ];
    }

    public function messages(): array
    {
        return [
            'id_fasilitas.exists'          => 'Fasilitas yang dipilih tidak tersedia atau sedang tidak aktif.',
            'id_tarif_sewa.exists'         => 'Tarif sewa untuk fasilitas ini tidak tersedia.',
            'tanggal_mulai.required'       => 'Tanggal mulai wajib dipilih.',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'tanggal_selesai.required'     => 'Tanggal berakhir wajib dipilih.',
            'tanggal_selesai.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'tanggal_selesai.after'        => 'Tanggal berakhir harus setelah tanggal mulai.',
            'jam_mulai.required'           => 'Jam mulai wajib dipilih.',
            'jam_selesai.required'         => 'Jam selesai wajib dipilih.',
            'jam_selesai.after'            => 'Jam selesai harus setelah jam mulai.',
            'jumlah_pengguna.required'     => 'Jumlah pengguna wajib diisi.',
            'jumlah_pengguna.integer'      => 'Jumlah pengguna harus berupa angka.',
            'jumlah_pengguna.min'          => 'Jumlah pengguna minimal 1 orang.',
            'keperluan.required'           => 'Keperluan wajib diisi.',
            'keperluan.min'                => 'Keperluan minimal 5 karakter.',
            'keperluan.max'                => 'Keperluan maksimal 1000 karakter.',
            'jam_mulai.date_format'        => 'Format jam mulai tidak valid.',
            'jam_selesai.date_format'      => 'Format jam selesai tidak valid.',
            'no_telepon.regex'             => 'Nomor telepon pada profil tidak valid. Perbarui profil Anda terlebih dahulu.',
            'nama_lengkap.required'        => 'Nama lengkap pada profil belum diisi. Lengkapi profil Anda terlebih dahulu.',
            'alamat.required'              => 'Alamat pada profil belum diisi. Lengkapi profil Anda terlebih dahulu.',
            'usia.required'                => 'Usia pada profil belum diisi. Lengkapi profil Anda terlebih dahulu.',
            'usia.min'                     => 'Pemesan minimal berusia 17 tahun.',
            'usia.max'                     => 'Usia pada profil tidak valid.',
            'pekerjaan.required'           => 'Pekerjaan pada profil belum diisi. Lengkapi profil Anda terlebih dahulu.',
            'no_telepon.required'          => 'Nomor telepon pada profil belum diisi. Lengkapi profil Anda terlebih dahulu.',
            'dokumen.*.mimes'              => 'Dokumen harus berformat PDF, JPG, atau PNG.',
            'dokumen.*.max'                => 'Ukuran tiap dokumen maksimal 5 MB.',
            'no_telepon.regex'             => 'Nomor telepon pada profil hanya boleh berisi angka (boleh diawali tanda +).',
        ];
    }
}
