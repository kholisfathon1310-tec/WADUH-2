<?php

namespace Tests\Feature;

use App\Models\Pemesan;
use App\Models\Reservasi;
use App\Models\TarifSewa;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\BantuPemesan;
use Tests\TestCase;

/**
 * Uji alur Pemesan Stage 2 memakai data hasil seeder (DatabaseTransactions → rollback,
 * data seed tetap utuh). Jalankan setelah `php artisan migrate:fresh --seed`.
 */
class AlurPemesanTest extends TestCase
{
    use BantuPemesan, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginPemesan();
    }

    /**
     * Buat fasilitas + tarif KHUSUS test (bukan ambil data seeded) supaya tidak
     * bentrok dengan reservasi asli yang mungkin sudah dibuat pengguna.
     * DatabaseTransactions me-rollback semuanya setelah test.
     */
    private function tarifSatuan(string $satuan): TarifSewa
    {
        $jenis = \App\Models\JenisSewa::where('satuan', $satuan)->firstOrFail();
        $fasilitas = \App\Models\Fasilitas::factory()->create(['status_aktif' => 'Aktif', 'kapasitas' => 20]);

        return TarifSewa::factory()
            ->create([
                'id_fasilitas'  => $fasilitas->id_fasilitas,
                'id_jenis_sewa' => $jenis->id_jenis_sewa,
                'status_aktif'  => 'Aktif',
            ])
            ->load('fasilitas', 'jenisSewa');
    }

    public function test_halaman_alur_bisa_dibuka(): void
    {
        $tarif = $this->tarifSatuan('Jam');
        $kategori = $tarif->fasilitas->kategori_fasilitas;

        $this->get('/reservasi')->assertOk();
        $this->get('/reservasi/'.rawurlencode($kategori).'/jenis-sewa')->assertOk();
        $this->get('/reservasi/'.rawurlencode($kategori).'/lantai?jenis='.$tarif->id_jenis_sewa)->assertOk();
        $this->get('/reservasi/'.rawurlencode($kategori).'/denah/'.$tarif->fasilitas->id_lantai.'?jenis='.$tarif->id_jenis_sewa)->assertOk();
        $this->get('/reservasi/fasilitas/'.$tarif->fasilitas->id_fasilitas.'?jenis='.$tarif->id_jenis_sewa)->assertOk();
        $this->get('/cek-status')->assertOk();
    }

    public function test_jam_terisi_dikembalikan_untuk_jam_yang_sudah_dipesan(): void
    {
        $tarif = $this->tarifSatuan('Jam');
        $fas = $tarif->fasilitas;
        $tgl = $this->hariKerja(20);

        Reservasi::create([
            'id_pemesan' => Pemesan::factory()->create()->id_pemesan, 'id_tarif_sewa' => $tarif->id_tarif_sewa,
            'id_admin' => null, 'kode_reservasi' => 'RSV-JTERISI', 'kode_transaksi' => 'TRX-JTERISI',
            'tanggal_mulai' => $tgl, 'tanggal_selesai' => $tgl, 'jam_mulai' => '10:00', 'jam_selesai' => '11:00',
            'durasi' => 1, 'jumlah_pengguna' => 2, 'keperluan' => 'Uji jam terisi', 'harga_satuan' => 100000,
            'total_biaya' => 100000, 'status_reservasi' => 'Menunggu', 'lock_status' => 'pending_approval',
        ]);

        // Endpoint AJAX dipanggil jam-picker tiap tanggal diganti.
        $res = $this->getJson(route('reservasi.fasilitas.jam-terisi', $fas->id_fasilitas).'?tanggal='.$tgl);
        $res->assertOk()->assertJson([['mulai' => '10:00', 'selesai' => '11:00']]);

        // Halaman detail fasilitas juga membawa data terisi AWAL (tanpa perlu AJAX dulu).
        $this->get('/reservasi/fasilitas/'.$fas->id_fasilitas.'?jenis='.$tarif->id_jenis_sewa.'&tanggal_mulai='.$tgl)
            ->assertOk()
            ->assertSee('data-terisi-awal="[{&quot;mulai&quot;:&quot;10:00&quot;,&quot;selesai&quot;:&quot;11:00&quot;}]"', false);

        // Tanggal lain (belum ada reservasi) → tidak ada jam terisi.
        $tglLain = Carbon::parse($tgl)->addDay()->toDateString();
        $this->getJson(route('reservasi.fasilitas.jam-terisi', $fas->id_fasilitas).'?tanggal='.$tglLain)
            ->assertOk()->assertExactJson([]);
    }

    public function test_tambah_keranjang_dan_hold_mengunci_session_lain(): void
    {
        $tarif = $this->tarifSatuan('Jam');
        $fas = $tarif->fasilitas;
        $tgl = $this->hariKerja(5);

        $res = $this->post('/reservasi/keranjang', [
            'id_fasilitas'    => $fas->id_fasilitas,
            'id_tarif_sewa'   => $tarif->id_tarif_sewa,
            'tanggal_mulai'   => $tgl,
            'jam_mulai'       => '09:00',
            'jam_selesai'     => '11:00',
            'jumlah_pengguna' => 2,
            'keperluan'       => 'Rapat',
        ]);

        $res->assertRedirect(route('reservasi.checkout.form'));
        $this->assertCount(1, session('reservasi_cart'));

        // Hold aktif → session lain terkunci, session pemilik tetap bebas.
        $svc = app(AvailabilityService::class);
        $slot = ['tanggal_mulai' => $tgl, 'tanggal_selesai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00'];
        $this->assertFalse($svc->slotAvailable($fas->id_fasilitas, $slot, 'session-lain'), 'Slot harus terkunci untuk session lain');
        $this->assertTrue($svc->slotAvailable($fas->id_fasilitas, $slot, session()->getId()), 'Slot harus bebas untuk pemilik hold');
    }

    public function test_checkout_membuat_pemesan_dan_reservasi(): void
    {
        $tarif = $this->tarifSatuan('Jam');
        $item = app(\App\Services\CartService::class)->buildItem($tarif, [
            'tanggal_mulai'   => $this->hariKerja(6),
            'jam_mulai'       => '13:00',
            'jam_selesai'     => '15:00',
            'jumlah_pengguna' => 3,
            'keperluan'       => 'Workshop',
        ]);

        $before = Reservasi::count();

        // Data diri tidak lagi diisi saat checkout — reservasi dibuat atas nama pemesan yang login.
        $res = $this->withSession(['reservasi_cart' => [$item]])->post('/reservasi/checkout');

        $res->assertRedirect(route('customer.reservasi-saya.index'));
        $res->assertSessionHas('checkout');
        $this->assertSame($before + 1, Reservasi::count());

        $baru = Reservasi::latest('id_reservasi')->first();
        $this->assertSame($this->pemesan->id_pemesan, $baru->id_pemesan);
        $this->assertSame('Menunggu', $baru->status_reservasi->value);
        $this->assertSame('pending_approval', $baru->lock_status->value);
        $this->assertNull($baru->id_admin);
        // Kode simpel: RSV-XXXX, dan untuk checkout 1 item kode reservasi = kode dasar.
        $this->assertMatchesRegularExpression('/^RSV-[A-Z2-9]{4}$/', $baru->kode_transaksi);
        $this->assertSame($baru->kode_transaksi, $baru->kode_reservasi);
        $this->assertEmpty(session('reservasi_cart', []), 'Keranjang harus kosong setelah checkout');
    }

    public function test_pembatalan_oleh_pemesan_tercatat_di_riwayat_tanpa_admin(): void
    {
        $pemesan = Pemesan::factory()->create();
        $tarif = $this->tarifSatuan('Hari');
        $reservasi = Reservasi::create([
            'id_pemesan'       => $pemesan->id_pemesan,
            'id_tarif_sewa'    => $tarif->id_tarif_sewa,
            'id_admin'         => null,
            'kode_reservasi'   => 'RSV-TESTCANCEL',
            'kode_transaksi'   => 'TRX-TESTCANCEL',
            'tanggal_mulai'    => $this->hariKerja(10),
            'tanggal_selesai'  => $this->hariKerja(11),
            'durasi'           => 2,
            'jumlah_pengguna'  => 5,
            'keperluan'        => 'Acara',
            'harga_satuan'     => 1000,
            'total_biaya'      => 2000,
            'status_reservasi' => 'Menunggu',
            'lock_status'      => 'pending_approval',
        ]);

        $this->post('/reservasi/RSV-TESTCANCEL/batalkan')->assertRedirect();

        $reservasi->refresh();
        $this->assertSame('Dibatalkan', $reservasi->status_reservasi->value);

        $riwayat = $reservasi->riwayatStatus()->latest('id_riwayat')->first();
        $this->assertNotNull($riwayat, 'Perubahan status harus tercatat di Riwayat_Status');
        $this->assertNull($riwayat->id_admin, 'Pembatalan pemesan tidak punya admin');
        $this->assertSame('Dibatalkan oleh pemesan', $riwayat->keterangan);
        $this->assertNull($reservasi->tanggal_diproses, 'tanggal_diproses hanya diisi saat admin memproses');
    }

    public function test_multi_pilih_denah_satu_form_untuk_semua_ruangan(): void
    {
        // Pilih 3 ruangan di denah → isi SATU form jadwal → ketiganya masuk keranjang sekaligus.
        $jenis = \App\Models\JenisSewa::where('satuan', 'Jam')->firstOrFail();
        $tarifs = collect(range(1, 3))->map(function () use ($jenis) {
            $f = \App\Models\Fasilitas::factory()->create(['status_aktif' => 'Aktif', 'kapasitas' => 20]);

            return TarifSewa::factory()->create([
                'id_fasilitas' => $f->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa, 'status_aktif' => 'Aktif',
            ]);
        });

        $tgl = $this->hariKerja(4);
        $ids = $tarifs->map(fn ($t) => $t->id_fasilitas);

        $this->post('/reservasi/keranjang', [
            'id_fasilitas'    => $ids[0],
            'id_tarif_sewa'   => $tarifs[0]->id_tarif_sewa,
            'antrian'         => $ids[1].','.$ids[2],
            'tanggal_mulai'   => $tgl,
            'jam_mulai'       => '09:00',
            'jam_selesai'     => '11:00',
            'jumlah_pengguna' => 2,
            'keperluan'       => 'Rapat',
        ])->assertRedirect(route('reservasi.checkout.form'));

        $cart = session('reservasi_cart');
        $this->assertCount(3, $cart);
        $this->assertEqualsCanonicalizing($ids->all(), array_column($cart, 'id_fasilitas'));
        // Jadwal sama untuk semua ruangan.
        $this->assertSame([$tgl, $tgl, $tgl], array_column($cart, 'tanggal_mulai'));
    }

    public function test_convention_hall_harian_otomatis_satu_hari(): void
    {
        // CH hanya sewa harian 1 hari (8 jam): tanpa input tanggal_selesai pun harus lolos,
        // tanggal_selesai otomatis = tanggal_mulai dan durasi 1 hari.
        $jenis = \App\Models\JenisSewa::where('satuan', 'Hari')->firstOrFail();
        $fasilitas = \App\Models\Fasilitas::factory()->create([
            'kategori_fasilitas' => 'Convention Hall',
            'status_aktif'       => 'Aktif',
            'kapasitas'          => 75, // eksplisit — factory acak bisa < jumlah_pengguna
        ]);
        $tarif = TarifSewa::factory()->create([
            'id_fasilitas'  => $fasilitas->id_fasilitas,
            'id_jenis_sewa' => $jenis->id_jenis_sewa,
            'status_aktif'  => 'Aktif',
        ]);

        $tgl = $this->hariKerja(9);
        $this->post('/reservasi/keranjang', [
            'id_fasilitas'    => $fasilitas->id_fasilitas,
            'id_tarif_sewa'   => $tarif->id_tarif_sewa,
            'tanggal_mulai'   => $tgl,
            // sengaja TANPA tanggal_selesai
            'jumlah_pengguna' => 50,
            'keperluan'       => 'Seminar',
        ])->assertRedirect(route('reservasi.checkout.form'));

        $item = session('reservasi_cart')[0];
        $this->assertSame($tgl, $item['tanggal_selesai']);
        $this->assertSame(1, $item['durasi']);
    }

    public function test_checkout_bulan_wajib_dokumen(): void
    {
        $tarif = $this->tarifSatuan('Bulan');
        $item = app(\App\Services\CartService::class)->buildItem($tarif, [
            'tanggal_mulai'   => $this->hariKerja(7),
            'tanggal_selesai' => Carbon::parse($this->hariKerja(7))->addMonths(3)->toDateString(),
            'jumlah_pengguna' => 10,
            'keperluan'       => 'Kantor sementara',
        ]);

        // Tanpa dokumen persyaratan di keranjang → harus gagal validasi.
        $this->withSession(['reservasi_cart' => [$item]])
            ->post('/reservasi/checkout')
            ->assertSessionHasErrors('dokumen');
    }

    public function test_tambah_keranjang_tolak_jam_mulai_yang_sudah_lewat_hari_ini(): void
    {
        Carbon::setTestNow(Carbon::parse($this->hariKerja(0))->setTime(14, 0)); // Bekukan waktu: hari ini jam 14.00.

        try {
            $tarif = $this->tarifSatuan('Jam');

            // Jam mulai 10.00 sudah lewat (sekarang 14.00) → ditolak, sekalipun tanggalnya hari ini.
            $lewat = $this->post('/reservasi/keranjang', [
                'id_fasilitas'    => $tarif->fasilitas->id_fasilitas,
                'id_tarif_sewa'   => $tarif->id_tarif_sewa,
                'tanggal_mulai'   => Carbon::today()->toDateString(),
                'jam_mulai'       => '10:00',
                'jam_selesai'     => '12:00',
                'jumlah_pengguna' => 2,
                'keperluan'       => 'Rapat',
            ]);
            $lewat->assertSessionHasErrors('jam_mulai');

            // Jam mulai 15.00 (akan datang, sesudah 14.00) tetap boleh.
            $akanDatang = $this->post('/reservasi/keranjang', [
                'id_fasilitas'    => $tarif->fasilitas->id_fasilitas,
                'id_tarif_sewa'   => $tarif->id_tarif_sewa,
                'tanggal_mulai'   => Carbon::today()->toDateString(),
                'jam_mulai'       => '15:00',
                'jam_selesai'     => '16:00',
                'jumlah_pengguna' => 2,
                'keperluan'       => 'Rapat',
            ]);
            $akanDatang->assertRedirect(route('reservasi.checkout.form'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_tambah_keranjang_tolak_tanggal_hari_ini_kalau_gedung_sudah_tutup(): void
    {
        Carbon::setTestNow(Carbon::parse($this->hariKerja(0))->setTime(17, 0)); // Bekukan waktu: hari ini jam 17.00, gedung sudah tutup.

        try {
            $tarif = $this->tarifSatuan('Hari');

            $res = $this->post('/reservasi/keranjang', [
                'id_fasilitas'    => $tarif->fasilitas->id_fasilitas,
                'id_tarif_sewa'   => $tarif->id_tarif_sewa,
                'tanggal_mulai'   => Carbon::today()->toDateString(),
                'tanggal_selesai' => Carbon::today()->toDateString(),
                'jumlah_pengguna' => 2,
                'keperluan'       => 'Rapat',
            ]);
            $res->assertSessionHasErrors('tanggal_mulai');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_tambah_keranjang_tolak_ruangan_dan_jadwal_yang_sama_dua_kali(): void
    {
        $tarif = $this->tarifSatuan('Jam');
        $fas = $tarif->fasilitas;
        $tgl = $this->hariKerja(8);

        $payload = [
            'id_fasilitas'    => $fas->id_fasilitas,
            'id_tarif_sewa'   => $tarif->id_tarif_sewa,
            'tanggal_mulai'   => $tgl,
            'jam_mulai'       => '09:00',
            'jam_selesai'     => '11:00',
            'jumlah_pengguna' => 2,
            'keperluan'       => 'Rapat',
        ];

        $this->post('/reservasi/keranjang', $payload)->assertRedirect(route('reservasi.checkout.form'));
        $this->assertCount(1, session('reservasi_cart'));

        // Submit persis sama lagi (mis. double-click / resubmit) → ditolak, keranjang tetap 1 item.
        $this->post('/reservasi/keranjang', $payload)->assertSessionHasErrors('jadwal');
        $this->assertCount(1, session('reservasi_cart'), 'Ruangan+jadwal yang sama tidak boleh masuk keranjang dua kali');
    }

    public function test_tambah_keranjang_tolak_ruangan_sama_jam_mulai_sama_walau_jam_selesai_beda(): void
    {
        // Ruangan sama, jam_mulai SAMA (09:00) tapi jam_selesai beda (11:00 vs 10:00) — tetap
        // bentrok (overlap 09:00-10:00), bukan cuma dicek identik persis.
        $tarif = $this->tarifSatuan('Jam');
        $fas = $tarif->fasilitas;
        $tgl = $this->hariKerja(8);

        $this->post('/reservasi/keranjang', [
            'id_fasilitas' => $fas->id_fasilitas, 'id_tarif_sewa' => $tarif->id_tarif_sewa,
            'tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00',
            'jumlah_pengguna' => 2, 'keperluan' => 'Rapat',
        ])->assertRedirect(route('reservasi.checkout.form'));
        $this->assertCount(1, session('reservasi_cart'));

        $this->post('/reservasi/keranjang', [
            'id_fasilitas' => $fas->id_fasilitas, 'id_tarif_sewa' => $tarif->id_tarif_sewa,
            'tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '10:00',
            'jumlah_pengguna' => 2, 'keperluan' => 'Rapat lain',
        ])->assertSessionHasErrors('jadwal');

        $this->assertCount(1, session('reservasi_cart'), 'jam_mulai yang sama pada ruangan yang sama harus tetap dianggap bentrok');
    }

    public function test_tambah_keranjang_boleh_ruangan_sama_jadwal_berbeda(): void
    {
        $tarif = $this->tarifSatuan('Jam');
        $fas = $tarif->fasilitas;
        $tgl = $this->hariKerja(8);

        $this->post('/reservasi/keranjang', [
            'id_fasilitas' => $fas->id_fasilitas, 'id_tarif_sewa' => $tarif->id_tarif_sewa,
            'tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00',
            'jumlah_pengguna' => 2, 'keperluan' => 'Rapat pagi',
        ])->assertRedirect(route('reservasi.checkout.form'));

        // Ruangan SAMA, jam BERBEDA (tidak bentrok) → harus boleh masuk keranjang.
        $this->post('/reservasi/keranjang', [
            'id_fasilitas' => $fas->id_fasilitas, 'id_tarif_sewa' => $tarif->id_tarif_sewa,
            'tanggal_mulai' => $tgl, 'jam_mulai' => '13:00', 'jam_selesai' => '15:00',
            'jumlah_pengguna' => 2, 'keperluan' => 'Rapat siang',
        ])->assertRedirect(route('reservasi.checkout.form'));

        $this->assertCount(2, session('reservasi_cart'));
    }

    public function test_tambah_keranjang_boleh_ruangan_berbeda_jadwal_sama(): void
    {
        $jenis = \App\Models\JenisSewa::where('satuan', 'Jam')->firstOrFail();
        $tarifA = $this->tarifSatuan('Jam');
        $fasB = \App\Models\Fasilitas::factory()->create(['status_aktif' => 'Aktif', 'kapasitas' => 20]);
        $tarifB = TarifSewa::factory()->create([
            'id_fasilitas' => $fasB->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa, 'status_aktif' => 'Aktif',
        ]);
        $tgl = $this->hariKerja(8);

        $this->post('/reservasi/keranjang', [
            'id_fasilitas' => $tarifA->fasilitas->id_fasilitas, 'id_tarif_sewa' => $tarifA->id_tarif_sewa,
            'tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00',
            'jumlah_pengguna' => 2, 'keperluan' => 'Rapat A',
        ])->assertRedirect(route('reservasi.checkout.form'));

        // Ruangan BERBEDA, jadwal SAMA → harus boleh masuk keranjang.
        $this->post('/reservasi/keranjang', [
            'id_fasilitas' => $fasB->id_fasilitas, 'id_tarif_sewa' => $tarifB->id_tarif_sewa,
            'tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00',
            'jumlah_pengguna' => 2, 'keperluan' => 'Rapat B',
        ])->assertRedirect(route('reservasi.checkout.form'));

        $this->assertCount(2, session('reservasi_cart'));
    }

    public function test_tambah_keranjang_dikunci_per_session_agar_tidak_race_klik_ganda(): void
    {
        // Regresi bug nyata: dua request tambahKeranjang yang HAMPIR BERSAMAAN untuk session
        // yang sama (mis. klik ganda / beberapa tab) bisa lolos bersamaan lewat pengecekan
        // bentrok kalau baca-cek-tulis keranjangnya tidak dikunci — keduanya sama-sama membaca
        // keranjang SEBELUM salah satu sempat menyimpan, jadi jadwal yang sebenarnya tumpang
        // tindih bisa sama-sama masuk. Disimulasikan di sini dengan menahan lock milik session
        // ini secara manual SEBELUM request masuk: request harus menunggu, lalu gagal dengan
        // pesan ramah (bukan 500 / lolos begitu saja) begitu batas block() habis.
        $tarif = $this->tarifSatuan('Jam');
        $fas = $tarif->fasilitas;
        $tgl = $this->hariKerja(8);

        // Request pertama seperti biasa (tanpa lock ditahan) — sekaligus dipakai untuk
        // mendapatkan session ID NYATA yang dipakai controller (pola yang sama seperti
        // test_tambah_keranjang_dan_hold_mengunci_session_lain di atas).
        $res1 = $this->post('/reservasi/keranjang', [
            'id_fasilitas' => $fas->id_fasilitas, 'id_tarif_sewa' => $tarif->id_tarif_sewa,
            'tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '10:00',
            'jumlah_pengguna' => 2, 'keperluan' => 'Rapat pertama',
        ]);
        $res1->assertRedirect(route('reservasi.checkout.form'));
        $this->assertCount(1, session('reservasi_cart'));
        $sessionId = session()->getId();

        // Simulasi HTTP test Laravel tidak otomatis mengirim ulang cookie session di antara
        // panggilan $this->post() terpisah (beda dengan browser sungguhan) — set eksplisit
        // supaya request kedua di bawah benar-benar dianggap session yang SAMA oleh
        // StartSession, sama seperti dua tab/klik ganda dari browser yang sama.
        $this->withCookie(config('session.cookie'), $sessionId);

        // Tahan lock milik session yang sama secara manual → simulasikan request lain yang
        // sedang memproses tambahKeranjang untuk session ini di saat yang bersamaan.
        $lock = Cache::lock("keranjang-lock:{$sessionId}", 10);
        $this->assertTrue($lock->get(), 'Gagal menahan lock untuk simulasi request bersamaan');

        try {
            // Jadwal BERBEDA & TIDAK bentrok dengan item pertama — andai lock tidak ada,
            // request ini akan lolos normal. Dengan lock, ia harus menunggu lalu gagal
            // dengan pesan ramah begitu batas block() habis, bukan lolos begitu saja.
            $res2 = $this->post('/reservasi/keranjang', [
                'id_fasilitas' => $fas->id_fasilitas, 'id_tarif_sewa' => $tarif->id_tarif_sewa,
                'tanggal_mulai' => $tgl, 'jam_mulai' => '13:00', 'jam_selesai' => '14:00',
                'jumlah_pengguna' => 2, 'keperluan' => 'Rapat kedua (bersamaan)',
            ]);

            $res2->assertSessionHasErrors();
            $this->assertCount(1, session('reservasi_cart'), 'Item kedua tidak boleh masuk selama lock masih ditahan pihak lain');
        } finally {
            $lock->release();
        }
    }
}
