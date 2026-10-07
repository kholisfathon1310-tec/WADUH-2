<?php

namespace Tests\Feature;

use App\Http\Controllers\StatusRealtimeController;
use App\Models\Admin;
use App\Models\Pemesan;
use App\Models\Reservasi;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RealtimeTest extends TestCase
{
    use DatabaseTransactions;

    /** Reservasi Menunggu jauh di masa depan supaya tidak ikut kedaluwarsa otomatis. */
    private function reservasiMenunggu(?Pemesan $pemesan = null): Reservasi
    {
        $mulai = Carbon::today()->addDays(200);

        return Reservasi::factory()->create([
            'id_pemesan'      => ($pemesan ?? Pemesan::factory()->create())->id_pemesan,
            'tanggal_mulai'   => $mulai->toDateString(),
            'tanggal_selesai' => $mulai->toDateString(),
            'durasi'          => 1,
        ]);
    }

    private function ringkasan(string $peran, array $kursor)
    {
        return $this->getJson(route('status-reservasi.ringkasan', ['peran' => $peran] + $kursor))->assertOk();
    }

    public function test_tamu_hanya_menerima_sidik_jari(): void
    {
        $this->getJson(route('status-reservasi.ringkasan', ['peran' => 'admin', 'r' => 1, 'h' => 1]))
            ->assertOk()
            ->assertJsonStructure(['versi'])
            ->assertJsonMissing(['badge'])
            ->assertJsonMissingPath('notifikasi');
    }

    public function test_admin_menerima_badge_dan_notifikasi_pengajuan_baru(): void
    {
        $this->actingAs(Admin::firstOrFail(), 'admin');
        $kursor = StatusRealtimeController::kursorAwal();
        $r = $this->reservasiMenunggu();

        $res = $this->ringkasan('admin', $kursor);
        $res->assertJsonPath('badge.menunggu', Reservasi::where('status_reservasi', 'Menunggu')->count());
        $this->assertCount(1, $res->json('notifikasi'));
        $this->assertStringContainsString($r->kode_transaksi, $res->json('notifikasi.0.pesan'));
        $this->assertSame($r->id_reservasi, $res->json('kursor.r'));

        // Kursor sudah maju → notifikasi yang sama tidak dikirim lagi.
        $this->assertCount(0, $this->ringkasan('admin', $res->json('kursor'))->json('notifikasi'));
    }

    public function test_pemesan_diberi_tahu_saat_reservasinya_disetujui(): void
    {
        $pemesan = Pemesan::factory()->create();
        $r = $this->reservasiMenunggu($pemesan);
        $kursor = StatusRealtimeController::kursorAwal();

        $this->actingAs(Admin::firstOrFail(), 'admin');
        $r->id_admin = Admin::firstOrFail()->id_admin;
        $r->status_reservasi = 'Disetujui';
        $r->save();

        $this->actingAs($pemesan, 'customer');
        $res = $this->ringkasan('pemesan', $kursor);
        $this->assertSame('Reservasi disetujui', $res->json('notifikasi.0.judul'));
        $this->assertSame(route('customer.reservasi-saya.show', $r->kode_reservasi), $res->json('notifikasi.0.url'));
    }

    public function test_pemesan_tidak_menerima_notifikasi_milik_pemesan_lain_atau_data_admin(): void
    {
        $lain = $this->reservasiMenunggu();
        $kursor = StatusRealtimeController::kursorAwal();
        $lain->id_admin = Admin::firstOrFail()->id_admin;
        $lain->status_reservasi = 'Ditolak';
        $lain->save();

        $this->actingAs(Pemesan::factory()->create(), 'customer');
        $this->assertSame([], $this->ringkasan('pemesan', $kursor)->json('notifikasi'));
        $this->ringkasan('admin', $kursor)->assertJsonMissingPath('badge');
    }

    public function test_layout_menyertakan_pemantau_dan_badge_live(): void
    {
        $this->actingAs(Admin::firstOrFail(), 'admin')
            ->get(route('admin.reservasi.index'))
            ->assertOk()
            ->assertSee('data-live-badge="menunggu"', false)
            ->assertSee('data-live="reservasi"', false)
            ->assertSee('id="rtToasts"', false);
    }
}
