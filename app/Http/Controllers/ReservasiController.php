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
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        $jenisId = $request->integer('jenis');
        $tarif = ($jenisId ? $semuaTarif->firstWhere('id_jenis_sewa', $jenisId) : null) ?? $semuaTarif->first();

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

        // Rentang jam yang sudah terisi pada tanggal yang sedang ditampilkan (Per Jam saja) —
        // dipakai jam-picker supaya jam yang bentrok tidak bisa diklik & langsung kelihatan
        // terisi sampai jam berapa. Selanjutnya di-refresh via AJAX (lihat jamTerisi()) tiap
        // pemesan mengganti tanggal di form, tanpa reload halaman.
        $jamTerisi = [];
        if ($tarif->jenisSewa->satuan === SatuanSewa::Jam) {
            $tanggalAwal = $request->input('tanggal_mulai', $editItem['tanggal_mulai'] ?? Carbon::today()->toDateString());
            $jamTerisi = $this->hitungJamTerisi($fasilitas->id_fasilitas, $tanggalAwal);
        }

        return view('reservasi.fasilitas', [
            'fasilitas'  => $fasilitas->load('lantai'),
            'tarif'      => $tarif,
            'jenis'      => $tarif->jenisSewa,
            'semuaTarif' => $semuaTarif,
            'jamTerisi'  => $jamTerisi,
            'pemesan'    => Auth::guard('customer')->user(),
            'editItem'   => $editItem,
            'editIndex'  => $editIndex,
        ]);
    }

    /**
     * AJAX: rentang jam yang sudah terisi (Menunggu/Disetujui) untuk 1 fasilitas pada 1
     * tanggal — dipanggil jam-picker (pilih-jam.blade.php) tiap tanggal di form diganti,
     * supaya daftar jam yang bisa diklik selalu sesuai tanggal yang sedang dipilih.
     */
    public function jamTerisi(Request $request, Fasilitas $fasilitas): JsonResponse
    {
        $tanggal = $request->input('tanggal');

        return response()->json($tanggal ? $this->hitungJamTerisi($fasilitas->id_fasilitas, $tanggal) : []);
    }

    /**
     * Rentang jam terisi (Menunggu/Disetujui) fasilitas pada tanggal tsb. Reservasi
     * Harian/Bulanan (jam_mulai kosong) mengunci SELURUH jam operasional hari itu.
     *
     * @return array<int, array{mulai: string, selesai: string}>
     */
    private function hitungJamTerisi(int $fasilitasId, string $tanggal): array
    {
        return Reservasi::query()
            ->whereHas('tarifSewa', fn ($q) => $q->where('id_fasilitas', $fasilitasId))
            ->whereIn('status_reservasi', AvailabilityService::statusAktif())
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->get(['jam_mulai', 'jam_selesai'])
            ->map(fn (Reservasi $r) => [
                'mulai'   => $r->jam_mulai ? Str::substr($r->jam_mulai, 0, 5) : '00:00',
                'selesai' => $r->jam_selesai ? Str::substr($r->jam_selesai, 0, 5) : '24:00',
            ])
            ->values()
            ->all();
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
                            'edit_index' => 'Tidak bisa mengedit jadwal sambil menambah ruangan baru dari denah. Batalkan pilihan ruangan lain terlebih dahulu.',
                        ]);
                    }

                    $existing = $this->cart->get($editIndex);
                    if (! $existing || (int) $existing['id_fasilitas'] !== (int) $tarifUtama->id_fasilitas) {
                        return back()->withInput()->withErrors([
                            'edit_index' => 'Item yang ingin diubah tidak ditemukan, silakan coba lagi dari Keranjang.',
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
                            'antrian' => 'Salah satu ruangan yang dipilih sudah tidak tersedia untuk jenis sewa ini. Silakan pilih ulang di denah.',
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

                    if ((int) $data['jumlah_pengguna'] > $t->fasilitas->kapasitas) {
                        $galat[] = "{$t->fasilitas->nama_fasilitas}: kapasitas maksimal {$t->fasilitas->kapasitas} orang.";
                    } elseif ($this->cart->hasConflict($item, $this->availability, $editIndex) || $this->bentrokDiBatch($items, $item)) {
                        $galat[] = "{$t->fasilitas->nama_fasilitas}: ruangan ini sudah ada di keranjang dengan jadwal yang bentrok.";
                    } elseif (! $this->availability->slotAvailable($item['id_fasilitas'], $slot, $sessionId)) {
                        $galat[] = "{$t->fasilitas->nama_fasilitas}: jadwal tersebut baru saja terisi, pilih jadwal lain.";
                    } else {
                        $items[] = $item;
                    }
                }

                if ($galat !== []) {
                    return back()->withInput()->withErrors($galat);
                }

                if ($editIndex !== null) {
                    $oldItem = $this->cart->get($editIndex);
                    $this->availability->releaseHold($oldItem);
                    $this->availability->putHold($items[0], $sessionId);
                    $this->cart->replace($editIndex, $items[0]);

                    $request->session()->save();

                    return redirect()->route('reservasi.checkout.form')->with('success', "Jadwal \"{$items[0]['nama_fasilitas']}\" berhasil diperbarui.");
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
                    ? "{$n} ruangan ditambahkan ke keranjang dengan jadwal yang sama. 🎉"
                    : "\"{$items[0]['nama_fasilitas']}\" ditambahkan ke keranjang.");
            });
        } catch (LockTimeoutException) {
            return back()->withInput()->withErrors([
                'antrian' => 'Keranjang Anda sedang diproses permintaan lain, coba lagi sesaat lagi.',
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

        // Kalau ini item terakhir, back() akan mendarat di halaman checkout yang langsung
        // redirect lagi (keranjang kosong) dan menimpa pesan sukses ini — jadi arahkan
        // langsung ke katalog Fasilitas supaya pesan "item dihapus" benar-benar terlihat.
        if ($this->cart->isEmpty()) {
            return redirect()->route('reservasi.index')->with('success', 'Item dihapus, keranjang Anda sekarang kosong.');
        }

        return back()->with('success', 'Item dihapus dari keranjang.');
    }

    /** Kosongkan seluruh keranjang + lepas semua cache hold. */
    public function kosongkanKeranjang(): RedirectResponse
    {
        foreach ($this->cart->items() as $item) {
            $this->availability->releaseHold($item);
        }
        $this->cart->clear();

        return redirect()->route('reservasi.checkout.form')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    /** Halaman Keranjang: ringkasan keranjang + form data diri. Selalu tampil, termasuk saat kosong. */
    public function checkoutForm(): View
    {
        return view('reservasi.checkout', [
            'items'    => $this->cart->items(),
            'total'    => $this->cart->total(),
            'hasBulan' => $this->cart->hasBulan(),
            'pemesan'  => Auth::guard('customer')->user(),
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
            return redirect()->route('reservasi.index')->with('error', 'Keranjang masih kosong.');
        }

        $items = $this->cart->items();
        $pemesan = Auth::guard('customer')->user();

        $hasil = DB::transaction(function () use ($request, $items, $pemesan) {
            // 1. SATU kode simpel per checkout (mis. RSV-7K3M) untuk seluruh ruangan.
            //    Baris ke-2 dst diberi suffix internal (-2, ...) karena kode_reservasi UNIQUE per baris,
            //    tapi kode yang ditampilkan ke pemesan tetap satu: kode dasar (= kode_transaksi).
            $kodeDasar = $this->generateKode();
            $multi = count($items) > 1;
            $kodeReservasi = [];

            foreach ($items as $index => $item) {
                $reservasi = Reservasi::create([
                    'id_pemesan'       => $pemesan->id_pemesan,
                    'id_tarif_sewa'    => $item['id_tarif_sewa'],
                    'id_admin'         => null,
                    'kode_reservasi'   => $multi ? $kodeDasar.'-'.($index + 1) : $kodeDasar,
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

                // 3. Item Bulan wajib punya dokumen persyaratan (sudah divalidasi CheckoutRequest).
                //    Satu lampiran (dokumen[]) berlaku untuk SEMUA ruangan Bulan pada transaksi ini,
                //    jadi disalin ke tiap baris Reservasi Bulan (skema id_reservasi tetap per-baris).
                if ($item['satuan'] === SatuanSewa::Bulan->value) {
                    $this->simpanDokumen($request, $reservasi);
                }

                $kodeReservasi[] = $reservasi->kode_reservasi;
            }

            return ['kode_transaksi' => $kodeDasar, 'kode_reservasi' => $kodeReservasi];
        });

        // 4 & 5. Setelah commit: lepas semua cache hold, kosongkan keranjang.
        foreach ($items as $item) {
            $this->availability->releaseHold($item);
        }
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
    public function batalkan(Request $request, string $kode_reservasi): RedirectResponse
    {
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
            return redirect($kembaliKe)->with('error', 'Reservasi hanya dapat dibatalkan selama masih diverifikasi.');
        }

        // Observer otomatis mencatat Riwayat_Status (id_admin null = dibatalkan pemesan).
        $reservasi->keteranganRiwayat = 'Dibatalkan oleh pemesan';
        $reservasi->status_reservasi = StatusReservasi::Dibatalkan;
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
        $today = Carbon::today()->toDateString();

        if ($satuan === SatuanSewa::Jam) {
            return [
                'tanggal_mulai'   => $request->input('tanggal_mulai', $today),
                'tanggal_selesai' => $request->input('tanggal_mulai', $today),
                'jam_mulai'       => $request->input('jam_mulai', '08:00'),
                'jam_selesai'     => $request->input('jam_selesai', '16:00'), // jam operasional 08.00–16.00
            ];
        }

        $mulai = $request->input('tanggal_mulai', $today);
        $defaultSelesai = $satuan === SatuanSewa::Bulan
            ? Carbon::parse($mulai)->addMonths(3)->toDateString()
            : $mulai;

        return [
            'tanggal_mulai'   => $mulai,
            'tanggal_selesai' => $request->input('tanggal_selesai', $defaultSelesai),
            'jam_mulai'       => null,
            'jam_selesai'     => null,
        ];
    }

    /**
     * Kode reservasi pendek & mudah dibaca: RSV- + 4 karakter tanpa huruf/angka membingungkan
     * (tanpa O/0, I/L/1). Unik terhadap kode dasar maupun kode bersufiks (-1, -2, ...).
     */
    private function generateKode(): string
    {
        $charset = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $kode = 'RSV-'.substr(str_shuffle($charset), 0, 4);
        } while (
            Reservasi::where('kode_transaksi', $kode)
                ->orWhere('kode_reservasi', 'like', $kode.'%')
                ->exists()
        );

        return $kode;
    }

    private function simpanDokumen(Request $request, Reservasi $reservasi): void
    {
        $files = $request->file('dokumen', []);
        $files = is_array($files) ? $files : [$files];

        foreach (array_filter($files) as $file) {
            $path = $file->store('dokumen', 'public');
            DokumenPersyaratan::create([
                'id_reservasi'      => $reservasi->id_reservasi,
                'jenis_dokumen'     => 'Persyaratan Sewa Bulanan',
                'nama_file'         => $file->getClientOriginalName(),
                'lokasi_file'       => $path,
                'tanggal_upload'    => now(),
                'status_verifikasi' => StatusVerifikasi::Menunggu,
            ]);
        }
    }
}
