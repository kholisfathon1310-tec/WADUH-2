<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\DokumenPersyaratan;
use App\Models\Fasilitas;
use App\Models\JenisSewa;
use App\Models\Pemesan;
use App\Models\Reservasi;
use App\Models\TarifSewa;
use App\Services\ReservasiApprovalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Mengunci perbaikan temuan UAT Oktober 2026. */
class PerbaikanUatTest extends TestCase
{
    use DatabaseTransactions;

    private function tarif(string $satuan = 'Jam'): TarifSewa
    {
        $jenis = JenisSewa::where('satuan', $satuan)->firstOrFail();
        $f = Fasilitas::factory()->create(['status_aktif' => 'Aktif', 'kapasitas' => 10, 'kategori_fasilitas' => 'Working Space']);

        return TarifSewa::factory()->create([
            'id_fasilitas' => $f->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa, 'status_aktif' => 'Aktif', 'harga' => 50000,
        ]);
    }

    private function hariKerja(int $hariLagi): string
    {
        $t = Carbon::today()->addDays($hariLagi);
        while ($t->isWeekend()) {
            $t->addDay();
        }

        return $t->toDateString();
    }

    private function reservasi(TarifSewa $t, array $isi = []): Reservasi
    {
        $tgl = $this->hariKerja(10);
        $kode = strtoupper(Str::random(2)).random_int(100, 999);

        return Reservasi::create(array_merge([
            'id_pemesan' => Pemesan::factory()->create()->id_pemesan, 'id_tarif_sewa' => $t->id_tarif_sewa, 'id_admin' => null,
            'kode_reservasi' => $kode, 'kode_transaksi' => $kode,
            'tanggal_mulai' => $tgl, 'tanggal_selesai' => $tgl, 'jam_mulai' => '14:00', 'jam_selesai' => '15:00', 'durasi' => 1,
            'jumlah_pengguna' => 2, 'keperluan' => 'Uji perbaikan', 'harga_satuan' => 50000, 'total_biaya' => 50000,
            'status_reservasi' => 'Menunggu', 'lock_status' => 'pending_approval',
        ], $isi));
    }

    /** Dua pengajuan Menunggu pada jadwal sama tidak saling mengunci; setelah satu disetujui, yang lain gagal. */
    public function test_dua_pengajuan_menunggu_tidak_saling_mengunci(): void
    {
        $t = $this->tarif();
        $a = $this->reservasi($t);
        $b = $this->reservasi($t);
        $svc = app(ReservasiApprovalService::class);

        $this->assertTrue($svc->passes($a));
        $this->assertTrue($svc->passes($b));

        $a->update(['status_reservasi' => 'Disetujui', 'lock_status' => 'confirmed']);
        $this->assertFalse($svc->passes($b->fresh()));
        $this->assertFalse(collect($svc->checklist($b->fresh()))->firstWhere('label', 'Tidak bentrok jadwal')['passed']);
    }

    /** Notifikasi sukses setujui tetap tampil setelah halaman diganti (fetch + location.replace). */
    public function test_flash_setujui_tetap_tampil_setelah_nav_replace(): void
    {
        $r = $this->reservasi($this->tarif());
        $this->actingAs(Admin::firstOrFail(), 'admin');

        $this->post(route('admin.reservasi.setujui', $r->kode_reservasi))->assertRedirect();
        // GET hasil redirect yang diikuti fetch (membawa header) tidak boleh menghabiskan flash.
        $this->withHeaders(['X-Nav-Replace' => '1'])->get(route('admin.reservasi.show', $r->kode_reservasi))->assertOk();
        $this->get(route('admin.reservasi.show', $r->kode_reservasi))->assertOk()->assertSee('berhasil disetujui');
    }

    public function test_lupa_kata_sandi_tidak_membocorkan_email_terdaftar(): void
    {
        $terdaftar = Pemesan::factory()->create();
        $balasanA = $this->from('/customer/lupa-password')->post('/customer/lupa-password', ['email' => 'tidak.ada.'.Str::random(6).'@gmail.com']);
        $balasanB = $this->from('/customer/lupa-password')->post('/customer/lupa-password', ['email' => $terdaftar->email]);

        $this->assertSame(session('success'), $balasanB->getSession()->get('success'));
        $balasanA->assertSessionHas('success');
        $balasanA->assertSessionMissing('error');
    }

    public function test_tautan_reset_yang_tidak_berlaku_ditolak_saat_dibuka(): void
    {
        $p = Pemesan::factory()->create();
        $token = Password::broker('pemesans')->createToken($p);

        $this->get('/customer/reset-password/'.$token.'?email='.urlencode($p->email))->assertOk()->assertSee('<input type="password" name="password"', false);
        $this->get('/customer/reset-password/token-palsu?email='.urlencode($p->email))->assertOk()
            ->assertSee('Tautan tidak berlaku')->assertDontSee('<input type="password" name="password"', false);
    }

    public function test_dokumen_persyaratan_hanya_untuk_admin(): void
    {
        Storage::fake('public');
        $r = $this->reservasi($this->tarif('Bulan'));
        Storage::disk('public')->put('dokumen/uji.pdf', '%PDF-1.4 uji');
        $dok = DokumenPersyaratan::create([
            'id_reservasi' => $r->id_reservasi, 'jenis_dokumen' => 'Persyaratan Sewa Bulanan', 'nama_file' => 'uji.pdf',
            'lokasi_file' => 'dokumen/uji.pdf', 'tanggal_upload' => now(), 'status_verifikasi' => 'Menunggu',
        ]);

        $this->get(route('admin.reservasi.dokumen.lihat', $dok->id_dokumen))->assertRedirect(route('admin.login'));
        $res = $this->actingAs(Admin::firstOrFail(), 'admin')->get(route('admin.reservasi.dokumen.lihat', $dok->id_dokumen));
        $res->assertOk();
        $this->assertStringContainsString('%PDF', $res->streamedContent());
    }

    public function test_data_reservasi_dibagi_per_halaman(): void
    {
        $this->actingAs(Admin::firstOrFail(), 'admin');
        $jumlahKode = Reservasi::distinct('kode_transaksi')->count('kode_transaksi');

        $res = $this->get(route('admin.reservasi.index'))->assertOk();
        $res->assertSee($jumlahKode.' reservasi');
        if ($jumlahKode > 10) {
            $res->assertSee('data-pagination', false);
            $this->get(route('admin.reservasi.index', ['page' => 2]))->assertOk()->assertSee('Menampilkan 11');
        }
    }

    /** Denah yang diminta untuk Sabtu/Minggu atau tanggal lampau tidak menampilkan hari tutup. */
    public function test_denah_menggeser_tanggal_ke_hari_kerja(): void
    {
        $f = Fasilitas::where('status_aktif', 'Aktif')->firstOrFail();
        $this->actingAs(Pemesan::factory()->create(), 'customer');
        $sabtu = Carbon::today()->next(Carbon::SATURDAY)->toDateString();
        $senin = Carbon::parse($sabtu)->addDays(2)->toDateString();

        $this->get(route('reservasi.denah', ['kategori' => $f->kategori_fasilitas, 'lantai' => $f->id_lantai, 'jenis' => 2, 'tanggal_mulai' => $sabtu]))
            ->assertOk()->assertSee('value="'.$senin.'"', false);
    }
}
