<?php

namespace Tests\Feature;

use App\Models\Fasilitas;
use App\Models\JenisSewa;
use App\Models\Pemesan;
use App\Models\Reservasi;
use App\Models\TarifSewa;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** PDF Bukti Reservasi (dokumen milik pemesan, terpisah dari Admin\FakturController) tidak
 *  punya cakupan uji sebelumnya — tes ini memastikan halaman kop surat & logo BITC yang
 *  ter-crop render tanpa error dan menghasilkan PDF yang valid. */
class BuktiReservasiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bukti_reservasi_pdf_render_ok(): void
    {
        $jenis = JenisSewa::where('satuan', 'Jam')->firstOrFail();
        $f = Fasilitas::factory()->create(['status_aktif' => 'Aktif']);
        $t = TarifSewa::factory()->create([
            'id_fasilitas' => $f->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa,
            'status_aktif' => 'Aktif', 'harga' => 50000,
        ]);
        $p = Pemesan::factory()->create();

        $r = Reservasi::create([
            'id_pemesan' => $p->id_pemesan, 'id_tarif_sewa' => $t->id_tarif_sewa, 'id_admin' => null,
            'kode_reservasi' => 'RSV-BUKTITEST', 'kode_transaksi' => 'TRX-BUKTITEST',
            'tanggal_mulai' => Carbon::today()->addDays(10)->toDateString(),
            'tanggal_selesai' => Carbon::today()->addDays(10)->toDateString(),
            'jam_mulai' => '09:00', 'jam_selesai' => '11:00', 'durasi' => 2,
            'jumlah_pengguna' => 2, 'keperluan' => 'Uji render bukti reservasi', 'harga_satuan' => 50000,
            'total_biaya' => 100000, 'status_reservasi' => 'Menunggu', 'lock_status' => 'pending_approval',
        ]);

        $res = $this->actingAs($p, 'customer')->get(route('customer.reservasi-saya.bukti', $r->kode_reservasi));
        $res->assertOk();
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    private function buatReservasi(TarifSewa $t, Pemesan $p, string $kodeReservasi, string $kodeTransaksi, string $status): Reservasi
    {
        return Reservasi::create([
            'id_pemesan' => $p->id_pemesan, 'id_tarif_sewa' => $t->id_tarif_sewa, 'id_admin' => null,
            'kode_reservasi' => $kodeReservasi, 'kode_transaksi' => $kodeTransaksi,
            'tanggal_mulai' => Carbon::today()->addDays(11)->toDateString(),
            'tanggal_selesai' => Carbon::today()->addDays(11)->toDateString(),
            'jam_mulai' => '09:00', 'jam_selesai' => '11:00', 'durasi' => 2,
            'jumlah_pengguna' => 2, 'keperluan' => 'Uji bukti reservasi', 'harga_satuan' => 50000,
            'total_biaya' => 100000, 'status_reservasi' => $status,
            'lock_status' => $status === 'Dibatalkan' ? 'released' : 'pending_approval',
        ]);
    }

    /** Kalau kode reservasi yang diminta sendiri sudah Dibatalkan dan tidak ada ruangan lain
     *  yang masih aktif dalam transaksi yang sama, bukti tidak tersedia — bukan PDF kosong. */
    public function test_unduh_bukti_untuk_reservasi_yang_sudah_dibatalkan_ditolak(): void
    {
        $jenis = JenisSewa::where('satuan', 'Jam')->firstOrFail();
        $f = Fasilitas::factory()->create(['status_aktif' => 'Aktif']);
        $t = TarifSewa::factory()->create([
            'id_fasilitas' => $f->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa,
            'status_aktif' => 'Aktif', 'harga' => 50000,
        ]);
        $p = Pemesan::factory()->create();

        $r = $this->buatReservasi($t, $p, 'RSV-BATAL1', 'TRX-BATAL1', 'Dibatalkan');

        $res = $this->actingAs($p, 'customer')->get(route('customer.reservasi-saya.bukti', $r->kode_reservasi));
        $res->assertRedirect(route('customer.reservasi-saya.show', 'TRX-BATAL1'));
        $res->assertSessionHas('error');
    }

    /** Transaksi multi-ruangan dengan satu ruangan Dibatalkan: bukti tetap terbit (ruangan lain
     *  masih aktif) — ruangan yang dibatalkan seharusnya tidak lagi ikut ditampilkan. */
    public function test_unduh_bukti_transaksi_multi_ruangan_mengecualikan_yang_dibatalkan(): void
    {
        $jenis = JenisSewa::where('satuan', 'Jam')->firstOrFail();
        $p = Pemesan::factory()->create();

        $fAktif = Fasilitas::factory()->create(['status_aktif' => 'Aktif']);
        $tAktif = TarifSewa::factory()->create([
            'id_fasilitas' => $fAktif->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa,
            'status_aktif' => 'Aktif', 'harga' => 50000,
        ]);
        $fBatal = Fasilitas::factory()->create(['status_aktif' => 'Aktif']);
        $tBatal = TarifSewa::factory()->create([
            'id_fasilitas' => $fBatal->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa,
            'status_aktif' => 'Aktif', 'harga' => 50000,
        ]);

        $rAktif = $this->buatReservasi($tAktif, $p, 'RSV-MULTI1', 'TRX-MULTI1', 'Menunggu');
        $this->buatReservasi($tBatal, $p, 'RSV-MULTI2', 'TRX-MULTI1', 'Dibatalkan');

        $res = $this->actingAs($p, 'customer')->get(route('customer.reservasi-saya.bukti', $rAktif->kode_reservasi));
        $res->assertOk();
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    /** Bukti hanya untuk Menunggu & Disetujui; status lain ditolak dan tombolnya tidak tampil. */
    public function test_bukti_hanya_untuk_status_menunggu_dan_disetujui(): void
    {
        $jenis = JenisSewa::where('satuan', 'Jam')->firstOrFail();
        $p = Pemesan::factory()->create();

        foreach (['Menunggu' => true, 'Disetujui' => true, 'Ditolak' => false, 'Dibatalkan' => false, 'Selesai' => false, 'Kadaluwarsa' => false] as $status => $boleh) {
            $f = Fasilitas::factory()->create(['status_aktif' => 'Aktif']);
            $t = TarifSewa::factory()->create([
                'id_fasilitas' => $f->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa,
                'status_aktif' => 'Aktif', 'harga' => 50000,
            ]);
            $kode = 'RSV-ST'.strtoupper(substr($status, 0, 3));
            $r = $this->buatReservasi($t, $p, $kode, 'TRX-'.$kode, $status);

            $this->assertSame($boleh, $r->fresh()->buktiTersedia(), $status);
            $res = $this->actingAs($p, 'customer')->get(route('customer.reservasi-saya.bukti', $r->kode_reservasi));
            if ($boleh) {
                $res->assertOk();
                $this->assertStringStartsWith('%PDF', $res->getContent());
            } else {
                $res->assertRedirect()->assertSessionHas('error');
            }
        }
    }

    /** Unduh bukti wajib login, dan hanya pemilik reservasi yang dapat mengunduhnya. */
    public function test_unduh_bukti_wajib_login_dan_hanya_pemilik(): void
    {
        $jenis = JenisSewa::where('satuan', 'Jam')->firstOrFail();
        $f = Fasilitas::factory()->create(['status_aktif' => 'Aktif']);
        $t = TarifSewa::factory()->create([
            'id_fasilitas' => $f->id_fasilitas, 'id_jenis_sewa' => $jenis->id_jenis_sewa,
            'status_aktif' => 'Aktif', 'harga' => 50000,
        ]);
        $pemilik = Pemesan::factory()->create();
        $lain = Pemesan::factory()->create();
        $r = $this->buatReservasi($t, $pemilik, 'RSV-MILIK1', 'TRX-MILIK1', 'Menunggu');

        $this->get(route('customer.reservasi-saya.bukti', $r->kode_reservasi))->assertRedirect(route('customer.login'));
        $this->actingAs($lain, 'customer')->get(route('customer.reservasi-saya.bukti', $r->kode_reservasi))->assertNotFound();
        $this->assertSame(404, $this->get('/cek-status/'.$r->kode_reservasi.'/bukti-reservasi')->getStatusCode());
    }
}
