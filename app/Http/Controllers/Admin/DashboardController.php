<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusReservasi;
use App\Http\Controllers\Controller;
use App\Models\Reservasi;
use App\Services\OkupansiService;
use App\Support\KalenderReservasi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OkupansiService $okupansiService): View
    {
        // ══════════════ OKUPANSI — diagram 12 bulan (?okupansi_tahun=) + rincian per fasilitas
        //    untuk satu bulan terpilih (?okupansi_bulan=Y-m, default bulan berjalan). Parameter
        //    yang tidak valid jatuh ke default. ══════════════
        $bulanDiminta = KalenderReservasi::parseBulan($request->query('okupansi_bulan'));
        $tahunInput = $request->query('okupansi_tahun');
        $tahunDiminta = is_string($tahunInput) && preg_match('/^\d{4}$/', $tahunInput)
            && (int) $tahunInput >= 2000 && (int) $tahunInput <= 2100
            ? (int) $tahunInput
            : null;

        $okupansiTahun = $tahunDiminta ?? $bulanDiminta?->year ?? now()->year;
        $okupansiBulan = ($bulanDiminta ?? now())->copy()->startOfMonth()->setYear($okupansiTahun)->startOfDay();

        $okupansiBulanan = $okupansiService->bulananTahun($okupansiTahun);
        $okupansiFasilitas = $okupansiService->perFasilitas($okupansiBulan);

        $bulanSebelum = $okupansiBulan->copy()->subMonth();
        $bulanSesudah = $okupansiBulan->copy()->addMonth();
        $okupansiNav = [
            'tahun'      => $okupansiTahun,
            'bulanKunci' => $okupansiBulan->format('Y-m'),
            'bulanLabel' => $okupansiBulan->translatedFormat('F Y'),
            'tahunPrev'  => ['okupansi_tahun' => $okupansiTahun - 1, 'okupansi_bulan' => $okupansiBulan->copy()->subYear()->format('Y-m')],
            'tahunNext'  => ['okupansi_tahun' => $okupansiTahun + 1, 'okupansi_bulan' => $okupansiBulan->copy()->addYear()->format('Y-m')],
            'bulanPrev'  => ['okupansi_tahun' => $bulanSebelum->year, 'okupansi_bulan' => $bulanSebelum->format('Y-m')],
            'bulanNext'  => ['okupansi_tahun' => $bulanSesudah->year, 'okupansi_bulan' => $bulanSesudah->format('Y-m')],
        ];

        // ══════════════ DISTRIBUSI STATUS — jumlah reservasi per status yang DIAJUKAN pada satu
        //    bulan (?status_bulan=Y-m, default bulan berjalan). ══════════════
        $statBulan = KalenderReservasi::parseBulan($request->query('status_bulan')) ?? now()->startOfMonth();

        $perStatus = Reservasi::query()
            ->whereYear('created_at', $statBulan->year)
            ->whereMonth('created_at', $statBulan->month)
            ->selectRaw('status_reservasi, COUNT(*) as jumlah')
            ->groupBy('status_reservasi')
            ->pluck('jumlah', 'status_reservasi');

        $statistik = [
            'total'      => (int) $perStatus->sum(),
            'menunggu'   => (int) $perStatus->get(StatusReservasi::Menunggu->value, 0),
            'disetujui'  => (int) $perStatus->get(StatusReservasi::Disetujui->value, 0),
            'selesai'    => (int) $perStatus->get(StatusReservasi::Selesai->value, 0),
            'ditolak'    => (int) $perStatus->get(StatusReservasi::Ditolak->value, 0),
            'dibatalkan' => (int) $perStatus->get(StatusReservasi::Dibatalkan->value, 0),
            'kadaluwarsa' => (int) $perStatus->get(StatusReservasi::Kadaluwarsa->value, 0),
        ];

        $statBulanNav = [
            'label' => $statBulan->translatedFormat('F Y'),
            'prev'  => $statBulan->copy()->subMonth()->format('Y-m'),
            'next'  => $statBulan->copy()->addMonth()->format('Y-m'),
        ];

        // Antrean di hero SELALU seluruh waktu (bukan ikut ter-scope bulan seperti $statistik
        // di atas) — ini antrean kerja admin sekarang, bukan riwayat bulan yang sedang dilihat.
        $menungguSekarang = (int) Reservasi::where('status_reservasi', StatusReservasi::Menunggu->value)->count();

        // Jumlah RESERVASI per kategori fasilitas — snapshot yang SEDANG BERLANGSUNG (Disetujui
        // saja). Begitu selesai dipakai (status Selesai), reservasinya lepas dari hitungan ini
        // supaya panel ini selalu mencerminkan pemakaian aktif saat ini, bukan riwayat.
        $reservasiPerKategori = Reservasi::query()
            ->where('status_reservasi', StatusReservasi::Disetujui->value)
            ->join('tarif_sewa', 'reservasi.id_tarif_sewa', '=', 'tarif_sewa.id_tarif_sewa')
            ->join('fasilitas', 'tarif_sewa.id_fasilitas', '=', 'fasilitas.id_fasilitas')
            ->selectRaw('fasilitas.kategori_fasilitas, COUNT(*) as jumlah')
            ->groupBy('fasilitas.kategori_fasilitas')
            ->orderBy('fasilitas.kategori_fasilitas')
            ->pluck('jumlah', 'kategori_fasilitas');

        $terbaru = Reservasi::query()
            ->with(['pemesan', 'tarifSewa.fasilitas'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        // ══════════════ KALENDER RESERVASI — sama seperti dashboard Pemesan, hanya
        //    yang berstatus Disetujui, tapi lintas semua pemesan (lihat partials.kalender-reservasi). ══════════════
        $rangeKalender = KalenderReservasi::range($request->query('view'), $request->query('tanggal'), $request->query('bulan'));
        $reservasiKalender = Reservasi::query()
            ->with('tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa')
            ->where('status_reservasi', StatusReservasi::Disetujui->value)
            ->whereBetween('tanggal_mulai', [
                $rangeKalender['rentangAwal']->toDateString(),
                $rangeKalender['rentangAkhir']->toDateString(),
            ])
            ->orderBy('tanggal_mulai')
            ->orderBy('jam_mulai')
            ->get();

        return view('admin.dashboard', compact(
            'okupansiBulanan', 'okupansiFasilitas', 'okupansiNav',
            'statistik', 'statBulanNav', 'menungguSekarang', 'reservasiPerKategori', 'terbaru',
            'rangeKalender', 'reservasiKalender',
        ));
    }
}
