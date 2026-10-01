<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Hitung rentang tanggal & navigasi kalender (Hari/Minggu/Bulan) — dipakai bersama oleh
 * dashboard Pemesan & Admin supaya kalender reservasi di kedua sisi identik.
 */
class KalenderReservasi
{
    /** Parameter bulan "Y-m" dari query string; null bila kosong atau tidak valid. */
    public static function parseBulan(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || ! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $nilai, $m)) {
            return null;
        }
        if ((int) $m[1] < 2000 || (int) $m[1] > 2100) {
            return null;
        }

        return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
    }

    /** Parameter tanggal "Y-m-d" dari query string; null bila kosong atau tidak valid. */
    public static function parseTanggal(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $nilai, $m)) {
            return null;
        }
        if ((int) $m[1] < 2000 || (int) $m[1] > 2100 || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return Carbon::create((int) $m[1], (int) $m[2], (int) $m[3])->startOfDay();
    }

    /**
     * @return array{view: string, tanggalAcuan: Carbon, rentangAwal: Carbon, rentangAkhir: Carbon,
     *               labelHeader: string, navPrev: string, navNext: string, paramNav: string}
     */
    public static function range(mixed $view, mixed $tanggal, mixed $bulan): array
    {
        $view = in_array($view, ['hari', 'minggu', 'bulan'], true) ? $view : 'bulan';

        $tanggalAcuan = self::parseTanggal($tanggal) ?? self::parseBulan($bulan) ?? now();

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
            default => $tanggalAcuan->copy()->startOfMonth()->subMonth()->format('Y-m'),
        };
        $navNext = match ($view) {
            'hari' => $tanggalAcuan->copy()->addDay()->toDateString(),
            'minggu' => $tanggalAcuan->copy()->addWeek()->toDateString(),
            default => $tanggalAcuan->copy()->startOfMonth()->addMonth()->format('Y-m'),
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
