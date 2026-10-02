<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Fasilitas;
use App\Models\Pemesan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Parameter filter/pencarian yang tidak valid (teks di kolom tanggal, angka ngawur, array,
 * dsb.) tidak boleh membuat halaman galat 500 — cukup diabaikan atau ditolak dengan wajar.
 */
class ParameterNgawurTest extends TestCase
{
    use DatabaseTransactions;

    private const NGAWUR = ['abc', '2026-13-45', '-1', '99999999999', "' OR 1=1 --", '<script>x</script>'];

    private function cek(string $url, array $param): void
    {
        foreach ($param as $nama) {
            foreach (self::NGAWUR as $nilai) {
                $res = $this->get($url.(str_contains($url, '?') ? '&' : '?').http_build_query([$nama => $nilai]));
                $this->assertLessThan(500, $res->status(), "{$url} dengan {$nama}={$nilai} menghasilkan {$res->status()}");
            }
            // Parameter berbentuk array (mis. ?tanggal[]=x).
            $res = $this->get($url.(str_contains($url, '?') ? '&' : '?').$nama.'[]=x');
            $this->assertLessThan(500, $res->status(), "{$url} dengan {$nama}[] menghasilkan {$res->status()}");
        }
    }

    public function test_halaman_admin_tahan_parameter_ngawur(): void
    {
        $this->actingAs(Admin::firstOrFail(), 'admin');
        $idFasilitas = Fasilitas::value('id_fasilitas');

        $this->cek('/admin/dashboard', ['okupansi_bulan', 'okupansi_tahun', 'status_bulan', 'view', 'tanggal']);
        $this->cek('/admin/reservasi', ['pemesan', 'kategori', 'lantai', 'jenis_sewa', 'status', 'tanggal', 'page']);
        $this->cek('/admin/monitoring', ['lantai', 'tanggal_mulai']);
        $this->cek("/admin/monitoring/fasilitas/{$idFasilitas}", ['tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai']);
        $this->cek('/admin/laporan', ['bulan', 'tahun']);
    }

    public function test_halaman_pemesan_dan_publik_tahan_parameter_ngawur(): void
    {
        $idFasilitas = Fasilitas::value('id_fasilitas');

        $this->cek('/fasilitas', ['tanggal_mulai']);
        $this->cek("/fasilitas/detail/{$idFasilitas}", ['tanggal', 'tanggal_mulai']);

        $this->actingAs(Pemesan::factory()->create(), 'customer');
        $this->cek('/customer/dashboard', ['view', 'tanggal', 'bulan']);
        $this->cek('/customer/reservasi-saya', ['status', 'q', 'page']);
        $this->cek("/reservasi/fasilitas/{$idFasilitas}", ['tanggal', 'jenis', 'antrian']);
        $this->cek("/reservasi/fasilitas/{$idFasilitas}/jam-terisi", ['tanggal']);
        $this->cek("/reservasi/fasilitas/{$idFasilitas}/cek-jadwal", ['tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai', 'id_tarif_sewa']);
    }
}
