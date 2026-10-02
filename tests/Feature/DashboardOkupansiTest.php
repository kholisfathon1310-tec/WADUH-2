<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Fasilitas;
use App\Models\JenisSewa;
use App\Models\Pemesan;
use App\Models\Reservasi;
use App\Models\TarifSewa;
use App\Services\OkupansiService;
use App\Support\KalenderReservasi;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Okupansi di dashboard Admin + ketahanan parameter bulan/tahun. Memakai data hasil seeder. */
class DashboardOkupansiTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): Admin
    {
        return Admin::firstOrFail();
    }

    private function reservasi(Fasilitas $fasilitas, string $mulai, string $selesai, string $status): Reservasi
    {
        $tarif = TarifSewa::factory()->create([
            'id_fasilitas'  => $fasilitas->id_fasilitas,
            'id_jenis_sewa' => JenisSewa::where('satuan', 'Hari')->firstOrFail()->id_jenis_sewa,
            'harga'         => 100_000,
        ]);

        return Reservasi::create([
            'id_pemesan'       => Pemesan::factory()->create()->id_pemesan,
            'id_tarif_sewa'    => $tarif->id_tarif_sewa,
            'kode_reservasi'   => 'RSV-OKUP-'.strtoupper(substr(md5($mulai.$status.$fasilitas->id_fasilitas), 0, 6)),
            'kode_transaksi'   => 'RSV-OKUP',
            'tanggal_mulai'    => $mulai,
            'tanggal_selesai'  => $selesai,
            'durasi'           => Carbon::parse($mulai)->diffInDays(Carbon::parse($selesai)) + 1,
            'jumlah_pengguna'  => 1,
            'keperluan'        => 'Uji okupansi',
            'harga_satuan'     => 100_000,
            'total_biaya'      => 100_000,
            'status_reservasi' => $status,
            'lock_status'      => 'confirmed',
        ]);
    }

    public function test_dashboard_admin_terbuka_dengan_parameter_default(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Okupansi per Bulan')
            ->assertSee('Okupansi per Fasilitas')
            ->assertSee(now()->translatedFormat('F Y'));
    }

    public function test_dashboard_admin_menerima_parameter_bulan_dan_tahun_yang_valid(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/dashboard?okupansi_tahun=2027&okupansi_bulan=2027-02&status_bulan=2026-02')
            ->assertOk()
            ->assertSee('Februari 2027')
            ->assertSee('Februari 2026')
            ->assertViewHas('okupansiNav', fn (array $nav) => $nav['tahun'] === 2027 && $nav['bulanKunci'] === '2027-02');
    }

    public function test_tahun_tanpa_bulan_memakai_bulan_yang_sama_pada_tahun_itu(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/dashboard?okupansi_tahun=2030')
            ->assertOk()
            ->assertViewHas('okupansiNav', fn (array $nav) => $nav['tahun'] === 2030 && $nav['bulanKunci'] === '2030-'.now()->format('m'));
    }

    /** @dataProvider parameterTidakValid */
    public function test_dashboard_admin_tidak_error_dengan_parameter_tidak_valid(string $query): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/dashboard?'.$query)
            ->assertOk()
            ->assertViewHas('okupansiNav', fn (array $nav) => $nav['bulanKunci'] === now()->format('Y-m') && $nav['tahun'] === now()->year);
    }

    public static function parameterTidakValid(): array
    {
        return [
            'teks'            => ['okupansi_bulan=abc&okupansi_tahun=abc&status_bulan=abc'],
            'bulan ke-13'     => ['okupansi_bulan=2026-13&status_bulan=2026-13'],
            'bulan nol'       => ['okupansi_bulan=2026-00&status_bulan=2026-00'],
            'tahun di luar'   => ['okupansi_tahun=99999&okupansi_bulan=9999-01'],
            'array'           => ['okupansi_bulan[]=2026-01&okupansi_tahun[]=2026&status_bulan[]=2026-01'],
            'kalender rusak'  => ['view=bulan&bulan=xx&tanggal=2026-02-31'],
            'kalender minggu' => ['view=minggu&tanggal=bukan-tanggal'],
        ];
    }

    public function test_status_bulan_tidak_melompat_pada_akhir_bulan(): void
    {
        Carbon::setTestNow('2026-03-31 10:00:00');
        try {
            // Dulu createFromFormat('Y-m') mengisi hari dari "hari ini" (31) → Februari jadi Maret.
            $this->assertSame('2026-02-01', KalenderReservasi::parseBulan('2026-02')->toDateString());

            $this->actingAs($this->admin(), 'admin')
                ->get('/admin/dashboard?status_bulan=2026-02&okupansi_bulan=2026-02&view=bulan&bulan=2026-02')
                ->assertOk()
                ->assertViewHas('statBulanNav', fn (array $nav) => $nav['prev'] === '2026-01' && $nav['next'] === '2026-03')
                ->assertViewHas('okupansiNav', fn (array $nav) => $nav['bulanKunci'] === '2026-02')
                ->assertViewHas('rangeKalender', fn (array $r) => $r['navPrev'] === '2026-01' && $r['navNext'] === '2026-03');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_per_fasilitas_menghitung_hari_kerja_yang_terisi(): void
    {
        $fasilitas = Fasilitas::factory()->create(['kode_fasilitas' => 'UJI-OKUP-1']);
        $lain = Fasilitas::factory()->create(['kode_fasilitas' => 'UJI-OKUP-2']);

        // Senin 4 Jan – Minggu 10 Jan 2038: 5 hari kerja terisi (Sabtu & Minggu tidak dihitung).
        $this->reservasi($fasilitas, '2038-01-04', '2038-01-10', 'Disetujui');
        // Status yang tidak memblokir tidak ikut dihitung.
        $this->reservasi($lain, '2038-01-04', '2038-01-08', 'Menunggu');
        $this->reservasi($lain, '2038-01-11', '2038-01-15', 'Ditolak');

        $service = app(OkupansiService::class);
        $bulan = Carbon::create(2038, 1, 1);
        $hasil = $service->perFasilitas($bulan)->keyBy('kode');

        $this->assertSame(21, $service->hariKerja($bulan)); // Januari 2038: 21 hari kerja
        $this->assertSame(5, $hasil['UJI-OKUP-1']['terisi']);
        $this->assertSame(21, $hasil['UJI-OKUP-1']['hariKerja']);
        $this->assertSame(24, $hasil['UJI-OKUP-1']['pct']); // 5 / 21
        $this->assertSame($fasilitas->id_fasilitas, $hasil['UJI-OKUP-1']['id_fasilitas']);
        $this->assertSame(0, $hasil['UJI-OKUP-2']['terisi']);
        $this->assertSame(0, $hasil['UJI-OKUP-2']['pct']);

        // Gedung: 5 hari-fasilitas terisi dari (hari kerja × fasilitas aktif).
        $tahunan = $service->bulananTahun(2038);
        $this->assertCount(12, $tahunan);
        $this->assertSame('2038-01', $tahunan[0]['kunci']);
        $this->assertSame(5, $tahunan[0]['terisi']);
        $this->assertSame(21 * Fasilitas::where('status_aktif', 'Aktif')->count(), $tahunan[0]['kapasitas']);
        $this->assertSame(0, $tahunan[1]['terisi']);
    }

    public function test_reservasi_lintas_bulan_dan_selesai_ikut_dihitung(): void
    {
        $fasilitas = Fasilitas::factory()->create(['kode_fasilitas' => 'UJI-OKUP-3']);

        // Kamis 28 Jan – Selasa 2 Feb 2038: Januari 2 hari kerja (28, 29), Februari 2 (1, 2).
        $this->reservasi($fasilitas, '2038-01-28', '2038-02-02', 'Selesai');

        $service = app(OkupansiService::class);
        $this->assertSame(2, $service->perFasilitas(Carbon::create(2038, 1, 1))->firstWhere('kode', 'UJI-OKUP-3')['terisi']);
        $this->assertSame(2, $service->perFasilitas(Carbon::create(2038, 2, 1))->firstWhere('kode', 'UJI-OKUP-3')['terisi']);
    }

    public function test_per_fasilitas_mengurutkan_kode_secara_natural_dan_melewati_yang_tidak_aktif(): void
    {
        $idLantai = Fasilitas::query()->value('id_lantai');
        Fasilitas::factory()->create(['kode_fasilitas' => 'UJI-K10', 'id_lantai' => $idLantai]);
        Fasilitas::factory()->create(['kode_fasilitas' => 'UJI-K2', 'id_lantai' => $idLantai]);
        Fasilitas::factory()->nonaktif()->create(['kode_fasilitas' => 'UJI-K5', 'id_lantai' => $idLantai]);

        $kode = app(OkupansiService::class)->perFasilitas(Carbon::create(2038, 1, 1))
            ->pluck('kode')
            ->filter(fn (string $k) => str_starts_with($k, 'UJI-K'))
            ->values()
            ->all();

        $this->assertSame(['UJI-K2', 'UJI-K10'], $kode);
    }

    public function test_dashboard_admin_menampilkan_fasilitas_terisi_pada_bulan_terpilih(): void
    {
        $fasilitas = Fasilitas::factory()->create(['kode_fasilitas' => 'UJI-OKUP-VIEW']);
        $this->reservasi($fasilitas, '2038-01-04', '2038-01-08', 'Disetujui');

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/dashboard?okupansi_bulan=2038-01')
            ->assertOk()
            ->assertSee('UJI-OKUP-VIEW')
            ->assertSee('5 dari 21 hari kerja')
            ->assertSee(route('admin.monitoring.detail', $fasilitas->id_fasilitas), false);
    }

    public function test_kartu_status_menghitung_per_fasilitas_bukan_per_reservasi(): void
    {
        $bulan = now()->format('Y-m');
        $awal = $this->actingAs($this->admin(), 'admin')->get('/admin/dashboard?status_bulan='.$bulan)
            ->viewData('statistik')['menunggu'];

        // Satu reservasi (satu kode transaksi) berisi dua fasilitas.
        $tgl = now()->addMonth()->toDateString();
        $this->reservasi(Fasilitas::factory()->create(['status_aktif' => 'Aktif']), $tgl, $tgl, 'Menunggu');
        $this->reservasi(Fasilitas::factory()->create(['status_aktif' => 'Aktif']), $tgl, $tgl, 'Menunggu');

        $this->get('/admin/dashboard?status_bulan='.$bulan)
            ->assertOk()
            ->assertViewHas('statistik', fn (array $s) => $s['menunggu'] === $awal + 2)
            ->assertSee('id="ringkasan-status"', false);
    }

    public function test_dashboard_pemesan_terbuka(): void
    {
        $this->actingAs(Pemesan::factory()->create(), 'customer')
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('Reservasi Terbaru')
            ->assertDontSee('dbOkupTip', false);

        $this->actingAs(Pemesan::factory()->create(), 'customer')
            ->get('/customer/dashboard?view=minggu&tanggal=rusak&bulan=2026-99')
            ->assertOk();
    }
}
