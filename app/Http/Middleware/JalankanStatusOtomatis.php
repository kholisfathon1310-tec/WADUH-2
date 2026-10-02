<?php

namespace App\Http\Middleware;

use App\Services\StatusOtomatisService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjalankan perubahan status berbasis waktu (Selesai/Kadaluwarsa) sebelum request web
 * diproses, supaya halaman selalu menampilkan status terkini walau scheduler
 * (`php artisan schedule:run` via cron/Task Scheduler) tidak berjalan.
 *
 * Dibatasi paling sering sekali per JEDA_DETIK agar tidak membebani setiap request.
 */
class JalankanStatusOtomatis
{
    private const JEDA_DETIK = 10;

    public function __construct(private readonly StatusOtomatisService $layanan)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Cache::add hanya berhasil bila kunci belum ada -> hanya satu request per jeda yang lolos.
        if (Cache::add('reservasi:status-otomatis:terakhir', now()->timestamp, self::JEDA_DETIK)) {
            try {
                $this->layanan->jalankan();
            } catch (\Throwable $e) {
                // Kegagalan pembaruan otomatis tidak boleh menggagalkan halaman yang diminta.
                Log::error('Gagal memperbarui status reservasi otomatis: '.$e->getMessage(), ['exception' => $e]);
            }
        }

        return $next($request);
    }
}
