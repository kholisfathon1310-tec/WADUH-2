<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Hitung rentang tanggal & navigasi kalender (Hari/Minggu/Bulan) — dipakai bersama oleh
 * dashboard Pemesan & Admin supaya kalender reservasi di kedua sisi identik.
 */
class KalenderReservasi
{
    /**
     * @return array{view: string, tanggalAcuan: Carbon, rentangAwal: Carbon, rentangAkhir: Carbon,
     *               labelHeader: string, navPrev: string, navNext: string, paramNav: string}
     */
    public static function range(?string $view, ?string $tanggal, ?string $bulan): array
    {
        $view = in_array($view, ['hari', 'minggu', 'bulan']) ? $view : 'bulan';

        $tanggalAcuan = $tanggal
            ? Carbon::parse($tanggal)
            : ($bulan ? Carbon::createFromFormat('Y-m', $bulan)->startOfMonth() : now());

        if ($view === 'hari') {
            $rentangAwal = $tanggalAcuan->copy()->startOfDay();
            $rentangAkhir = $tanggalAcuan->copy()->endOfDay();
            $labelHeader = $tanggalAcuan->translatedFormat('l, d F Y');
        } elseif ($view === 'minggu') {
            $rentangAwal = $tanggalAcuan->copy()->startOfWeek(Carbon::MONDAY);
            $rentangAkhir = $tanggalAcuan->copy()->endOfWeek(Carbon::SUNDAY);
            $labelHeader = $rentangAwal->translatedFormat('d M').' – '.$rentangAkhir->translatedFormat('d M Y');
        } else {
            $bulanAktif = $tanggalAcuan->copy()->startOfMonth();
            $rentangAwal = $bulanAktif->copy()->startOfWeek(Carbon::MONDAY);
            $rentangAkhir = $bulanAktif->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
            $labelHeader = $bulanAktif->translatedFormat('F Y');
        }

        $navPrev = match ($view) {
            'hari' => $tanggalAcuan->copy()->subDay()->toDateString(),
            'minggu' => $tanggalAcuan->copy()->subWeek()->toDateString(),
            default => $tanggalAcuan->copy()->subMonth()->format('Y-m'),
        };
        $navNext = match ($view) {
            'hari' => $tanggalAcuan->copy()->addDay()->toDateString(),
            'minggu' => $tanggalAcuan->copy()->addWeek()->toDateString(),
            default => $tanggalAcuan->copy()->addMonth()->format('Y-m'),
        };

        return [
            'view'         => $view,
            'tanggalAcuan' => $tanggalAcuan,
            'rentangAwal'  => $rentangAwal,
            'rentangAkhir' => $rentangAkhir,
            'labelHeader'  => $labelHeader,
            'navPrev'      => $navPrev,
            'navNext'      => $navNext,
            'paramNav'     => $view === 'bulan' ? 'bulan' : 'tanggal',
        ];
    }
}
