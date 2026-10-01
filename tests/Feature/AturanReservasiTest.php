<?php

namespace Tests\Feature;

use App\Enums\SatuanSewa;
use App\Models\Fasilitas;
use App\Models\JenisSewa;
use App\Models\Pemesan;
use App\Models\Reservasi;
use App\Models\TarifSewa;
use App\Services\AvailabilityService;
use App\Services\CartService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\BantuPemesan;
use Tests\TestCase;

/**
 * Aturan inti reservasi: kondisi fasilitas (Tersedia / Sebagian Terisi / Terisi) per jenis
 * sewa, pesan bentrok dengan tanggal/jam spesifik, kapasitas, dan perhitungan sewa bulanan.
 */
class AturanReservasiTest extends TestCase
{
    use BantuPemesan, DatabaseTransactions;

    private const HARGA = ['Jam' => 50_000, 'Hari' => 400_000, 'Bulan' => 1_000_000];

    private Carbon $senin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginPemesan();
        // Senin pertama setelah 30 hari dari sekarang — semua tanggal uji dihitung dari sini
        // supaya pasti hari kerja di masa depan.
        $this->senin = Carbon::today()->addDays(30)->next(Carbon::MONDAY);
    }

    /** Tanggal (Y-m-d) $hari hari setelah Senin acuan. */
    private function tgl(int $hari = 0): string
    {
        return $this->senin->copy()->addDays($hari)->toDateString();
    }

    /** Fasilitas buatan dengan tarif untuk satuan yang diminta. */
    private function fasilitas(array $satuan = ['Jam', 'Hari', 'Bulan'], int $kapasitas = 10): Fasilitas
    {
        $f = Fasilitas::factory()->create([
            'status_aktif' => 'Aktif', 'kapasitas' => $kapasitas, 'kategori_fasilitas' => 'Working Space',
        ]);
        foreach ($satuan as $s) {
            TarifSewa::factory()->create([
                'id_fasilitas'  => $f->id_fasilitas,
                'id_jenis_sewa' => JenisSewa::where('satuan', $s)->value('id_jenis_sewa'),
                'harga'         => self::HARGA[$s],
                'status_aktif'  => 'Aktif',
            ]);
        }

        return $f;
    }

    private function tarif(Fasilitas $f, string $satuan): TarifSewa
    {
        return TarifSewa::where('id_fasilitas', $f->id_fasilitas)
            ->whereHas('jenisSewa', fn ($q) => $q->where('satuan', $satuan))
            ->firstOrFail();
    }

    /** Reservasi aktif milik pemesan LAIN, langsung di database. */
    private function reservasiLain(Fasilitas $f, string $mulai, ?string $selesai = null, ?string $jamMulai = null, ?string $jamSelesai = null): Reservasi
    {
        $tarif = $this->tarif($f, $jamMulai ? 'Jam' : 'Hari');

        return Reservasi::create([
            'id_pemesan'       => Pemesan::factory()->create()->id_pemesan,
            'id_tarif_sewa'    => $tarif->id_tarif_sewa,
            'id_admin'         => null,
            'kode_reservasi'   => 'RSV-UJI-'.Str::upper(Str::random(8)),
            'kode_transaksi'   => 'RSV-UJI-'.Str::upper(Str::random(8)),
            'tanggal_mulai'    => $mulai,
            'tanggal_selesai'  => $selesai ?? $mulai,
            'jam_mulai'        => $jamMulai,
            'jam_selesai'      => $jamSelesai,
            'durasi'           => 1,
            'jumlah_pengguna'  => 1,
            'keperluan'        => 'Reservasi pemesan lain',
            'harga_satuan'     => $tarif->harga,
            'total_biaya'      => $tarif->harga,
            'status_reservasi' => 'Menunggu',
            'lock_status'      => 'pending_approval',
        ]);
    }

    private function keranjang(Fasilitas $f, string $satuan, array $jadwal, array $tambahan = [])
    {
        return $this->post('/reservasi/keranjang', array_merge([
            'id_fasilitas'    => $f->id_fasilitas,
            'id_tarif_sewa'   => $this->tarif($f, $satuan)->id_tarif_sewa,
            'jumlah_pengguna' => 2,
            'keperluan'       => 'Uji aturan reservasi',
        ], $jadwal, $tambahan));
    }

    /** Gabungan pesan error pada kunci tertentu dari session. */
    private function pesanError(string $kunci): string
    {
        return implode(' | ', session('errors')?->get($kunci) ?? []);
    }

    // ------------------------------------------------------------------
    // Kondisi fasilitas & aturan jenis sewa
    // ------------------------------------------------------------------

    public function test_aturan_jenis_sewa_per_kondisi(): void
    {
        $harap = [
            'hijau'  => ['Jam' => true, 'Hari' => true, 'Bulan' => true],
            'kuning' => ['Jam' => true, 'Hari' => false, 'Bulan' => false],
            'merah'  => ['Jam' => false, 'Hari' => false, 'Bulan' => false],
        ];

        foreach ($harap as $status => $perSatuan) {
            foreach ($perSatuan as $satuan => $bisa) {
                $this->assertSame(
                    $bisa,
                    AvailabilityService::bisaDipesan($status, SatuanSewa::from($satuan)),
                    "Status {$status}, sewa per {$satuan}",
                );
            }
        }

        $this->assertSame('Tersedia', AvailabilityService::labelStatus('hijau'));
        $this->assertSame('Sebagian Terisi', AvailabilityService::labelStatus('kuning'));
        $this->assertSame('Terisi', AvailabilityService::labelStatus('merah'));
    }

    public function test_kondisi_harian_dihitung_dari_jadwal_tersimpan(): void
    {
        $f = $this->fasilitas();
        $this->reservasiLain($f, $this->tgl(1), null, '10:00', '11:00');       // sebagian
        $this->reservasiLain($f, $this->tgl(2));                               // harian → penuh
        $this->reservasiLain($f, $this->tgl(3), null, '08:00', '12:00');       // 08–16 habis → penuh
        $this->reservasiLain($f, $this->tgl(3), null, '12:00', '16:00');

        $harian = app(AvailabilityService::class)->kondisiHarian($f->id_fasilitas, $this->tgl(0), $this->tgl(3), 'sesi-uji');

        $this->assertSame('bebas', $harian[$this->tgl(0)]['status']);
        $this->assertSame('sebagian', $harian[$this->tgl(1)]['status']);
        $this->assertSame([['mulai' => '10:00', 'selesai' => '11:00']], $harian[$this->tgl(1)]['jam']);
        $this->assertSame('penuh', $harian[$this->tgl(2)]['status']);
        $this->assertSame('penuh', $harian[$this->tgl(3)]['status']);
        // Rentang jam bersinggungan digabung.
        $this->assertSame([['mulai' => '08:00', 'selesai' => '16:00']], $harian[$this->tgl(3)]['jam']);

        // Reservasi yang sudah ditolak/dibatalkan tidak lagi memblokir.
        $batal = $this->reservasiLain($f, $this->tgl(4));
        $batal->update(['status_reservasi' => 'Dibatalkan']);
        $this->assertSame('bebas', app(AvailabilityService::class)->kondisiHarian($f->id_fasilitas, $this->tgl(4), $this->tgl(4), 'sesi-uji')[$this->tgl(4)]['status']);
    }

    // ------------------------------------------------------------------
    // Bentrok jadwal + pesan spesifik
    // ------------------------------------------------------------------

    public function test_per_jam_bentrok_menyebut_jam_terisi_dan_jam_kosong_tetap_bisa(): void
    {
        $f = $this->fasilitas();
        $tgl = $this->tgl(0);
        $this->reservasiLain($f, $tgl, null, '10:00', '11:00');

        $this->keranjang($f, 'Jam', ['tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00'])
            ->assertSessionHasErrors('jadwal');
        $pesan = $this->pesanError('jadwal');
        $this->assertStringContainsString('10.00–11.00', $pesan);
        $this->assertStringContainsString('09.00–11.00', $pesan);
        $this->assertEmpty(session('reservasi_cart', []));

        // Sebagian Terisi: jam yang masih kosong tetap dapat dipesan.
        $this->keranjang($f, 'Jam', ['tanggal_mulai' => $tgl, 'jam_mulai' => '13:00', 'jam_selesai' => '14:00'])
            ->assertRedirect(route('reservasi.checkout.form'));
        $this->assertCount(1, session('reservasi_cart'));
    }

    public function test_per_jam_pada_hari_yang_terisi_seharian_ditolak(): void
    {
        $f = $this->fasilitas();
        $tgl = $this->tgl(0);
        $this->reservasiLain($f, $tgl); // harian

        $this->keranjang($f, 'Jam', ['tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '10:00'])
            ->assertSessionHasErrors('jadwal');
        $this->assertStringContainsString('sudah terisi penuh', $this->pesanError('jadwal'));
    }

    public function test_per_hari_ditolak_pada_hari_sebagian_terisi(): void
    {
        $f = $this->fasilitas();
        $tgl = $this->tgl(1);
        $this->reservasiLain($f, $tgl, null, '10:00', '11:00');

        $this->keranjang($f, 'Hari', ['tanggal_mulai' => $this->tgl(0), 'tanggal_selesai' => $this->tgl(2)])
            ->assertSessionHasErrors('jadwal');
        $pesan = $this->pesanError('jadwal');
        $this->assertStringContainsString(AvailabilityService::ringkasTanggal([$tgl]), $pesan);
        $this->assertStringContainsString('jadwal sudah terisi pada tanggal', $pesan);

        // Hari lain yang kosong tetap bisa.
        $this->keranjang($f, 'Hari', ['tanggal_mulai' => $this->tgl(2), 'tanggal_selesai' => $this->tgl(3)])
            ->assertRedirect(route('reservasi.checkout.form'));
    }

    public function test_per_bulan_bentrok_menyebut_tanggal_spesifik(): void
    {
        Storage::fake('public');
        $f = $this->fasilitas();

        // Dua tanggal terpisah + satu rentang berurutan di tengah periode 3 bulan.
        $this->reservasiLain($f, $this->tgl(10));
        $this->reservasiLain($f, $this->tgl(15));
        $this->reservasiLain($f, $this->tgl(20), $this->tgl(22));
        $bentrok = [$this->tgl(10), $this->tgl(15), $this->tgl(20), $this->tgl(21), $this->tgl(22)];

        $this->keranjang($f, 'Bulan', [
            'tanggal_mulai'   => $this->tgl(0),
            'tanggal_selesai' => $this->senin->copy()->addMonthsNoOverflow(3)->toDateString(),
        ], ['dokumen' => [UploadedFile::fake()->create('a.pdf', 100, 'application/pdf')]])
            ->assertSessionHasErrors('jadwal');

        $this->assertStringContainsString(
            'Reservasi tidak dapat dilakukan karena terdapat jadwal sudah terisi pada tanggal '.AvailabilityService::ringkasTanggal($bentrok),
            $this->pesanError('jadwal'),
        );
        $this->assertEmpty(session('reservasi_cart', []));
    }

    public function test_ringkas_tanggal(): void
    {
        $this->assertSame(
            '10, 15, dan 20 Januari 2027',
            AvailabilityService::ringkasTanggal(['2027-01-15', '2027-01-10', '2027-01-20']),
        );
        $this->assertSame(
            '10–12 dan 15 Januari 2027',
            AvailabilityService::ringkasTanggal(['2027-01-10', '2027-01-11', '2027-01-12', '2027-01-15']),
        );
        $this->assertSame(
            '31 Januari 2027 serta 1 dan 3 Februari 2027',
            AvailabilityService::ringkasTanggal(['2027-01-31', '2027-02-01', '2027-02-03']),
        );
        $this->assertSame('5 Maret 2027', AvailabilityService::ringkasTanggal(['2027-03-05', '2027-03-05']));
    }

    // ------------------------------------------------------------------
    // Sewa bulanan: 3 bulan = minimum, bukan maksimum
    // ------------------------------------------------------------------

    public function test_hitung_bulan(): void
    {
        $this->assertSame(['penuh' => 3, 'sisa_hari' => 0, 'ditagih' => 3], CartService::hitungBulan('2027-01-01', '2027-04-01'));
        $this->assertSame(['penuh' => 4, 'sisa_hari' => 0, 'ditagih' => 4], CartService::hitungBulan('2027-01-01', '2027-05-01'));
        $this->assertSame(['penuh' => 3, 'sisa_hari' => 14, 'ditagih' => 4], CartService::hitungBulan('2027-01-01', '2027-04-15'));
        $this->assertSame(3, CartService::hitungBulan('2026-11-30', '2027-02-28')['ditagih']);
        $this->assertSame(12, CartService::hitungBulan('2027-01-01', '2028-01-01')['ditagih']);
        $this->assertSame(0, CartService::hitungBulan('2027-01-01', '2027-01-01')['ditagih']);
        $this->assertSame(0, CartService::hitungBulan('2027-02-01', '2027-01-01')['ditagih']);
    }

    public function test_sewa_bulanan_minimal_tiga_bulan_dan_boleh_lebih(): void
    {
        Storage::fake('public');
        $this->assertSame(3, (int) JenisSewa::where('satuan', 'Bulan')->value('durasi_minimum'));
        $dok = fn () => ['dokumen' => [UploadedFile::fake()->create('a.pdf', 100, 'application/pdf')]];

        foreach ([3, 4, 5, 12] as $bulan) {
            $f = $this->fasilitas();
            $this->keranjang($f, 'Bulan', [
                'tanggal_mulai'   => $this->tgl(0),
                'tanggal_selesai' => $this->senin->copy()->addMonthsNoOverflow($bulan)->toDateString(),
            ], $dok())->assertRedirect(route('reservasi.checkout.form'));

            $item = collect(session('reservasi_cart'))->last();
            $this->assertSame($f->id_fasilitas, $item['id_fasilitas']);
            $this->assertSame($bulan, $item['durasi'], "Durasi {$bulan} bulan");
            $this->assertSame((float) ($bulan * self::HARGA['Bulan']), (float) $item['total_biaya'], "Total {$bulan} bulan");
        }

        // Sisa hari di luar bulan penuh ditagih sebagai satu bulan: 3 bulan + 10 hari = 4 bulan.
        $f = $this->fasilitas();
        $this->keranjang($f, 'Bulan', [
            'tanggal_mulai'   => $this->tgl(0),
            'tanggal_selesai' => $this->senin->copy()->addMonthsNoOverflow(3)->addDays(10)->toDateString(),
        ], $dok())->assertRedirect(route('reservasi.checkout.form'));
        $item = collect(session('reservasi_cart'))->last();
        $this->assertSame(4, $item['durasi']);
        $this->assertSame(4.0 * self::HARGA['Bulan'], (float) $item['total_biaya']);

        // Kurang dari 3 bulan → ditolak pada kolom tanggal berakhir.
        $jumlah = count(session('reservasi_cart'));
        $f = $this->fasilitas();
        $this->keranjang($f, 'Bulan', [
            'tanggal_mulai'   => $this->tgl(0),
            'tanggal_selesai' => $this->senin->copy()->addMonthsNoOverflow(3)->subDay()->toDateString(),
        ], $dok())->assertSessionHasErrors('tanggal_selesai');
        $this->assertStringContainsString('minimal 3 bulan', $this->pesanError('tanggal_selesai'));
        $this->assertCount($jumlah, session('reservasi_cart'));
    }

    // ------------------------------------------------------------------
    // Jumlah pengguna vs kapasitas
    // ------------------------------------------------------------------

    public function test_jumlah_pengguna_dibatasi_kapasitas(): void
    {
        $f = $this->fasilitas(['Jam'], 5);
        $jadwal = ['tanggal_mulai' => $this->tgl(0), 'jam_mulai' => '09:00', 'jam_selesai' => '10:00'];

        $this->keranjang($f, 'Jam', $jadwal, ['jumlah_pengguna' => 6])->assertSessionHasErrors('jumlah_pengguna');
        $this->assertStringContainsString('5 orang', $this->pesanError('jumlah_pengguna'));
        $this->assertEmpty(session('reservasi_cart', []));

        $this->keranjang($f, 'Jam', $jadwal, ['jumlah_pengguna' => 0])->assertSessionHasErrors('jumlah_pengguna');

        $this->keranjang($f, 'Jam', $jadwal, ['jumlah_pengguna' => 5])->assertRedirect(route('reservasi.checkout.form'));
        $this->assertSame(5, session('reservasi_cart')[0]['jumlah_pengguna']);
    }

    public function test_multi_ruangan_memakai_kapasitas_terkecil(): void
    {
        $a = $this->fasilitas(['Jam'], 10);
        $b = $this->fasilitas(['Jam'], 4);
        $jadwal = ['tanggal_mulai' => $this->tgl(0), 'jam_mulai' => '09:00', 'jam_selesai' => '10:00', 'antrian' => (string) $b->id_fasilitas];

        $this->keranjang($a, 'Jam', $jadwal, ['jumlah_pengguna' => 5])->assertSessionHasErrors('jumlah_pengguna');
        $this->assertStringContainsString('4 orang', $this->pesanError('jumlah_pengguna'));
        $this->assertEmpty(session('reservasi_cart', []));

        $this->keranjang($a, 'Jam', $jadwal, ['jumlah_pengguna' => 4])->assertRedirect(route('reservasi.checkout.form'));
        $this->assertCount(2, session('reservasi_cart'));
    }

    // ------------------------------------------------------------------
    // Checkout mengecek ulang ketersediaan
    // ------------------------------------------------------------------

    public function test_checkout_mengecek_ulang_jadwal_sebelum_menyimpan(): void
    {
        $f = $this->fasilitas();
        $tgl = $this->tgl(0);

        $this->keranjang($f, 'Jam', ['tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00'])
            ->assertRedirect(route('reservasi.checkout.form'));

        // Sementara item masih di keranjang, jadwal itu keburu diisi reservasi lain.
        $this->reservasiLain($f, $tgl, null, '10:00', '12:00');

        $this->post('/reservasi/checkout')
            ->assertRedirect(route('reservasi.checkout.form'))
            ->assertSessionHasErrors('jadwal');
        $this->assertStringContainsString('10.00–12.00', $this->pesanError('jadwal'));
        $this->assertSame(0, Reservasi::where('id_pemesan', $this->pemesan->id_pemesan)->count());
        $this->assertCount(1, session('reservasi_cart'), 'Item tetap di keranjang supaya jadwalnya bisa diubah');
    }

    public function test_checkout_berhasil_bila_jadwal_masih_kosong(): void
    {
        $f = $this->fasilitas();
        $this->keranjang($f, 'Jam', ['tanggal_mulai' => $this->tgl(0), 'jam_mulai' => '09:00', 'jam_selesai' => '11:00'], ['jumlah_pengguna' => 7])
            ->assertRedirect(route('reservasi.checkout.form'));

        $this->post('/reservasi/checkout')->assertRedirect(route('customer.reservasi-saya.index'));

        $r = Reservasi::where('id_pemesan', $this->pemesan->id_pemesan)->firstOrFail();
        $this->assertSame(7, $r->jumlah_pengguna);
        $this->assertSame(2, $r->durasi);
        $this->assertSame(2.0 * self::HARGA['Jam'], (float) $r->total_biaya);
    }

    // ------------------------------------------------------------------
    // Endpoint pengecekan jadwal (AJAX) & halaman
    // ------------------------------------------------------------------

    public function test_endpoint_cek_jadwal(): void
    {
        $f = $this->fasilitas();
        $tgl = $this->tgl(0);
        $this->reservasiLain($f, $tgl, null, '10:00', '11:00');
        $url = route('reservasi.fasilitas.cek-jadwal', $f->id_fasilitas);

        // Jam kosong pada hari yang sebagian terisi.
        $this->getJson($url.'?'.http_build_query(['satuan' => 'Jam', 'tanggal_mulai' => $tgl, 'jam_mulai' => '13:00', 'jam_selesai' => '14:00']))
            ->assertOk()
            ->assertJson([
                'tersedia' => true,
                'pesan'    => [],
                'kondisi'  => ['status' => 'kuning', 'label' => 'Sebagian Terisi', 'bisa' => ['Jam' => true, 'Hari' => false, 'Bulan' => false]],
            ]);

        // Jam yang menabrak.
        $res = $this->getJson($url.'?'.http_build_query(['satuan' => 'Jam', 'tanggal_mulai' => $tgl, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00']))
            ->assertOk()->assertJson(['tersedia' => false]);
        $this->assertStringContainsString('10.00–11.00', $res->json('pesan.0'));

        // Per Hari pada hari sebagian terisi → tidak dapat dipesan.
        $this->getJson($url.'?'.http_build_query(['satuan' => 'Hari', 'tanggal_mulai' => $tgl, 'tanggal_selesai' => $tgl]))
            ->assertOk()->assertJson(['tersedia' => false]);
        // …bahkan sebelum tanggal selesai diisi.
        $this->getJson($url.'?'.http_build_query(['satuan' => 'Bulan', 'tanggal_mulai' => $tgl]))
            ->assertOk()->assertJson(['tersedia' => false]);

        // Hari kosong → semua jenis sewa dapat dipesan.
        $this->getJson($url.'?'.http_build_query(['satuan' => 'Hari', 'tanggal_mulai' => $this->tgl(1), 'tanggal_selesai' => $this->tgl(1)]))
            ->assertOk()
            ->assertJson(['tersedia' => true, 'kondisi' => ['status' => 'hijau', 'bisa' => ['Jam' => true, 'Hari' => true, 'Bulan' => true]]]);

        // Parameter tidak valid tidak boleh menimbulkan error.
        $this->getJson($url.'?tanggal_mulai=bukan-tanggal')->assertOk()->assertJson(['tersedia' => true]);
        $this->getJson($url.'?'.http_build_query(['satuan' => 'Jam', 'tanggal_mulai' => '2027-02-31', 'jam_mulai' => 'x']))->assertOk();
        $this->getJson($url.'?'.http_build_query(['satuan' => 'Aneh', 'tanggal_mulai' => $tgl, 'tanggal_selesai' => 'abc']))->assertOk();
    }

    public function test_jam_terisi_multi_ruangan_digabung(): void
    {
        $a = $this->fasilitas(['Jam', 'Hari']);
        $b = $this->fasilitas(['Jam', 'Hari']);
        $tgl = $this->tgl(0);
        $this->reservasiLain($a, $tgl, null, '09:00', '10:00');
        $this->reservasiLain($b, $tgl, null, '13:00', '14:00');

        $this->getJson(route('reservasi.fasilitas.jam-terisi', ['fasilitas' => $a->id_fasilitas, 'antrian' => $b->id_fasilitas, 'tanggal' => $tgl]))
            ->assertOk()
            ->assertExactJson([['mulai' => '09:00', 'selesai' => '10:00'], ['mulai' => '13:00', 'selesai' => '14:00']]);
    }

    public function test_halaman_detail_menampilkan_semua_harga_dan_kolom_jumlah_pengguna(): void
    {
        $a = $this->fasilitas(['Jam', 'Hari', 'Bulan'], 8);
        $b = $this->fasilitas(['Jam', 'Hari', 'Bulan'], 6);

        $this->get(route('reservasi.fasilitas.show', $a->id_fasilitas))
            ->assertOk()
            ->assertSee($a->nama_fasilitas)
            ->assertSee('Per Jam')->assertSee('Per Hari')->assertSee('Per Bulan')
            ->assertSee('Rp 50.000')->assertSee('Rp 400.000')->assertSee('Rp 1.000.000')
            ->assertSee('Jumlah Pengguna')
            ->assertSee('Kapasitas maksimal')
            ->assertSee('8 orang')
            ->assertSee('Tersedia');

        // Multi-ruangan: kedua ruangan tampil, kapasitas = yang terkecil, harga = total.
        $this->get(route('reservasi.fasilitas.show', ['fasilitas' => $a->id_fasilitas, 'antrian' => $b->id_fasilitas]))
            ->assertOk()
            ->assertSee($a->nama_fasilitas)->assertSee($b->nama_fasilitas)
            ->assertSee('2 Fasilitas Terpilih')
            ->assertSee('Rp 100.000')
            ->assertSee('name="jumlah_pengguna"', false)
            ->assertSee('max="6"', false);

        // Tanggal yang terisi seharian: semua jenis sewa ditutup.
        $this->reservasiLain($a, $this->tgl(0));
        $this->get(route('reservasi.fasilitas.show', ['fasilitas' => $a->id_fasilitas, 'tanggal_mulai' => $this->tgl(0)]))
            ->assertOk()->assertSee('Terisi')->assertSee('Tidak dapat dipesan');

        // Parameter tanggal tidak valid → tetap 200 (jatuh ke hari ini).
        $this->get(route('reservasi.fasilitas.show', ['fasilitas' => $a->id_fasilitas, 'tanggal_mulai' => 'abc']))->assertOk();
    }

    public function test_denah_terbuka_dengan_dan_tanpa_jenis_sewa(): void
    {
        $f = $this->fasilitas();
        $param = ['kategori' => $f->kategori_fasilitas, 'lantai' => $f->id_lantai];

        $this->get(route('reservasi.denah', $param))->assertOk()->assertSee('Sebagian Terisi');
        foreach (['Jam', 'Hari', 'Bulan'] as $satuan) {
            $this->get(route('reservasi.denah', $param + ['jenis' => $this->tarif($f, $satuan)->id_jenis_sewa]))->assertOk();
        }
        $this->get(route('reservasi.denah', $param + ['tanggal_mulai' => 'abc', 'tanggal_selesai' => '2020-01-01']))->assertOk();
    }
}
