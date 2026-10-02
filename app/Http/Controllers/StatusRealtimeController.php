<?php

namespace App\Http\Controllers;

use App\Services\StatusOtomatisService;
use Illuminate\Http\JsonResponse;

class StatusRealtimeController extends Controller
{
    /**
     * Sidik jari data reservasi untuk halaman yang memantau perubahan status. Hanya berisi
     * hash (tanpa data reservasi apa pun), jadi aman diakses publik. Request ke sini juga
     * melewati middleware JalankanStatusOtomatis, sehingga polling dari halaman yang sedang
     * terbuka sekaligus memicu transisi Selesai/Kadaluwarsa tepat waktu.
     */
    public function versi(StatusOtomatisService $layanan): JsonResponse
    {
        return response()
            ->json(['versi' => $layanan->versi()])
            ->header('Cache-Control', 'no-store');
    }
}
