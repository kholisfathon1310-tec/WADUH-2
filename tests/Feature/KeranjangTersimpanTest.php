<?php

namespace Tests\Feature;

use App\Models\Fasilitas;
use App\Models\JenisSewa;
use App\Models\KeranjangPemesan;
use App\Models\Pemesan;
use App\Models\Reservasi;
use App\Models\TarifSewa;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\BantuPemesan;
use Tests\TestCase;

class KeranjangTersimpanTest extends TestCase
{
    use BantuPemesan;
    use DatabaseTransactions;

    private function tarifJam(): TarifSewa
    {
        $f = Fasilitas::factory()->create(['status_aktif' => 'Aktif', 'kapasitas' => 20]);

        return TarifSewa::factory()->create([
            'id_fasilitas'  => $f->id_fasilitas,
            'id_jenis_sewa' => JenisSewa::where('satuan', 'Jam')->firstOrFail()->id_jenis_sewa,
            'status_aktif'  => 'Aktif',
            'harga'         => 100_000,
        ]);
    }

    private function tambahItem(TarifSewa $t, string $tgl): void
    {
        $this->post('/reservasi/keranjang', [
            'id_fasilitas'    => $t->id_fasilitas,
            'id_tarif_sewa'   => $t->id_tarif_sewa,
            'tanggal_mulai'   => $tgl,
            'jam_mulai'       => '09:00',
            'jam_selesai'     => '11:00',
            'jumlah_pengguna' => 2,
            'keperluan'       => 'Rapat keranjang tersimpan',
        ])->assertRedirect(route('reservasi.checkout.form'));
    }

    /** Simulasikan keluar lalu masuk kembali: session baru (ID baru), pemesan yang sama. */
    private function keluarMasuk(Pemesan $pemesan): string
    {
        Auth::guard('customer')->logout();
        $this->flushSession();
        $this->app['session']->setId(\Illuminate\Support\Str::random(40));
        $this->app['session']->start();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->actingAs($pemesan, 'customer');

        return session()->getId();
    }

    public function test_keranjang_tetap_ada_setelah_keluar_dan_masuk_kembali(): void
    {
        $pemesan = $this->loginPemesan();
        $t = $this->tarifJam();
        $tgl = $this->hariKerja(10);
        $this->tambahItem($t, $tgl);

        $this->assertDatabaseHas('keranjang_pemesan', ['id_pemesan' => $pemesan->id_pemesan]);

        $sessionBaru = $this->keluarMasuk($pemesan);
        $this->assertNull(session('reservasi_cart'));

        $this->get(route('reservasi.checkout.form'))
            ->assertOk()
            ->assertSee($t->fasilitas->nama_fasilitas);
        $this->assertCount(1, session('reservasi_cart'));

        // Hold dipindah ke session baru: bebas bagi pemilik, terkunci bagi session lain.
        $svc = app(AvailabilityService::class);
        $slot = ['tanggal_mulai' => $tgl, 'tanggal_selesai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00'];
        $this->assertTrue($svc->slotAvailable($t->id_fasilitas, $slot, $sessionBaru));
        $this->assertFalse($svc->slotAvailable($t->id_fasilitas, $slot, 'session-lain'));
    }

    public function test_checkout_sukses_mengosongkan_keranjang_tersimpan(): void
    {
        $pemesan = $this->loginPemesan();
        $this->tambahItem($this->tarifJam(), $this->hariKerja(10));
        $this->assertDatabaseHas('keranjang_pemesan', ['id_pemesan' => $pemesan->id_pemesan]);

        $this->post(route('reservasi.checkout'))->assertRedirect(route('customer.reservasi-saya.index'));

        $this->assertDatabaseMissing('keranjang_pemesan', ['id_pemesan' => $pemesan->id_pemesan]);
        $this->assertSame(1, Reservasi::where('id_pemesan', $pemesan->id_pemesan)->count());
    }

    public function test_kosongkan_keranjang_menghapus_salinan_tersimpan(): void
    {
        $pemesan = $this->loginPemesan();
        $this->tambahItem($this->tarifJam(), $this->hariKerja(10));

        $this->delete(route('reservasi.keranjang.kosongkan'))->assertRedirect();
        $this->assertDatabaseMissing('keranjang_pemesan', ['id_pemesan' => $pemesan->id_pemesan]);
    }

    public function test_item_bertanggal_lampau_dibuang_saat_dimuat_dan_ditolak_saat_checkout(): void
    {
        $pemesan = $this->loginPemesan();
        $t = $this->tarifJam();
        $tgl = $this->hariKerja(10);
        $this->tambahItem($t, $tgl);
        $this->tambahItem($t, $this->hariKerja(20));

        // Waktu berjalan melewati tanggal item pertama.
        Carbon::setTestNow(Carbon::parse($tgl)->addDay()->setTime(9, 0));
        try {
            $this->keluarMasuk($pemesan);
            $this->get(route('reservasi.checkout.form'))->assertOk();
            $this->assertCount(1, session('reservasi_cart'));
            $this->assertCount(1, KeranjangPemesan::find($pemesan->id_pemesan)->item);

            // Item di session yang sudah lewat (tanpa dimuat ulang) ditolak saat diajukan.
            $item = session('reservasi_cart')[0];
            $item['tanggal_mulai'] = $item['tanggal_selesai'] = Carbon::yesterday()->toDateString();
            session()->put('reservasi_cart', [$item]);
            session()->save();
            $this->post(route('reservasi.checkout'))
                ->assertRedirect(route('reservasi.checkout.form'))
                ->assertSessionHasErrors('jadwal');
            $this->assertSame(0, Reservasi::where('id_pemesan', $pemesan->id_pemesan)->count());
        } finally {
            Carbon::setTestNow();
        }
    }
}
