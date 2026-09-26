<?php

namespace App\Services;

use App\Enums\StatusAktif;
use App\Enums\StatusReservasi;
use App\Models\Fasilitas;
use App\Models\Reservasi;
use Illuminate\Support\Carbon;

/**
 * Perhitungan tingkat okupansi fasilitas — dipakai bersama oleh dashboard Admin & Pemesan
 * supaya keduanya selalu menampilkan angka yang sama persis (satu sumber kebenaran).
 *
 * "Terisi" = ada reservasi Disetujui/Selesai yang mencakup tanggal itu (Selesai ikut dihitung
 * untuk hari-hari yang sudah lewat, supaya okupansi masa lalu tidak surut hanya karena
 * statusnya sudah berubah begitu masa pakainya berakhir).
 */
class OkupansiService
{
    /**
     * @return array{
     *     totalRuanganAktif: int,
     *     harian: array<int, array{tanggal: Carbon, label: string, terisi: int, pct: int, isAktif: bool}>,
     *     bulanan: array<int, array{tanggal: Carbon, label: string, pct: int, isAktif: bool}>,
     *     perRuangan: \Illuminate\Support\Collection<int, array{nama: string, lantai: string, terisi: int, pct: int}>,
     * }
     */
    public function hitung(): array
    {
        $totalRuanganAktif = Fasilitas::where('status_aktif', StatusAktif::Aktif->value)->count();

        $rentangMulai = now()->startOfMonth()->subMonths(11)->startOfDay();
        $rentangAkhir = now()->endOfDay();

        $baris = Reservasi::query()
            ->join('tarif_sewa', 'tarif_sewa.id_tarif_sewa', '=', 'reservasi.id_tarif_sewa')
            ->whereIn('reservasi.status_reservasi', [StatusReservasi::Disetujui->value, StatusReservasi::Selesai->value])
            ->whereDate('reservasi.tanggal_mulai', '<=', $rentangAkhir)
            ->whereDate('reservasi.tanggal_selesai', '>=', $rentangMulai)
            ->get(['tarif_sewa.id_fasilitas as id_fasilitas', 'reservasi.tanggal_mulai', 'reservasi.tanggal_selesai']);

        // Peta tanggal → himpunan id_fasilitas yang terisi hari itu (dibangun sekali, dipakai
        // ulang untuk ketiga sudut pandang di bawah supaya tidak query berulang-ulang).
        $peta = [];
        foreach ($baris as $row) {
            $mulai = $row->tanggal_mulai->copy()->startOfDay()->max($rentangMulai);
            $selesai = $row->tanggal_selesai->copy()->startOfDay()->min($rentangAkhir);
            for ($d = $mulai->copy(); $d->lte($selesai); $d->addDay()) {
                $peta[$d->toDateString()][$row->id_fasilitas] = true;
            }
        }

        $harian = collect(range(0, 6))->map(function ($i) use ($peta, $totalRuanganAktif) {
            $tgl = now()->startOfDay()->subDays(6 - $i);
            $terisi = count($peta[$tgl->toDateString()] ?? []);

            return [
                'tanggal' => $tgl,
                'label'   => $tgl->translatedFormat('D'),
                'terisi'  => $terisi,
                'pct'     => $totalRuanganAktif > 0 ? (int) round($terisi / $totalRuanganAktif * 100) : 0,
                'isAktif' => $tgl->isToday(),
            ];
        })->all();

        $bulanan = collect(range(0, 11))->map(function ($i) use ($rentangMulai, $peta, $totalRuanganAktif) {
            $bln = $rentangMulai->copy()->addMonths($i);
            $awal = $bln->copy()->startOfMonth();
            $akhir = $bln->isCurrentMonth() ? now()->startOfDay() : $bln->copy()->endOfMonth();
            $jumlahHari = $awal->diffInDays($akhir) + 1;

            $akumulasi = 0;
            for ($d = $awal->copy(); $d->lte($akhir); $d->addDay()) {
                $akumulasi += count($peta[$d->toDateString()] ?? []);
            }

            $pct = ($totalRuanganAktif > 0 && $jumlahHari > 0)
                ? (int) round($akumulasi / ($totalRuanganAktif * $jumlahHari) * 100)
                : 0;

            return [
                'tanggal' => $bln,
                'label'   => $bln->translatedFormat('M'),
                'pct'     => $pct,
                'isAktif' => $bln->isCurrentMonth(),
            ];
        })->all();

        $hariBerjalanBulanIni = now()->day;
        $perRuangan = Fasilitas::where('status_aktif', StatusAktif::Aktif->value)
            ->with('lantai')
            ->orderBy('nama_fasilitas')
            ->get()
            ->map(function (Fasilitas $f) use ($peta, $hariBerjalanBulanIni) {
                $terisi = 0;
                for ($d = now()->startOfMonth(); $d->lte(now()->startOfDay()); $d->addDay()) {
                    if (! empty($peta[$d->toDateString()][$f->id_fasilitas])) {
                        $terisi++;
                    }
                }

                return [
                    'nama'   => $f->nama_fasilitas,
                    'lantai' => $f->lantai->nomor_lantai ?? '-',
                    'terisi' => $terisi,
                    'pct'    => $hariBerjalanBulanIni > 0 ? (int) round($terisi / $hariBerjalanBulanIni * 100) : 0,
                ];
            })
            ->sortByDesc('pct')
            ->values();

        return [
            'totalRuanganAktif' => $totalRuanganAktif,
            'harian'            => $harian,
            'bulanan'           => $bulanan,
            'perRuangan'        => $perRuangan,
        ];
    }
}
