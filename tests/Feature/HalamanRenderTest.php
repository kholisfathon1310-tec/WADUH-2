<?php

namespace Tests\Feature;

use App\Enums\StatusReservasi;
use App\Models\Admin;
use App\Models\Fasilitas;
use App\Models\JenisSewa;
use App\Models\Lantai;
use App\Models\Pemesan;
use App\Models\Reservasi;
use App\Models\TarifSewa;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Memastikan halaman publik, pemesan, dan admin merender tanpa galat (data seeder + rollback). */
class HalamanRenderTest extends TestCase
{
    use DatabaseTransactions;

    private function fasilitasSeeder(): Fasilitas
    {
        return Fasilitas::where('status_aktif', 'Aktif')->whereHas('tarifSewa')->with('lantai')->firstOrFail();
    }

    /** @return array{0: Pemesan, 1: \Illuminate\Support\Collection<int, Reservasi>} */
    private function pemesanDenganReservasi(): array
    {
        $pemesan = Pemesan::factory()->create();
        $fasilitas = $this->fasilitasSeeder();

        $daftar = collect(['Jam', 'Hari', 'Bulan'])->map(function (string $satuan, int $i) use ($pemesan, $fasilitas) {
            $jenis = JenisSewa::where('satuan', $satuan)->firstOrFail();
            $tarif = TarifSewa::firstOrCreate(
                ['id_fasilitas' => $fasilitas->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa],
                ['harga' => 100_000, 'status_aktif' => 'Aktif'],
            );
            $status = [StatusReservasi::Menunggu, StatusReservasi::Disetujui, StatusReservasi::Selesai][$i];

            return Reservasi::factory()->create([
                'id_pemesan'       => $pemesan->id_pemesan,
                'id_tarif_sewa'    => $tarif->id_tarif_sewa,
                'jam_mulai'        => $satuan === 'Jam' ? '09:00' : null,
                'jam_selesai'      => $satuan === 'Jam' ? '11:00' : null,
                'status_reservasi' => $status->value,
            ]);
        });

        return [$pemesan, $daftar];
    }

    public function test_halaman_publik_merender(): void
    {
        $fasilitas = $this->fasilitasSeeder();
        $kategori = $fasilitas->kategori_fasilitas;

        $this->get('/')->assertOk()->assertSee('WADUH');
        $this->get('/cek-status')->assertOk()->assertSee('RS186')->assertDontSee('TRX-');
        $this->get('/cek-status/RSV-TIDAKADA')->assertOk()->assertSee('tidak ditemukan');
        $this->get('/fasilitas')->assertOk();
        $this->get(route('fasilitas.lantai', ['kategori' => $kategori]))->assertOk();
        $this->get(route('fasilitas.denah', ['kategori' => $kategori, 'lantai' => $fasilitas->id_lantai]))->assertOk();
    }

    public function test_halaman_auth_merender(): void
    {
        foreach (['/customer/masuk', '/customer/daftar', '/customer/lupa-password', '/customer/reset-password/token-uji?email=a@b.test',
            '/admin/login', '/admin/lupa-password', '/admin/reset-password/token-uji?email=a@b.test'] as $url) {
            $this->get($url)->assertOk()->assertDontSee("confirmButtonText: 'Oke", false);
        }

        $this->get('/admin/login')->assertSee('Panel Admin');
    }

    public function test_galat_validasi_auth_tampil_di_bawah_kolom(): void
    {
        $this->from('/customer/daftar')->post('/customer/daftar', ['email' => 'bukan-email'])
            ->assertRedirect('/customer/daftar')
            ->assertSessionHasErrors(['nama_lengkap', 'email', 'password']);

        $this->followingRedirects()->from('/customer/daftar')->post('/customer/daftar', ['email' => 'bukan-email'])
            ->assertOk()
            ->assertSee('catatan-salah', false)
            ->assertSee('Format email tidak valid, contoh: nama@email.com.');

        $this->followingRedirects()->from('/admin/login')->post('/admin/login', ['admin_email' => '', 'admin_password' => ''])
            ->assertOk()
            ->assertSee('Email wajib diisi.')
            ->assertSee('Kata sandi wajib diisi.');
    }

    public function test_halaman_pemesan_merender(): void
    {
        [$pemesan, $daftar] = $this->pemesanDenganReservasi();
        $this->actingAs($pemesan, 'customer');

        $this->get('/reservasi')->assertOk();
        $this->get('/customer/reservasi-saya')->assertOk()
            ->assertSee('Menunggu Verifikasi')
            ->assertSee($daftar[0]->jumlah_pengguna.' orang');

        foreach ($daftar as $r) {
            $this->get(route('customer.reservasi-saya.show', $r->kode_reservasi))->assertOk()
                ->assertDontSee('Lunas')
                ->assertSee('Durasi '.$r->durasi);
            $this->get(route('cek-status.hasil', $r->kode_reservasi))->assertOk()->assertDontSee('Lunas');
        }
    }

    public function test_halaman_admin_merender(): void
    {
        [, $daftar] = $this->pemesanDenganReservasi();
        $this->actingAs(Admin::firstOrFail(), 'admin');
        $fasilitas = $this->fasilitasSeeder();
        $lantai = Lantai::findOrFail($fasilitas->id_lantai);

        $this->get('/admin/reservasi')->assertOk();
        $this->get('/admin/reservasi?status=Menunggu')->assertOk();
        foreach ($daftar as $r) {
            $this->get(route('admin.reservasi.show', $r->kode_reservasi))->assertOk()->assertSee('Checklist');
        }
        $this->get('/admin/laporan')->assertOk();
        $this->get('/admin/monitoring?lantai='.$lantai->id_lantai)->assertOk();
        $this->get(route('admin.monitoring.detail', $fasilitas))->assertOk();
    }
}
