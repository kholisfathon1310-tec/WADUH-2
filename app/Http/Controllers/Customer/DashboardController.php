<?php

namespace App\Http\Controllers\Customer;

use App\Enums\StatusReservasi;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $pemesan = Auth::guard('customer')->user();

        $reservasi = $pemesan->reservasi()->with('tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa');

        $jumlahPerStatus = (clone $reservasi)->selectRaw('status_reservasi, count(*) as total')
            ->groupBy('status_reservasi')
            ->pluck('total', 'status_reservasi')
            ->mapWithKeys(fn ($total, $status) => [$status instanceof StatusReservasi ? $status->value : $status => $total]);

        $berjalan = (clone $reservasi)
            ->whereIn('status_reservasi', [StatusReservasi::Menunggu->value, StatusReservasi::Disetujui->value])
            ->latest('created_at')
            ->take(5)
            ->get();

        $terbaru = (clone $reservasi)->latest('created_at')->take(5)->get();

        return view('customer.dashboard', [
            'pemesan'         => $pemesan,
            'totalReservasi'  => (clone $reservasi)->count(),
            'jumlahPerStatus' => $jumlahPerStatus,
            'berjalan'        => $berjalan,
            'terbaru'         => $terbaru,
        ]);
    }
}
