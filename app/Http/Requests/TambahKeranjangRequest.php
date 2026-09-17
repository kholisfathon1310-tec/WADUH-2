<?php

namespace App\Http\Requests;

use App\Enums\SatuanSewa;
use App\Enums\StatusAktif;
use App\Models\TarifSewa;
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
            'keperluan'       => ['required', 'string', 'max:1000'],
            'edit_index'      => ['nullable', 'integer', 'min:0'],

            // Data diri Pemesan — diisi di form yang sama supaya keranjang sudah "siap" sebelum checkout.
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'alamat'       => ['required', 'string', 'max:500'],
            'usia'         => ['required', 'integer', 'min:17', 'max:120'],
            'pekerjaan'    => ['required', 'string', 'max:100'],
            'no_telepon'   => ['required', 'string', 'regex:/^[0-9+\-\s()]{8,20}$/'],
        ];

        // Aturan jadwal tergantung satuan sewa.
        if ($satuan === SatuanSewa::Jam) {
            $rules['jam_mulai']   = ['required', 'date_format:H:i'];
            $rules['jam_selesai'] = ['required', 'date_format:H:i', 'after:jam_mulai'];
        } elseif ($satuan === SatuanSewa::Hari) {
            $rules['tanggal_selesai'] = ['required', 'date', 'after_or_equal:tanggal_mulai'];
        } elseif ($satuan === SatuanSewa::Bulan) {
            $rules['tanggal_selesai'] = ['required', 'date', 'after:tanggal_mulai'];
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

            // Jumlah pengguna tidak boleh melebihi kapasitas.
            $fasilitas = $tarif->fasilitas;
            if ($fasilitas && $this->filled('jumlah_pengguna') && (int) $this->input('jumlah_pengguna') > $fasilitas->kapasitas) {
                $v->errors()->add('jumlah_pengguna', "Jumlah pengguna melebihi kapasitas fasilitas ({$fasilitas->kapasitas} orang).");
            }

            // Sewa Bulan: rentang harus memenuhi durasi_minimum (mis. 3 bulan).
            if ($tarif->jenisSewa?->satuan === SatuanSewa::Bulan && $this->filled('tanggal_mulai') && $this->filled('tanggal_selesai')) {
                $bulan = Carbon::parse($this->input('tanggal_mulai'))->diffInMonths(Carbon::parse($this->input('tanggal_selesai')));
                $min = (int) $tarif->jenisSewa->durasi_minimum;
                if ($bulan < $min) {
                    $v->errors()->add('tanggal_selesai', "Sewa bulanan minimal {$min} bulan.");
                }
            }
        });
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
            'tanggal_mulai.required'       => 'Tanggal mulai pemakaian wajib dipilih.',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu, pilih hari ini atau setelahnya.',
            'tanggal_selesai.required'     => 'Isi tanggal selesai sewa.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'tanggal_selesai.after'        => 'Tanggal selesai harus setelah tanggal mulai.',
            'jam_mulai.required'           => 'Isi jam mulai pemakaian (mis. 09:00).',
            'jam_selesai.required'         => 'Isi jam selesai pemakaian.',
            'jam_selesai.after'            => 'Jam selesai harus setelah jam mulai.',
            'jumlah_pengguna.required'     => 'Isi berapa orang yang akan memakai ruangan.',
            'jumlah_pengguna.min'          => 'Jumlah pengguna minimal 1 orang.',
            'keperluan.required'           => 'Keperluan pemakaian ruangan wajib diisi, contoh rapat tim.',
            'keperluan.max'                => 'Keperluan terlalu panjang, maksimal 1000 karakter.',
            'nama_lengkap.required'        => 'Nama lengkap wajib diisi sesuai identitas.',
            'alamat.required'              => 'Alamat wajib diisi.',
            'usia.required'                => 'Usia wajib diisi.',
            'usia.min'                     => 'Pemesan minimal berusia 17 tahun.',
            'usia.max'                     => 'Usia tidak valid.',
            'pekerjaan.required'           => 'Pekerjaan wajib diisi.',
            'no_telepon.required'          => 'Nomor telepon wajib diisi agar admin bisa menghubungi Anda.',
            'no_telepon.regex'             => 'Format nomor telepon tidak valid, gunakan angka saja, contoh 0812xxxxxxx.',
        ];
    }
}
