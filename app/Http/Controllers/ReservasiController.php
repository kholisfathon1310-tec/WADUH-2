<?php

namespace App\Http\Controllers;

use App\Enums\LockStatus;
use App\Enums\SatuanSewa;
use App\Enums\StatusAktif;
use App\Enums\StatusReservasi;
use App\Enums\StatusVerifikasi;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\TambahKeranjangRequest;
use App\Models\DokumenPersyaratan;
use App\Models\Fasilitas;
use App\Models\JenisSewa;
use App\Models\Lantai;
use App\Models\Reservasi;
use App\Models\TarifSewa;
use App\Services\AvailabilityService;
use App\Services\CartService;
use App\Services\StatusOtomatisService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReservasiController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly CartService $cart,
    ) {
    }

    /**
     * Ringkasan Lantai — pengganti katalog "semua ruangan" lama. Menampilkan 5 lantai
     * (mirror section "Fasilitas per Lantai" di homepage) yang mengarah ke Denah, supaya
     * alur Pemesan konsisten: Fasilitas (ringkasan lantai) → Denah → Detail.
     */
    public function index(): View
    {
        $lantai = Lantai::with('fasilitas')
            ->orderBy('id_lantai')
            ->get()
            ->map(function (Lantai $l) {
                $aktif = $l->fasilitas->where('status_aktif', StatusAktif::Aktif);

                // Lantai bisa campur kategori (mis. 3A/3B) — pakai kategori dengan jumlah
                // ruangan terbanyak, sama seperti HomeController.
                $kategoriUtama = $l->fasilitas
                    ->countBy('kategori_fasilitas')
                    ->sortDesc()
                    ->keys()
                    ->first();

                return [
                    'id'       => $l->id_lantai,
                    'nomor'    => $l->nomor_lantai,
                    'kategori' => $kategoriUtama ?? '-',
                    'total'    => $l->fasilitas->count(),
                    'tersedia' => $aktif->count(),
                    'kapasitas_maks' => $aktif->max('kapasitas'),
                ];
            });

        return view('reservasi.kategori', [
            'daftarLantai' => $lantai,
            'cartCount'    => $this->cart->count(),
        ]);
    }

    /** Langkah 2 — pilih jenis sewa yang tersedia untuk kategori. */
    public function jenisSewa(string $kategori): View
    {
        $this->pastikanKategoriAda($kategori);

        $jenis = $this->jenisUntukKategori($kategori);

        return view('reservasi.jenis-sewa', compact('kategori', 'jenis'));
    }

    /** Semua Jenis Sewa (Jam/Hari/Bulan) yang punya tarif aktif untuk kategori ini — dipakai switcher jenis sewa. */
    private function jenisUntukKategori(string $kategori)
    {
        return JenisSewa::query()
            ->whereHas('tarifSewa', function ($q) use ($kategori) {
                $q->where('status_aktif', StatusAktif::Aktif->value)
                    ->whereHas('fasilitas', fn ($f) => $f
                        ->where('status_aktif', StatusAktif::Aktif->value)
                        ->where('kategori_fasilitas', $kategori));
            })
            ->orderBy('id_jenis_sewa')
            ->get();
    }

    /** Langkah 3 — pilih lantai yang punya fasilitas kategori+jenis ini. */
    public function lantai(Request $request, string $kategori): View
    {
        $this->pastikanKategoriAda($kategori);
        $jenis = $this->jenisDipilih($request);

        $lantai = Lantai::query()
            ->whereHas('fasilitas', fn ($f) => $this->fasilitasScope($f, $kategori, $jenis?->id_jenis_sewa))
            ->orderBy('nomor_lantai')
            ->get();

        return view('reservasi.lantai', compact('kategori', 'jenis', 'lantai'));
    }

    /** Langkah 4 — denah fasilitas per lantai dengan indikator warna ketersediaan. */
    public function denah(Request $request, string $kategori, Lantai $lantai): View
    {
        $this->pastikanKategoriAda($kategori);
        $jenis = $this->jenisDipilih($request);

        $fasilitas = Fasilitas::query()
            ->where('id_lantai', $lantai->id_lantai)
            ->where(fn ($f) => $this->fasilitasScope($f, $kategori, $jenis?->id_jenis_sewa))
            ->orderBy('nama_fasilitas')
            ->get();

        $slot = $this->slotDariRequest($request, $jenis?->satuan);
        $sessionId = $request->session()->getId();

        $status = $fasilitas->mapWithKeys(fn (Fasilitas $f) => [
            $f->id_fasilitas => $this->availability->statusFasilitas($f, $slot, $sessionId),
        ]);

        // Per Hari / Per Bulan: ruangan yang sebagian terisi tidak dapat dipesan, jadi langsung
        // ditandai Terisi (merah). Status Sebagian Terisi (kuning) hanya tampil pada Per Jam
        // dan saat jenis sewa belum dipilih.
        if ($jenis && $jenis->satuan !== SatuanSewa::Jam) {
            $status = $status->map(fn (string $s) => $s === 'kuning' ? 'merah' : $s);
        }

        // Filter interaktif: request AJAX cukup dibalas fragmen hasil, tanpa layout.
        if ($request->ajax()) {
            return view('reservasi.partials.denah-hasil', compact('kategori', 'jenis', 'lantai', 'fasilitas', 'status', 'slot'));
        }

        $semuaJenis = $this->jenisUntukKategori($kategori);

        return view('reservasi.denah', compact('kategori', 'jenis', 'lantai', 'fasilitas', 'status', 'slot', 'semuaJenis'));
    }

    /**
     * Detail fasilitas + pilih Jenis Sewa + form jadwal — satu halaman (sesuai revisi: Kategori
     * & Jenis Sewa tampil sebagai bagian dari proses reservasi SETELAH fasilitas dipilih, bukan
     * langkah/halaman tersendiri). Jenis sewa default ke opsi pertama yang tersedia (urutan
     * Jam → Hari → Bulan), bisa diganti lewat pill switcher (?jenis=) tanpa reload penuh alur.
     */
    public function fasilitas(Request $request, Fasilitas $fasilitas): View
    {
        abort_if($fasilitas->status_aktif !== StatusAktif::Aktif, 404);

        $urutanSatuan = ['Jam', 'Hari', 'Bulan'];
        $semuaTarif = TarifSewa::tersedia()
            ->where('id_fasilitas', $fasilitas->id_fasilitas)
            ->with('jenisSewa')
            ->get()
            ->sortBy(fn (TarifSewa $t) => array_search($t->jenisSewa->satuan->value, $urutanSatuan))
            ->values();

        abort_if($semuaTarif->isEmpty(), 404, 'Belum ada tarif aktif untuk fasilitas ini.');

        // Mode "Ubah" (dari Keranjang): kalau item di index tsb memang ruangan ini, kirim
        // datanya ke view untuk mengisi ulang form jadwal (lihat atur-jadwal-modal.blade.php).
        $editIndex = $request->filled('edit_index') ? (int) $request->input('edit_index') : null;
        $editItem = null;
        if ($editIndex !== null) {
            $item = $this->cart->get($editIndex);
            if ($item && (int) $item['id_fasilitas'] === $fasilitas->id_fasilitas) {
                $editItem = $item;
            }
        }

        // Kondisi fasilitas (Tersedia / Sebagian Terisi / Terisi) pada tanggal acuan — dihitung
        // dari jadwal reservasi tersimpan + hold keranjang session lain — menentukan jenis sewa
        // mana yang masih dapat dipesan (lihat AvailabilityService::bisaDipesan()).
        $sessionId = $request->session()->getId();
        $tanggalAcuan = $this->tanggalValid($request->input('tanggal_mulai'), $editItem['tanggal_mulai'] ?? null);
        // Multi-pilih dari denah (?antrian=id2,id3): satu jadwal berlaku untuk semua ruangan,
        // jadi kondisi gabungannya = kondisi terburuk di antara ruangan terpilih.
        $semuaRuangan = $this->ruanganDariRequest($request, $fasilitas);
        $kondisiPerRuangan = $semuaRuangan->mapWithKeys(fn (Fasilitas $f) => [
            $f->id_fasilitas => $this->kondisiFasilitas($f, $tanggalAcuan, $sessionId),
        ]);
        $kondisi = $this->gabungKondisi($kondisiPerRuangan->all(), $tanggalAcuan);

        $tarifPerRuangan = TarifSewa::tersedia()
            ->whereIn('id_fasilitas', $semuaRuangan->pluck('id_fasilitas'))
            ->with('jenisSewa')
            ->get()
            ->groupBy('id_fasilitas');

        $jenisId = $request->integer('jenis') ?: (int) ($editItem['id_jenis_sewa'] ?? 0);
        $tarif = ($jenisId ? $semuaTarif->firstWhere('id_jenis_sewa', $jenisId) : null)
            ?? $semuaTarif->first(fn (TarifSewa $t) => $kondisi['bisa'][$t->jenisSewa->satuan->value] ?? false)
            ?? $semuaTarif->first();

        return view('reservasi.fasilitas', [
            'fasilitas'         => $fasilitas,
            'semuaRuangan'      => $semuaRuangan,
            'kondisiPerRuangan' => $kondisiPerRuangan,
            'tarifPerRuangan'   => $tarifPerRuangan,
            'tarif'             => $tarif,
            'jenis'             => $tarif->jenisSewa,
            'semuaTarif'        => $semuaTarif,
            // Rentang jam terisi pada tanggal acuan — dipakai jam-picker supaya jam yang sudah
            // terisi tidak bisa dipilih; di-refresh via AJAX tiap tanggal di form diganti.
            'jamTerisi'         => $kondisi['jam_terisi'],
            'kondisi'           => $kondisi,
            'tanggalAcuan'      => $tanggalAcuan,
            'pemesan'           => Auth::guard('customer')->user(),
            'editItem'          => $editItem,
            'editIndex'         => $editIndex,
        ]);
    }

    /**
     * AJAX: rentang jam yang sudah terisi pada 1 tanggal (gabungan seluruh ruangan terpilih)
     * — dipanggil jam-picker (pilih-jam.blade.php) tiap tanggal di form diganti.
     */
    public function jamTerisi(Request $request, Fasilitas $fasilitas): JsonResponse
    {
        $tanggal = $this->tanggalValid($request->input('tanggal'), null, false);
        if (! $tanggal) {
            return response()->json([]);
        }

        $sessionId = $request->session()->getId();
        $perRuangan = $this->ruanganDariRequest($request, $fasilitas)
            ->map(fn (Fasilitas $f) => $this->kondisiFasilitas($f, $tanggal, $sessionId))
            ->all();

        return response()->json($this->gabungKondisi($perRuangan, $tanggal)['jam_terisi']);
    }

    /** Ruangan utama + ruangan antrian (multi-pilih denah, ?antrian=id2,id3) yang masih aktif. */
    private function ruanganDariRequest(Request $request, Fasilitas $fasilitas): \Illuminate\Support\Collection
    {
        $fasilitas->loadMissing('lantai');
        $idsLain = array_values(array_unique(array_filter(
            array_map('intval', explode(',', (string) $request->input('antrian'))),
            fn (int $id) => $id > 0 && $id !== $fasilitas->id_fasilitas,
        )));

        $lain = $idsLain
            ? Fasilitas::with('lantai')
                ->whereIn('id_fasilitas', $idsLain)
                ->where('status_aktif', StatusAktif::Aktif->value)
                ->orderBy('nama_fasilitas')
                ->get()
            : collect();

        return collect([$fasilitas])->concat($lain)->values();
    }

    /**
     * Gabungkan kondisi beberapa ruangan menjadi satu: status terburuk, jenis sewa hanya
     * dapat dipesan bila SEMUA ruangan mengizinkan, jam terisi = gabungan semua ruangan.
     *
     * @param  array<int, array>  $perRuangan
     */
    private function gabungKondisi(array $perRuangan, string $tanggal): array
    {
        $urutan = ['hijau' => 0, 'kuning' => 1, 'merah' => 2];
        $status = 'hijau';
        $jam = [];
        $bisa = [];

        foreach ($perRuangan as $k) {
            if ($urutan[$k['status']] > $urutan[$status]) {
                $status = $k['status'];
            }
            $jam = array_merge($jam, $k['jam_terisi']);
            foreach ($k['bisa'] as $satuan => $boleh) {
                $bisa[$satuan] = ($bisa[$satuan] ?? true) && $boleh;
            }
        }

        return [
            'status'     => $status,
            'label'      => AvailabilityService::labelStatus($status),
            'tanggal'    => $tanggal,
            'jam_terisi' => $this->availability->gabungRentangJam($jam),
            'bisa'       => $bisa,
        ];
    }

    /**
     * AJAX: pengecekan jadwal SEBELUM form disimpan — kondisi fasilitas pada tanggal mulai,
     * jenis sewa yang masih dapat dipesan, dan (kalau jadwalnya sudah lengkap) apakah jadwal
     * itu bentrok beserta tanggal/jam penyebabnya. Validasi final tetap di tambahKeranjang().
     */
    public function cekJadwal(Request $request, Fasilitas $fasilitas): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $tanggalMulai = $this->tanggalValid($request->input('tanggal_mulai'), null, false);
        if (! $tanggalMulai) {
            return response()->json(['tersedia' => true, 'pesan' => [], 'kondisi' => null]);
        }

        $ruangan = $this->ruanganDariRequest($request, $fasilitas);
        $kondisi = $this->gabungKondisi(
            $ruangan->map(fn (Fasilitas $f) => $this->kondisiFasilitas($f, $tanggalMulai, $sessionId))->all(),
            $tanggalMulai,
        );
        $satuan = SatuanSewa::tryFrom((string) $request->input('satuan'));
        $editIndex = $request->filled('edit_index') ? (int) $request->input('edit_index') : null;

        $slot = null;
        if ($satuan === SatuanSewa::Jam) {
            $jamMulai = (string) $request->input('jam_mulai');
            $jamSelesai = (string) $request->input('jam_selesai');
            if (preg_match('/^\d{2}:\d{2}$/', $jamMulai) && preg_match('/^\d{2}:\d{2}$/', $jamSelesai) && $jamSelesai > $jamMulai) {
                $slot = ['tanggal_mulai' => $tanggalMulai, 'tanggal_selesai' => $tanggalMulai, 'jam_mulai' => $jamMulai, 'jam_selesai' => $jamSelesai];
            }
        } elseif ($satuan !== null) {
            $tanggalSelesai = $this->tanggalValid($request->input('tanggal_selesai'), null, false);
            if ($tanggalSelesai && $tanggalSelesai >= $tanggalMulai && Carbon::parse($tanggalMulai)->diffInDays($tanggalSelesai) <= 366 * 5) {
                $slot = ['tanggal_mulai' => $tanggalMulai, 'tanggal_selesai' => $tanggalSelesai, 'jam_mulai' => null, 'jam_selesai' => null];
            }
        }

        $pesan = [];
        if ($slot) {
            $multi = $ruangan->count() > 1;

            foreach ($ruangan as $f) {
                $galat = $this->galatJadwal($f->id_fasilitas, $slot, $sessionId, $editIndex);
                if ($galat !== null) {
                    $pesan[] = ($multi ? $f->nama_fasilitas.': ' : '').$galat;
                }
            }
        }

        // Jadwal belum lengkap, tetapi kondisi fasilitas pada tanggal mulai sudah menutup
        // jenis sewa yang sedang dipilih — beri tahu lebih awal.
        if ($pesan === [] && $satuan !== null && ! $kondisi['bisa'][$satuan->value]) {
            $tanggal = Carbon::parse($tanggalMulai)->translatedFormat('l, j F Y');
            $pesan[] = $kondisi['status'] === 'merah'
                ? "Fasilitas sudah terisi penuh pada {$tanggal}. Silakan pilih tanggal lain."
                : "Fasilitas sebagian terisi pada {$tanggal}, sehingga hanya dapat dipesan per jam pada jam yang masih kosong. Silakan pilih tanggal lain untuk sewa per ".strtolower($satuan->value).'.';
        }

        return response()->json([
            'tersedia' => $pesan === [],
            'pesan'    => $pesan,
            'kondisi'  => $kondisi,
        ]);
    }

    /**
     * Kondisi fasilitas pada satu tanggal + jenis sewa yang masih dapat dipesan.
     *
     * @return array{status: string, label: string, tanggal: string, jam_terisi: array, bisa: array<string, bool>}
     */
    private function kondisiFasilitas(Fasilitas $fasilitas, string $tanggal, string $sessionId): array
    {
        $hari = $this->availability->kondisiHarian($fasilitas->id_fasilitas, $tanggal, $tanggal, $sessionId)[$tanggal];
        $status = ['bebas' => 'hijau', 'sebagian' => 'kuning', 'penuh' => 'merah'][$hari['status']];

        $bisa = [];
        foreach (SatuanSewa::cases() as $satuan) {
            $bisa[$satuan->value] = AvailabilityService::bisaDipesan($status, $satuan);
        }

        return [
            'status'     => $status,
            'label'      => AvailabilityService::labelStatus($status),
            'tanggal'    => $tanggal,
            // Terisi seharian (reservasi harian/bulanan) → seluruh jam operasional terkunci.
            'jam_terisi' => $hari['status'] === 'penuh' && $hari['jam'] === []
                ? [['mulai' => '00:00', 'selesai' => '24:00']]
                : $hari['jam'],
            'bisa'       => $bisa,
        ];
    }

    /**
     * Pesan bentrok untuk satu ruangan pada slot yang diminta (null = jadwal bebas): bentrok
     * dengan item lain di keranjang sendiri, atau dengan jadwal tersimpan/hold session lain.
     */
    private function galatJadwal(int $fasilitasId, array $slot, string $sessionId, ?int $editIndex = null): ?string
    {
        if ($this->cart->hasConflict(['id_fasilitas' => $fasilitasId] + $slot, $this->availability, $editIndex)) {
            return 'Fasilitas ini sudah ada di keranjang Anda dengan jadwal yang bertumpang-tindih.';
        }

        $detail = $this->availability->detailBentrok($fasilitasId, $slot, $sessionId);

        return $detail['bentrok'] ? $this->availability->pesanBentrok($slot, $detail) : null;
    }

    /** Tanggal Y-m-d yang valid dari input bebas; selain itu pakai cadangan / hari ini. */
    private function tanggalValid(mixed $nilai, ?string $cadangan = null, bool $defaultHariIni = true): ?string
    {
        foreach ([$nilai, $cadangan] as $kandidat) {
            if (is_string($kandidat) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $kandidat)) {
                [$y, $m, $d] = array_map('intval', explode('-', $kandidat));
                if (checkdate($m, $d, $y)) {
                    return $kandidat;
                }
            }
        }

        return $defaultHariIni ? Carbon::today()->toDateString() : null;
    }

    /**
     * Tambah ke keranjang + pasang cache hold. Multi-select denah: SATU form jadwal
     * berlaku untuk semua ruangan terpilih (?antrian=id2,id3) — semua masuk sekaligus,
     * atomic (kalau satu ruangan bermasalah, tidak ada yang ditambahkan).
     *
     * Seluruh baca-cek-tulis keranjang dikunci per session (Cache::lock) supaya dua
     * request yang hampir bersamaan dari session yang sama (mis. klik ganda, atau
     * beberapa tab) tidak lolos bersamaan lewat pengecekan bentrok — tanpa lock, keduanya
     * bisa membaca isi keranjang SEBELUM salah satu sempat menyimpan, jadi keduanya sama-sama
     * menganggap tidak ada bentrok dan menyimpan jadwal yang sebenarnya tumpang tindih.
     */
    public function tambahKeranjang(TambahKeranjangRequest $request): RedirectResponse
    {
        $tarifUtama = $request->tarif();
        $data = $request->validated();
        $sessionId = $request->session()->getId();
        $editIndex = $request->filled('edit_index') ? (int) $request->input('edit_index') : null;

        // Data diri Pemesan diisi di form yang sama (bukan lagi di checkout) — simpan ke akun
        // begitu jadwal tervalidasi, supaya keranjang & checkout selalu memakai data terbaru.
        Auth::guard('customer')->user()->update([
            'nama_lengkap' => $data['nama_lengkap'],
            'alamat'       => $data['alamat'],
            'usia'         => $data['usia'],
            'pekerjaan'    => $data['pekerjaan'],
            'no_telepon'   => $data['no_telepon'],
        ]);

        try {
            return Cache::lock("keranjang-lock:{$sessionId}", 10)->block(5, function () use ($request, $tarifUtama, $data, $sessionId, $editIndex) {
                // PENTING: session ini sudah dimuat ke memori SEBELUM request menunggu giliran
                // lock di atas, jadi isinya bisa basi begitu giliran tiba — mis. keranjang baru
                // saja ditulis oleh request lain untuk session yang sama, tepat sebelum lock ini
                // didapat. start() memuat ulang dari storage supaya pengecekan bentrok di bawah
                // memakai isi keranjang yang benar-benar terbaru, bukan salinan basi.
                $request->session()->start();

                $idsLain = array_values(array_filter(explode(',', (string) $request->input('antrian'))));

                // Mode "Ubah" (dari Keranjang): timpa 1 item yang sudah ada, tidak boleh
                // dicampur dengan penambahan ruangan baru dari denah (antrian) — indeks
                // keranjang bisa jadi tidak konsisten kalau keduanya digabung.
                if ($editIndex !== null) {
                    if ($idsLain !== []) {
                        return back()->withInput()->withErrors([
                            'edit_index' => 'Jadwal tidak dapat diubah bersamaan dengan penambahan fasilitas baru. Batalkan pilihan fasilitas lain terlebih dahulu.',
                        ]);
                    }

                    $existing = $this->cart->get($editIndex);
                    if (! $existing || (int) $existing['id_fasilitas'] !== (int) $tarifUtama->id_fasilitas) {
                        return back()->withInput()->withErrors([
                            'edit_index' => 'Item yang akan diubah tidak ditemukan. Silakan ulangi dari halaman Keranjang.',
                        ]);
                    }
                }

                // Kumpulkan tarif semua ruangan: utama + antrian (jenis sewa sama dengan ruangan utama).
                $daftarTarif = collect([$tarifUtama]);
                foreach ($idsLain as $idFasilitas) {
                    $t = TarifSewa::where('status_aktif', StatusAktif::Aktif->value)
                        ->where('id_fasilitas', $idFasilitas)
                        ->where('id_jenis_sewa', $tarifUtama->id_jenis_sewa)
                        ->whereHas('fasilitas', fn ($q) => $q->where('status_aktif', StatusAktif::Aktif->value))
                        ->with('fasilitas', 'jenisSewa')
                        ->first();

                    if (! $t) {
                        return back()->withInput()->withErrors([
                            'antrian' => 'Salah satu fasilitas yang dipilih tidak tersedia untuk jenis sewa ini. Silakan pilih ulang melalui denah.',
                        ]);
                    }
                    $daftarTarif->push($t);
                }

                // Bangun & periksa SEMUA item dulu — kapasitas & ketersediaan per ruangan.
                $items = [];
                $galat = [];
                foreach ($daftarTarif as $t) {
                    $item = $this->cart->buildItem($t, $data);
                    $slot = [
                        'tanggal_mulai'   => $item['tanggal_mulai'],
                        'tanggal_selesai' => $item['tanggal_selesai'],
                        'jam_mulai'       => $item['jam_mulai'],
                        'jam_selesai'     => $item['jam_selesai'],
                    ];

                    $awalan = $daftarTarif->count() > 1 ? "{$t->fasilitas->nama_fasilitas}: " : '';

                    if ((int) $data['jumlah_pengguna'] > $t->fasilitas->kapasitas) {
                        $galat[] = "{$awalan}Jumlah pengguna melebihi kapasitas maksimal fasilitas ({$t->fasilitas->kapasitas} orang).";
                    } elseif ($this->bentrokDiBatch($items, $item)) {
                        $galat[] = "{$awalan}Fasilitas ini dipilih lebih dari satu kali dengan jadwal yang sama.";
                    } elseif (($pesan = $this->galatJadwal($item['id_fasilitas'], $slot, $sessionId, $editIndex)) !== null) {
                        $galat[] = $awalan.$pesan;
                    } else {
                        $items[] = $item;
                    }
                }

                if ($galat !== []) {
                    return back()->withInput()->withErrors(['jadwal' => $galat]);
                }

                // Dokumen persyaratan Bulan — satu lampiran untuk seluruh keranjang, diproses
                // begitu validasi jadwal & ketersediaan lolos (bukan sebelumnya, supaya tidak
                // menyimpan berkas untuk request yang ujung-ujungnya gagal karena bentrok dll).
                if ($tarifUtama->jenisSewa->satuan === SatuanSewa::Bulan) {
                    $this->prosesDokumen($request);
                }

                if ($editIndex !== null) {
                    $oldItem = $this->cart->get($editIndex);
                    $this->availability->releaseHold($oldItem);
                    $this->availability->putHold($items[0], $sessionId);
                    $this->cart->replace($editIndex, $items[0]);

                    $request->session()->save();

                    return redirect()->route('reservasi.checkout.form')->with('success', "Jadwal {$items[0]['nama_fasilitas']} berhasil diperbarui.");
                }

                foreach ($items as $item) {
                    $this->availability->putHold($item, $sessionId);
                    $this->cart->add($item);
                }

                // Simpan session SEKARANG (bukan menunggu StartSession menyimpannya belakangan
                // di akhir request) — supaya begitu lock ini dilepas, request lain yang tadi
                // antre langsung melihat keranjang yang sudah ter-update, bukan versi lama.
                $request->session()->save();

                $n = count($items);

                return redirect()->route('reservasi.checkout.form')->with('success', $n > 1
                    ? "{$n} fasilitas berhasil ditambahkan ke keranjang dengan jadwal yang sama."
                    : "{$items[0]['nama_fasilitas']} berhasil ditambahkan ke keranjang.");
            });
        } catch (LockTimeoutException) {
            return back()->withInput()->withErrors([
                'antrian' => 'Keranjang Anda sedang memproses permintaan lain. Silakan coba kembali beberapa saat lagi.',
            ]);
        }
    }

    /** Ruangan yang sama dengan jadwal bentrok sudah ada di antara item yang baru dikumpulkan pada batch ini? */
    private function bentrokDiBatch(array $items, array $item): bool
    {
        foreach ($items as $existing) {
            if (
                (int) $existing['id_fasilitas'] === (int) $item['id_fasilitas']
                && $this->availability->slotsConflict($existing, $item)
            ) {
                return true;
            }
        }

        return false;
    }

    /** Hapus item dari keranjang + lepas cache hold. */
    public function hapusKeranjang(int $index): RedirectResponse
    {
        $removed = $this->cart->remove($index);
        if ($removed) {
            $this->availability->releaseHold($removed);
        }

        // Kalau item Bulan terakhir baru saja dihapus, dokumen persyaratan yang tadi
        // tersimpan jadi yatim (tidak dipakai ruangan Bulan mana pun lagi) — bersihkan.
        if (! $this->cart->hasBulan()) {
            $this->cart->clearDokumen(true);
        }

        // Kalau ini item terakhir, back() akan mendarat di halaman checkout yang langsung
        // redirect lagi (keranjang kosong) dan menimpa pesan sukses ini — jadi arahkan
        // langsung ke katalog Fasilitas supaya pesan "item dihapus" benar-benar terlihat.
        if ($this->cart->isEmpty()) {
            return redirect()->route('reservasi.index')->with('success', 'Item berhasil dihapus. Keranjang Anda kosong.');
        }

        return back()->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    /** Kosongkan seluruh keranjang + lepas semua cache hold. */
    public function kosongkanKeranjang(): RedirectResponse
    {
        foreach ($this->cart->items() as $item) {
            $this->availability->releaseHold($item);
        }
        $this->cart->clearDokumen(true);
        $this->cart->clear();

        return redirect()->route('reservasi.checkout.form')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    /** Halaman Keranjang: ringkasan keranjang + form data diri. Selalu tampil, termasuk saat kosong. */
    public function checkoutForm(): View
    {
        return view('reservasi.checkout', [
            'items'        => $this->cart->items(),
            'total'        => $this->cart->total(),
            'hasBulan'     => $this->cart->hasBulan(),
            'dokumenBulan' => $this->cart->dokumen(),
            'pemesan'      => Auth::guard('customer')->user(),
        ]);
    }

    /**
     * Proses submit checkout: buat Reservasi per item (+ dokumen untuk Bulan). Data diri Pemesan
     * sudah disimpan sebelumnya lewat form "Isi Jadwal" (lihat tambahKeranjang()), jadi di sini
     * tinggal dipakai langsung, tidak perlu diminta/diperbarui lagi.
     */
    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('reservasi.index')->with('error', 'Keranjang Anda masih kosong. Pilih fasilitas terlebih dahulu.');
        }

        $items = $this->cart->items();
        $pemesan = Auth::guard('customer')->user();
        $sessionId = $request->session()->getId();

        // Keranjang kini tersimpan lintas login, jadi itemnya bisa saja sudah melewati
        // tanggal mulai saat akhirnya diajukan.
        $hariIni = Carbon::today()->toDateString();
        $lampau = array_filter($items, fn (array $i) => $i['tanggal_mulai'] < $hariIni);
        if ($lampau !== []) {
            return redirect()->route('reservasi.checkout.form')->withErrors(['jadwal' => array_map(
                fn (array $i) => "{$i['nama_fasilitas']}: tanggal mulai ".Carbon::parse($i['tanggal_mulai'])->translatedFormat('j F Y')
                    .' sudah lewat. Ubah jadwal atau hapus item ini dari keranjang.',
                array_values($lampau),
            )]);
        }

        $hasil = DB::transaction(function () use ($items, $pemesan, $sessionId) {
            // 0. Cek ulang ketersediaan SEMUA item tepat sebelum disimpan — hold keranjang
            //    hanya berlaku 15 menit, jadi jadwalnya bisa saja sudah diisi reservasi lain
            //    sejak item dimasukkan ke keranjang. Baris tarif fasilitas dikunci dulu
            //    (lockForUpdate) supaya dua checkout bersamaan untuk fasilitas yang sama
            //    diproses bergantian, bukan sama-sama lolos.
            TarifSewa::whereIn('id_fasilitas', array_unique(array_column($items, 'id_fasilitas')))
                ->orderBy('id_tarif_sewa')
                ->lockForUpdate()
                ->get(['id_tarif_sewa']);

            $galat = [];
            foreach ($items as $item) {
                $slot = [
                    'tanggal_mulai'   => $item['tanggal_mulai'],
                    'tanggal_selesai' => $item['tanggal_selesai'],
                    'jam_mulai'       => $item['jam_mulai'],
                    'jam_selesai'     => $item['jam_selesai'],
                ];
                $detail = $this->availability->detailBentrok($item['id_fasilitas'], $slot, $sessionId);
                if ($detail['bentrok']) {
                    $galat[] = "{$item['nama_fasilitas']}: ".$this->availability->pesanBentrok($slot, $detail);
                }
            }
            if ($galat !== []) {
                return ['galat' => $galat];
            }

            // 1. SATU kode simpel per checkout (mis. RS186) untuk seluruh ruangan.
            //    Bila lebih dari satu ruangan, tiap baris diberi huruf (RS186A, RS186B, ...) karena kode_reservasi UNIQUE per baris,
            //    tapi kode yang ditampilkan ke pemesan tetap satu: kode dasar (= kode_transaksi).
            $kodeDasar = $this->generateKode();
            $multi = count($items) > 1;
            $kodeReservasi = [];

            foreach ($items as $index => $item) {
                $reservasi = Reservasi::create([
                    'id_pemesan'       => $pemesan->id_pemesan,
                    'id_tarif_sewa'    => $item['id_tarif_sewa'],
                    'id_admin'         => null,
                    'kode_reservasi'   => Reservasi::kodeRuangan($kodeDasar, $index, $multi),
                    'kode_transaksi'   => $kodeDasar,
                    'tanggal_mulai'    => $item['tanggal_mulai'],
                    'tanggal_selesai'  => $item['tanggal_selesai'],
                    'jam_mulai'        => $item['jam_mulai'],
                    'jam_selesai'      => $item['jam_selesai'],
                    'durasi'           => $item['durasi'],
                    'jumlah_pengguna'  => $item['jumlah_pengguna'],
                    'keperluan'        => $item['keperluan'],
                    'harga_satuan'     => $item['harga_satuan'],
                    'total_biaya'      => $item['total_biaya'],
                    'status_reservasi' => StatusReservasi::Menunggu,
                    // Alur utama Stage 2 langsung pending_approval saat submit.
                    // (lock_status='temporary_hold' disediakan untuk pengembangan lanjutan — lihat spec.)
                    'lock_status'      => LockStatus::PendingApproval,
                    'lock_expires_at'  => null,
                    'tanggal_diproses' => null,
                ]);

                // 3. Item Bulan wajib punya dokumen persyaratan — sudah diunggah & tervalidasi
                //    sejak langkah "Isi Jadwal" (lihat CartService::dokumen()), bukan lagi di sini.
                //    Satu lampiran berlaku untuk SEMUA ruangan Bulan pada transaksi ini, jadi
                //    disalin ke tiap baris Reservasi Bulan (skema id_reservasi tetap per-baris).
                if ($item['satuan'] === SatuanSewa::Bulan->value) {
                    $this->simpanDokumen($reservasi);
                }

                $kodeReservasi[] = $reservasi->kode_reservasi;
            }

            return ['kode_transaksi' => $kodeDasar, 'kode_reservasi' => $kodeReservasi];
        });

        if (isset($hasil['galat'])) {
            return redirect()->route('reservasi.checkout.form')->withErrors(['jadwal' => $hasil['galat']]);
        }

        // 4 & 5. Setelah commit: lepas semua cache hold, kosongkan keranjang. Dokumen TIDAK
        // dihapus dari storage (hapusFile=false) — path filenya sekarang jadi lokasi_file
        // milik baris DokumenPersyaratan yang baru dibuat, masih dipakai.
        foreach ($items as $item) {
            $this->availability->releaseHold($item);
        }
        $this->cart->clearDokumen(false);
        $this->cart->clear();

        // 6. Langsung ke Reservasi Saya — pop-up konfirmasi tampil di halaman itu (lihat
        // session('checkout') di layouts.customer), bukan lewat halaman perantara lagi.
        return redirect()->route('customer.reservasi-saya.index')->with('checkout', $hasil);
    }

    /** Halaman notifikasi sukses (kode_transaksi + daftar kode_reservasi). */
    public function sukses(): View|RedirectResponse
    {
        $checkout = session('checkout');
        if (! $checkout) {
            return redirect()->route('reservasi.index');
        }

        return view('reservasi.sukses', ['checkout' => $checkout]);
    }

    /**
     * Pembatalan mandiri oleh pemesan — dari halaman Cek Status. Redirect BALIK ke halaman
     * hasil pencarian yang sama (bukan back(), yang bisa jatuh ke form kosong karena hasil
     * pencarian awalnya dirender dari POST), supaya pemesan tidak "terlempar keluar" dan
     * reservasi yang baru dibatalkan tetap terlihat di tempatnya dengan status terbaru.
     */
    public function batalkan(Request $request, string $kode_reservasi, StatusOtomatisService $statusOtomatis): RedirectResponse
    {
        // Ruangan yang sudah lewat batas persetujuan menjadi Kadaluwarsa dulu, bukan Dibatalkan.
        $statusOtomatis->kadaluwarsakanRuanganTerlambat();

        $reservasi = Reservasi::where('kode_reservasi', $kode_reservasi)->firstOrFail();

        // Dipanggil dari 2 tempat: Cek Status publik (default) dan "Reservasi Saya" (mengirim
        // `kembali` berisi path lokal supaya kembali ke halamannya sendiri, bukan Cek Status).
        $kembaliInput = $request->input('kembali');
        $kembaliKe = (is_string($kembaliInput) && str_starts_with($kembaliInput, '/'))
            ? $kembaliInput
            : route('cek-status.hasil', ['kode' => $request->input('kode', $kode_reservasi)]);

        // Hanya boleh dibatalkan selama masih proses verifikasi (Menunggu) dan belum lewat tanggal.
        $bolehStatus = $reservasi->status_reservasi === StatusReservasi::Menunggu;
        $belumLewat = $reservasi->tanggal_mulai->startOfDay()->gte(Carbon::today());

        if (! $bolehStatus || ! $belumLewat) {
            return redirect($kembaliKe)->with('error', 'Reservasi hanya dapat dibatalkan selama masih menunggu verifikasi dan belum melewati tanggal mulai.');
        }

        // Observer otomatis mencatat Riwayat_Status (id_admin null = dibatalkan pemesan).
        $reservasi->keteranganRiwayat = 'Dibatalkan oleh pemesan';
        $reservasi->status_reservasi = StatusReservasi::Dibatalkan;
        $reservasi->lock_status = LockStatus::Released;
        $reservasi->save();

        return redirect($kembaliKe)->with('success', "Reservasi {$reservasi->kode_reservasi} berhasil dibatalkan.");
    }

    // ------------------------------------------------------------------
    // Helper privat
    // ------------------------------------------------------------------

    private function pastikanKategoriAda(string $kategori): void
    {
        $ada = Fasilitas::where('status_aktif', StatusAktif::Aktif->value)
            ->where('kategori_fasilitas', $kategori)
            ->exists();
        abort_unless($ada, 404, 'Kategori fasilitas tidak ditemukan.');
    }

    /** Scope query fasilitas aktif untuk kategori (dan opsional punya tarif jenis tertentu). */
    private function fasilitasScope($query, string $kategori, ?int $idJenis)
    {
        return $query
            ->where('status_aktif', StatusAktif::Aktif->value)
            ->where('kategori_fasilitas', $kategori)
            ->when($idJenis, fn ($q) => $q->whereHas('tarifSewa', fn ($t) => $t
                ->where('status_aktif', StatusAktif::Aktif->value)
                ->where('id_jenis_sewa', $idJenis)));
    }

    private function jenisDipilih(Request $request): ?JenisSewa
    {
        $id = $request->integer('jenis');

        return $id ? JenisSewa::find($id) : null;
    }

    /** Bangun slot untuk pengecekan warna dari query, dengan default aman agar warna selalu tampil. */
    private function slotDariRequest(Request $request, ?SatuanSewa $satuan): array
    {
        if ($satuan === SatuanSewa::Jam) {
            $tanggal = $this->tanggalValid($request->input('tanggal_mulai'));

            // Per Jam selalu dinilai terhadap SELURUH jam operasional (08.00–16.00) hari itu.
            return [
                'tanggal_mulai'   => $tanggal,
                'tanggal_selesai' => $tanggal,
                'jam_mulai'       => '08:00',
                'jam_selesai'     => '16:00',
            ];
        }


        $mulai = $this->tanggalValid($request->input('tanggal_mulai'));
        // Sewa Bulan: jendela default = durasi minimum (mis. 3 bulan) sejak tanggal mulai.
        $minBulan = max(1, (int) JenisSewa::where('satuan', SatuanSewa::Bulan->value)->value('durasi_minimum'));
        $defaultSelesai = $satuan === SatuanSewa::Bulan
            ? Carbon::parse($mulai)->addMonthsNoOverflow($minBulan)->toDateString()
            : $mulai;
        $selesai = $this->tanggalValid($request->input('tanggal_selesai'), $defaultSelesai);
        // Rentang terbalik atau tak wajar panjangnya → kembali ke default.
        if ($selesai < $mulai || Carbon::parse($mulai)->diffInDays($selesai) > 366 * 5) {
            $selesai = $defaultSelesai;
        }

        return [
            'tanggal_mulai'   => $mulai,
            'tanggal_selesai' => $selesai,
            'jam_mulai'       => null,
            'jam_selesai'     => null,
        ];
    }

    /**
     * Kode reservasi pendek & mudah dibaca tanpa tanda hubung: 2 huruf + 3 angka (mis. RS186).
     * Unik terhadap kode dasar maupun kode per ruangan (RS186A, RS186B, ...).
     */
    private function generateKode(): string
    {
        do {
            $kode = Reservasi::kodeAcak();
        } while (
            Reservasi::where('kode_transaksi', $kode)
                ->orWhere('kode_reservasi', 'like', $kode.'%')
                ->exists()
        );

        return $kode;
    }

    /** Salin daftar dokumen keranjang (CartService::dokumen(), sudah tersimpan di disk publik) ke baris Reservasi ini. */
    private function simpanDokumen(Reservasi $reservasi): void
    {
        foreach ($this->cart->dokumen() as $dok) {
            DokumenPersyaratan::create([
                'id_reservasi'      => $reservasi->id_reservasi,
                'jenis_dokumen'     => 'Persyaratan Sewa Bulanan',
                'nama_file'         => $dok['nama'],
                'lokasi_file'       => $dok['path'],
                'tanggal_upload'    => now(),
                'status_verifikasi' => StatusVerifikasi::Menunggu,
            ]);
        }
    }

    /**
     * Perbarui daftar dokumen persyaratan Bulan di keranjang (CartService::dokumen()) dari
     * form "Isi Jadwal": simpan berkas baru (dokumen[]) ke disk, buang dokumen lama yang
     * TIDAK ada lagi di dokumen_pertahankan[] (dihapus pemesan lewat tombol "x" di form).
     */
    private function prosesDokumen(Request $request): void
    {
        $dipertahankan = array_map('strval', $request->input('dokumen_pertahankan', []));

        $daftar = [];
        foreach ($this->cart->dokumen() as $dok) {
            if (in_array($dok['path'], $dipertahankan, true)) {
                $daftar[] = $dok;
            } else {
                Storage::disk('public')->delete($dok['path']);
            }
        }

        $filesBaru = $request->file('dokumen', []);
        $filesBaru = is_array($filesBaru) ? $filesBaru : [$filesBaru];
        foreach (array_filter($filesBaru) as $file) {
            $daftar[] = [
                'path' => $file->store('dokumen', 'public'),
                'nama' => $file->getClientOriginalName(),
            ];
        }

        $this->cart->simpanDokumen($daftar);
    }
}
