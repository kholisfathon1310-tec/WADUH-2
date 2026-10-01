<?php

namespace Tests\Concerns;

use App\Models\Pemesan;
use Illuminate\Support\Carbon;

/**
 * Pembantu tes alur Pemesan: seluruh rute /reservasi/* wajib login guard `customer`, dan form
 * "Atur Jadwal" selalu mengirim data profil pemesan bersama jadwalnya.
 */
trait BantuPemesan
{
    protected ?Pemesan $pemesan = null;

    /** Login sebagai Pemesan berprofil lengkap (dibuat di dalam transaksi tes). */
    protected function loginPemesan(array $override = []): Pemesan
    {
        $this->pemesan = Pemesan::factory()->create(array_merge([
            'nama_lengkap' => 'Rina Pemesan',
            'alamat'       => 'Jl. Uji No. 1, Cimahi',
            'usia'         => 28,
            'pekerjaan'    => 'Wirausaha',
            'no_telepon'   => '081234567890',
        ], $override));

        $this->actingAs($this->pemesan, 'customer');

        return $this->pemesan;
    }

    /**
     * POST keranjang selalu membawa data profil pemesan, sama seperti form "Atur Jadwal".
     * Setelahnya cookie session dipertahankan: tanpa itu tiap request tes mendapat session ID
     * baru, sehingga hold keranjang milik sendiri terbaca sebagai hold "session lain"
     * (di browser sungguhan ID session memang tetap sama).
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        if ($uri !== '/reservasi/keranjang' || $this->pemesan === null) {
            return parent::post($uri, $data, $headers);
        }

        $respons = parent::post($uri, $data + $this->profil(), $headers);
        $this->withCookie(config('session.cookie'), session()->getId());

        return $respons;
    }

    /** Data profil yang ikut terkirim pada POST /reservasi/keranjang. */
    protected function profil(): array
    {
        return [
            'nama_lengkap' => $this->pemesan->nama_lengkap,
            'alamat'       => $this->pemesan->alamat,
            'usia'         => $this->pemesan->usia,
            'pekerjaan'    => $this->pemesan->pekerjaan,
            'no_telepon'   => $this->pemesan->no_telepon,
        ];
    }

    /**
     * Tanggal (Y-m-d) hari kerja pertama pada atau setelah hari ini + $offset hari — gedung
     * tidak beroperasi Sabtu & Minggu, jadi tanggal uji tidak boleh jatuh di akhir pekan.
     */
    protected function hariKerja(int $offset = 0): string
    {
        $tgl = Carbon::today()->addDays($offset);
        while ($tgl->isWeekend()) {
            $tgl->addDay();
        }

        return $tgl->toDateString();
    }
}
