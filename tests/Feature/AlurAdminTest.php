<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\DokumenPersyaratan;
use App\Models\Faktur;
use App\Models\Laporan;
use App\Models\Pemesan;
use App\Models\Reservasi;
use App\Models\TarifSewa;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AlurAdminTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): Admin
    {
        return Admin::firstOrFail();
    }

    /** Reservasi Menunggu yang lolos semua checklist (fasilitas aktif, jauh di masa depan). */
    private function reservasiMenunggu(string $satuan = 'Hari', int $offsetDays = 200): Reservasi
    {
        $tarif = TarifSewa::where('status_aktif', 'Aktif')
            ->whereHas('jenisSewa', fn ($q) => $q->where('satuan', $satuan))
            ->whereHas('fasilitas', fn ($f) => $f->where('status_aktif', 'Aktif'))
            ->with('fasilitas')
            ->firstOrFail();

        $mulai = Carbon::today()->addDays($offsetDays);
        $isJam = $satuan === 'Jam';
        $isBulan = $satuan === 'Bulan';
        // Jam: 09.00–11.00 (2 jam); Hari: 2 hari; Bulan: 3 bulan — total = harga × durasi.
        $durasi = $isBulan ? 3 : 2;

        return Reservasi::create([
            'id_pemesan'       => Pemesan::factory()->create()->id_pemesan,
            'id_tarif_sewa'    => $tarif->id_tarif_sewa,
            'id_admin'         => null,
            'kode_reservasi'   => 'RSV-ADMTEST-'.$satuan.$offsetDays,
            'kode_transaksi'   => 'TRX-ADMTEST-'.$offsetDays,
            'tanggal_mulai'    => $mulai->toDateString(),
            'tanggal_selesai'  => $isJam ? $mulai->toDateString() : (clone $mulai)->addMonths($isBulan ? 3 : 0)->addDays($isBulan ? 0 : 1)->toDateString(),
            'jam_mulai'        => $isJam ? '09:00' : null,
            'jam_selesai'      => $isJam ? '11:00' : null,
            'durasi'           => $durasi,
            'jumlah_pengguna'  => 1,
            'keperluan'        => 'Uji admin',
            'harga_satuan'     => $tarif->harga,
            'total_biaya'      => $tarif->harga * $durasi,
            'status_reservasi' => 'Menunggu',
            'lock_status'      => 'pending_approval',
        ]);
    }

    public function test_login_page_dapat_diakses(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Panel Admin');
    }

    public function test_area_admin_redirect_ke_login_jika_belum_auth(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        $this->get('/admin/reservasi')->assertRedirect(route('admin.login'));
    }

    /**
     * Admin dengan kata sandi yang diketahui — di-set di dalam transaksi tes (di-rollback),
     * supaya tes login tidak bergantung pada kredensial yang tersimpan di database.
     */
    private function adminDenganSandi(string $sandi = 'Rahasia123'): Admin
    {
        $admin = $this->admin();
        $admin->update(['password' => $sandi]);

        return $admin;
    }

    public function test_login_dengan_kredensial_benar_berhasil(): void
    {
        $admin = $this->adminDenganSandi();

        $this->post('/admin/login', ['admin_email' => $admin->email, 'admin_password' => 'Rahasia123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(auth()->guard('admin')->check());
    }

    /** Checkbox "Ingat saya" mengirim remember=1 → sebelum kolom remember_token ditambahkan
     *  ke tabel admin, ini gagal dengan SQL error "Column not found". */
    public function test_login_dengan_ingat_saya_tidak_error(): void
    {
        $admin = $this->adminDenganSandi();

        $this->post('/admin/login', ['admin_email' => $admin->email, 'admin_password' => 'Rahasia123', 'remember' => '1'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(auth()->guard('admin')->check());
        $this->assertNotNull($this->admin()->fresh()->remember_token);
    }

    public function test_login_salah_ditolak(): void
    {
        $admin = $this->adminDenganSandi();

        $this->post('/admin/login', ['admin_email' => $admin->email, 'admin_password' => 'salah'])
            ->assertRedirect();

        $this->assertFalse(auth()->guard('admin')->check());
    }

    public function test_dashboard_monitoring_reservasi_terbuka(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->get('/admin/dashboard')->assertOk();
        $this->get('/admin/monitoring')->assertOk();
        $this->get('/admin/reservasi')->assertOk();
    }

    public function test_setujui_reservasi_mengisi_observer(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $r = $this->reservasiMenunggu('Hari', 210);

        $this->post(route('admin.reservasi.setujui', $r->kode_reservasi))->assertRedirect();

        $r->refresh();
        $this->assertSame('Disetujui', $r->status_reservasi->value);
        $this->assertSame('confirmed', $r->lock_status->value);
        $this->assertSame($admin->id_admin, $r->id_admin);
        $this->assertNotNull($r->tanggal_diproses, 'Observer harus isi tanggal_diproses saat admin memproses');
        $this->assertSame(1, $r->riwayatStatus()->where('status_baru', 'Disetujui')->count());
    }

    public function test_tolak_wajib_alasan_dan_tersimpan_di_riwayat(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $r = $this->reservasiMenunggu('Hari', 220);

        // Tanpa alasan → gagal validasi.
        $this->post(route('admin.reservasi.tolak', $r->kode_reservasi), [])->assertSessionHasErrors('alasan');

        // Dengan alasan → Ditolak + keterangan di Riwayat_Status.
        $this->post(route('admin.reservasi.tolak', $r->kode_reservasi), ['alasan' => 'Dokumen tidak lengkap'])->assertRedirect();
        $r->refresh();
        $this->assertSame('Ditolak', $r->status_reservasi->value);
        $this->assertSame('released', $r->lock_status->value);
        $this->assertSame('Dokumen tidak lengkap', $r->riwayatStatus()->latest('id_riwayat')->first()->keterangan);
    }

    public function test_cetak_faktur_membuat_baris_dan_pdf(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $r = $this->reservasiMenunggu('Hari', 230);
        $this->post(route('admin.reservasi.setujui', $r->kode_reservasi));

        $response = $this->post(route('admin.reservasi.faktur.cetak', $r->kode_reservasi));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $faktur = Faktur::where('id_reservasi', $r->id_reservasi)->first();
        $this->assertNotNull($faktur);
        $this->assertStringStartsWith('INV/', $faktur->nomor_faktur);
    }

    public function test_verifikasi_dokumen(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $r = $this->reservasiMenunggu('Bulan', 240);
        $dok = DokumenPersyaratan::create([
            'id_reservasi'      => $r->id_reservasi,
            'jenis_dokumen'     => 'Company Profile',
            'nama_file'         => 'cp.pdf',
            'lokasi_file'       => 'dokumen/cp.pdf',
            'tanggal_upload'    => now(),
            'status_verifikasi' => 'Menunggu',
        ]);

        $this->post(route('admin.reservasi.dokumen.verifikasi', $dok->id_dokumen), ['status_verifikasi' => 'Valid'])
            ->assertRedirect();
        $this->assertSame('Valid', $dok->fresh()->status_verifikasi->value);
    }

    /** Sekali diputuskan (Valid/Tidak Valid), keputusan dokumen tidak boleh diubah lagi. */
    public function test_verifikasi_dokumen_terkunci_setelah_diputuskan(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $r = $this->reservasiMenunggu('Bulan', 241);
        $dok = DokumenPersyaratan::create([
            'id_reservasi'      => $r->id_reservasi,
            'jenis_dokumen'     => 'Company Profile',
            'nama_file'         => 'cp-terkunci.pdf',
            'lokasi_file'       => 'dokumen/cp-terkunci.pdf',
            'tanggal_upload'    => now(),
            'status_verifikasi' => 'Valid',
        ]);

        $this->post(route('admin.reservasi.dokumen.verifikasi', $dok->id_dokumen), ['status_verifikasi' => 'Tidak Valid'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('Valid', $dok->fresh()->status_verifikasi->value, 'Status tidak boleh berubah setelah diputuskan.');
    }

    /**
     * Reproduksi keluhan "tombol Kembali habis setujui/tolak muncul pop-up lagi": setelah
     * setujui, GET ulang halaman detail (simulasi klik Kembali / reload) TIDAK boleh
     * menampilkan flash sukses lagi (sudah dikonsumsi sekali), dan halaman admin harus
     * mengirim header Cache-Control: no-store supaya browser tidak menyimpan halaman lama
     * di back-forward cache.
     *
     * setujui()/tolak() TETAP redirect ke halaman detail yang sama (back()) — pemesanan tetap
     * terlihat di sini setelah diproses. Duplikasi entri histori yang ditimbulkan pola ini
     * (perlu 2x klik back browser) ditangani di sisi KLIEN lewat fetch + location.replace()
     * pada form ber-atribut data-nav-replace (lihat admin/layouts/app.blade.php) — bukan bisa
     * diuji lewat request HTTP biasa di sini, jadi cukup diverifikasi atributnya ada di view.
     */
    public function test_halaman_detail_tidak_menampilkan_flash_sukses_dua_kali_setelah_setujui(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $r = $this->reservasiMenunggu('Hari', 270);

        // Selagi masih Menunggu (form Setujui/Tolak tampil): harus punya data-nav-replace
        // supaya fetch+replace di klien aktif untuk form ini.
        $this->get(route('admin.reservasi.show', $r->kode_reservasi))->assertSee('data-nav-replace', false);

        // setujui() memakai back() → redirect ke halaman detail (referer). Ikuti redirect-nya
        // supaya "request pertama" di bawah adalah render halaman detail dengan flash sukses.
        $pertama = $this->followingRedirects()->from(route('admin.reservasi.show', $r->kode_reservasi))
            ->post(route('admin.reservasi.setujui', $r->kode_reservasi));
        $pertama->assertOk()->assertSee('disetujui', false);
        $ccPertama = $pertama->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', (string) $ccPertama, "Header aktual: {$ccPertama}");

        // Request kedua (simulasi Kembali/reload): flash sudah dikonsumsi, tidak boleh muncul lagi.
        $kedua = $this->get(route('admin.reservasi.show', $r->kode_reservasi));
        $kedua->assertOk();
        $kedua->assertSessionMissing('success');
        $ccKedua = $kedua->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', (string) $ccKedua, "Header aktual: {$ccKedua}");
    }

    public function test_halaman_detail_monitoring_laporan_render(): void
    {
        $this->actingAs($this->admin(), 'admin');

        // Detail reservasi (checklist).
        $dummy = $this->reservasiMenunggu('Hari', 210);
        $this->get(route('admin.reservasi.show', $dummy->kode_reservasi))->assertOk()->assertSee('Checklist');

        // Monitoring detail satu fasilitas.
        $fasilitasId = TarifSewa::firstOrFail()->id_fasilitas;
        $this->get(route('admin.monitoring.detail', $fasilitasId))->assertOk();

        // Laporan index.
        $this->get(route('admin.laporan'))->assertOk();
    }

    public function test_faktur_tunggal_untuk_transaksi_multi_ruangan(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        // Dua reservasi disetujui dalam satu kode_transaksi (satu kode reservasi utk pemesan).
        $a = $this->reservasiMenunggu('Hari', 260);
        $b = $this->reservasiMenunggu('Jam', 265);
        $b->kode_transaksi = $a->kode_transaksi;
        $b->save();
        $this->post(route('admin.reservasi.setujui', $a->kode_reservasi));
        $this->post(route('admin.reservasi.setujui', $b->kode_reservasi));

        // Cetak dari baris mana pun → SATU PDF berisi kedua ruangan, satu baris Faktur.
        $response = $this->post(route('admin.reservasi.faktur.cetak', $a->kode_reservasi));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        // Klik lagi dari baris lain → idempotent: tetap satu Faktur, PDF sama.
        $response2 = $this->post(route('admin.reservasi.faktur.cetak', $b->kode_reservasi));
        $response2->assertOk();
        $this->assertStringStartsWith('%PDF', $response2->getContent());

        $this->assertSame(1, Faktur::whereIn('id_reservasi', [$a->id_reservasi, $b->id_reservasi])->count());
    }

    public function test_ekspor_laporan_pdf_menyimpan_baris_laporan(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $before = Laporan::count();
        $response = $this->get(route('admin.laporan.pdf', ['kategori' => 'Co-Working Space']));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame($before + 1, Laporan::count());
    }

    public function test_filter_laporan_menyediakan_tahun_sebelumnya_dan_menolak_periode_tidak_valid(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $tahunIni = now()->year;

        $this->get(route('admin.laporan'))
            ->assertOk()
            ->assertSee('value="'.($tahunIni - 5).'"', false)
            ->assertSee('value="'.($tahunIni + 1).'"', false);

        // Periode di luar rentang kembali ke bulan berjalan, bukan galat 500.
        $this->get(route('admin.laporan', ['bulan' => 13, 'tahun' => 99999]))->assertOk();
        $this->get(route('admin.laporan.pdf', ['bulan' => 0, 'tahun' => 'abc']))->assertOk();
    }

    public function test_laporan_dikelompokkan_per_bulan_disetujui_bukan_periode_sewa(): void
    {
        $this->actingAs($this->admin(), 'admin');

        // Sewa mulai ~7 bulan lagi, tetapi disetujui hari ini → masuk laporan bulan ini saja.
        $r = $this->reservasiMenunggu('Hari', 215);
        $this->post(route('admin.reservasi.setujui', $r->kode_reservasi));
        $this->assertSame('Disetujui', $r->fresh()->status_reservasi->value);

        $this->get(route('admin.laporan', ['bulan' => now()->month, 'tahun' => now()->year]))
            ->assertOk()->assertSee($r->kode_reservasi);

        $mulai = $r->tanggal_mulai;
        $this->get(route('admin.laporan', ['bulan' => $mulai->month, 'tahun' => $mulai->year]))
            ->assertOk()->assertDontSee($r->kode_reservasi);
    }

    public function test_kalender_hari_dan_minggu_menampilkan_sewa_harian(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $r = $this->reservasiMenunggu('Hari', 220);
        $this->post(route('admin.reservasi.setujui', $r->kode_reservasi));
        $tanggal = $r->tanggal_mulai->toDateString();

        // Sewa tanpa jam tampil sebagai blok penuh (08.00–17.00) di time grid, bertautan ke detailnya.
        $pola = '~href="'.preg_quote(route('admin.reservasi.show', $r->kode_reservasi), '~').'"\s+class="db-cal-time-event"\s+style="top:0rem;~';
        foreach (['hari', 'minggu'] as $view) {
            $html = $this->get(route('admin.dashboard', ['view' => $view, 'tanggal' => $tanggal]))->assertOk()->getContent();
            $this->assertMatchesRegularExpression($pola, $html);
        }
    }
}
