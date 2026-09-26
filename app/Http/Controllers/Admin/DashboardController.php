<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusReservasi;
use App\Http\Controllers\Controller;
use App\Models\Reservasi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        // Periode chart tren: 'harian' (7 hari terakhir) atau 'bulanan' (12 bulan terakhir).
        // Dipilih lewat query string ?periode=, default 'harian'. Nilai tak dikenal → fallback harian.
        $periode = request('periode', 'harian');
        $periode = in_array($periode, ['harian', 'bulanan'], true) ? $periode : 'harian';

        // ══════════════ 6 CARD STATUS — di-scope PER BULAN (?status_bulan=Y-m, default bulan
        //    berjalan), supaya angka & persentasenya mulai dari 0 lagi tiap ganti bulan. ══════════════
        $statBulanInput = request('status_bulan');
        $statBulan = $statBulanInput
            ? Carbon::createFromFormat('Y-m', $statBulanInput)->startOfMonth()
            : now()->startOfMonth();

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
            'label'     => $statBulan->translatedFormat('F Y'),
            'prev'      => $statBulan->copy()->subMonth()->format('Y-m'),
            'next'      => $statBulan->copy()->addMonth()->format('Y-m'),
            'isCurrent' => $statBulan->isSameMonth(now()),
        ];

        // Antrian di hero SELALU seluruh waktu (bukan ikut ter-scope bulan seperti $statistik
        // di atas) — ini working queue admin sekarang, bukan riwayat bulan yang sedang dilihat.
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

        // Tren reservasi untuk bar chart di dashboard — dua mode:
        // - harian : 7 hari terakhir (termasuk hari ini), label "Sen, Sel, ..."
        // - bulanan: 12 bulan terakhir (termasuk bulan ini), label "Jan, Feb, ..."
        // Beda dengan panel kategori (yang cuma menghitung Disetujui): tren riwayat pemakaian
        // ini tetap menghitung reservasi yang sudah Selesai, supaya angkanya tidak berkurang
        // begitu masa pakainya berakhir — riwayat bulan/hari yang sudah lewat harus tetap utuh.
        // Selalu di-pad ke seluruh rentang supaya jumlah bar tetap konsisten meskipun
        // ada hari/bulan tanpa reservasi sama sekali.
        $statusAktif = [StatusReservasi::Disetujui->value, StatusReservasi::Selesai->value];

        if ($periode === 'bulanan') {
            $mulai = now()->startOfMonth()->subMonths(11);

            $countPerBulan = Reservasi::query()
                ->whereIn('status_reservasi', $statusAktif)
                ->where('created_at', '>=', $mulai)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bln, COUNT(*) as jumlah")
                ->groupBy('bln')
                ->pluck('jumlah', 'bln');

            $trendChart = collect(range(0, 11))->map(function ($i) use ($mulai, $countPerBulan) {
                $bln = $mulai->copy()->addMonths($i);
                $key = $bln->format('Y-m');

                return [
                    'tanggal' => $bln,
                    'label'   => $bln->translatedFormat('M'),   // Jan, Feb, ...
                    'jumlah'  => (int) ($countPerBulan[$key] ?? 0),
                    'isAktif' => $bln->isCurrentMonth(),
                ];
            })->all();
        } else {
            $mulai = now()->startOfDay()->subDays(6);

            $countPerHari = Reservasi::query()
                ->whereIn('status_reservasi', $statusAktif)
                ->where('created_at', '>=', $mulai)
                ->selectRaw('DATE(created_at) as tgl, COUNT(*) as jumlah')
                ->groupBy('tgl')
                ->pluck('jumlah', 'tgl');

            $trendChart = collect(range(0, 6))->map(function ($i) use ($mulai, $countPerHari) {
                $tgl = $mulai->copy()->addDays($i);
                $key = $tgl->toDateString();

                return [
                    'tanggal' => $tgl,
                    'label'   => $tgl->translatedFormat('D'),   // Sen, Sel, ...
                    'jumlah'  => (int) ($countPerHari[$key] ?? 0),
                    'isAktif' => $tgl->isToday(),
                ];
            })->all();
        }

        // ══════════════ KALENDER RESERVASI — sama seperti dashboard Pemesan, hanya
        //    yang berstatus Disetujui, tapi lintas semua pemesan (lihat partials.kalender-reservasi). ══════════════
        $rangeKalender = \App\Support\KalenderReservasi::range(request('view'), request('tanggal'), request('bulan'));
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
            'statistik', 'statBulanNav', 'menungguSekarang', 'reservasiPerKategori', 'terbaru', 'trendChart', 'periode',
            'rangeKalender', 'reservasiKalender',
        ));
    }
}