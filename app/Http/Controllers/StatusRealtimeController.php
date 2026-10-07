<?php

namespace App\Http\Controllers;

use App\Enums\StatusReservasi;
use App\Models\Reservasi;
use App\Models\RiwayatStatus;
use App\Services\StatusOtomatisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatusRealtimeController extends Controller
{
    /** Batas notifikasi per putaran polling, supaya respons tetap kecil walau lama tidak dibuka. */
    private const MAKS_NOTIFIKASI = 5;

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

    /**
     * Ringkasan realtime untuk partials/pantau-status: sidik jari data, angka badge, dan
     * notifikasi baru sejak kursor terakhir (r = id_reservasi, h = id_riwayat). Isi badge &
     * notifikasi hanya untuk peran yang benar-benar login pada guard-nya; tamu hanya
     * menerima sidik jari.
     */
    public function ringkasan(Request $request, StatusOtomatisService $layanan): JsonResponse
    {
        $kursorR = max(0, (int) $request->query('r'));
        $kursorH = max(0, (int) $request->query('h'));
        $data = ['versi' => $layanan->versi()];

        if ($request->query('peran') === 'admin' && Auth::guard('admin')->check()) {
            $data += $this->untukAdmin($kursorR, $kursorH);
        } elseif ($request->query('peran') === 'pemesan' && Auth::guard('customer')->check()) {
            $data += $this->untukPemesan((int) Auth::guard('customer')->id(), $kursorH);
        }

        return response()->json($data)->header('Cache-Control', 'no-store');
    }

    /** Kursor awal yang dirender bersama halaman, supaya notifikasi hanya untuk kejadian sesudahnya. */
    public static function kursorAwal(): array
    {
        return [
            'r' => (int) Reservasi::max('id_reservasi'),
            'h' => (int) RiwayatStatus::max('id_riwayat'),
        ];
    }

    private function untukAdmin(int $kursorR, int $kursorH): array
    {
        $notifikasi = [];

        // Pengajuan baru — satu notifikasi per pemesanan (kode_transaksi), bukan per ruangan.
        if ($kursorR > 0) {
            Reservasi::query()
                ->with(['pemesan', 'tarifSewa.fasilitas'])
                ->where('id_reservasi', '>', $kursorR)
                ->where('status_reservasi', StatusReservasi::Menunggu->value)
                ->orderBy('id_reservasi')
                ->get()
                ->groupBy('kode_transaksi')
                ->take(self::MAKS_NOTIFIKASI)
                ->each(function ($grup) use (&$notifikasi) {
                    $r = $grup->first();
                    $ruangan = $grup->count() > 1
                        ? $grup->count().' ruangan'
                        : $r->tarifSewa->fasilitas->nama_fasilitas;
                    $notifikasi[] = [
                        'jenis' => 'info',
                        'judul' => 'Pengajuan reservasi baru',
                        'pesan' => ($r->pemesan?->nama_lengkap ?? 'Pemesan').' mengajukan '.$ruangan.' ('.$r->kode_transaksi.').',
                        'url'   => route('admin.reservasi.show', $r->kode_reservasi),
                    ];
                });
        }

        // Pembatalan oleh pemesan (perubahan status tanpa admin, selain transisi otomatis).
        if ($kursorH > 0) {
            RiwayatStatus::query()
                ->with('reservasi')
                ->where('id_riwayat', '>', $kursorH)
                ->whereNull('id_admin')
                ->where('status_baru', StatusReservasi::Dibatalkan->value)
                ->orderBy('id_riwayat')
                ->limit(self::MAKS_NOTIFIKASI)
                ->get()
                ->each(function (RiwayatStatus $h) use (&$notifikasi) {
                    $notifikasi[] = [
                        'jenis' => 'peringatan',
                        'judul' => 'Reservasi dibatalkan pemesan',
                        'pesan' => 'Reservasi '.$h->reservasi->kode_reservasi.' telah dibatalkan oleh pemesan.',
                        'url'   => route('admin.reservasi.show', $h->reservasi->kode_reservasi),
                    ];
                });
        }

        return [
            'badge'      => ['menunggu' => Reservasi::where('status_reservasi', StatusReservasi::Menunggu->value)->count()],
            'notifikasi' => $notifikasi,
            'kursor'     => self::kursorAwal(),
        ];
    }

    private function untukPemesan(int $idPemesan, int $kursorH): array
    {
        $notifikasi = [];

        if ($kursorH > 0) {
            $judul = [
                StatusReservasi::Disetujui->value   => ['sukses', 'Reservasi disetujui'],
                StatusReservasi::Ditolak->value     => ['bahaya', 'Reservasi ditolak'],
                StatusReservasi::Selesai->value     => ['info', 'Masa penggunaan selesai'],
                StatusReservasi::Kadaluwarsa->value => ['peringatan', 'Reservasi kedaluwarsa'],
                StatusReservasi::Dibatalkan->value  => ['peringatan', 'Reservasi dibatalkan'],
            ];

            RiwayatStatus::query()
                ->with('reservasi.tarifSewa.fasilitas')
                ->where('id_riwayat', '>', $kursorH)
                ->whereHas('reservasi', fn ($q) => $q->where('id_pemesan', $idPemesan))
                // Pembatalan yang dilakukan pemesan sendiri tidak perlu diberitahukan kembali.
                ->where(fn ($q) => $q->whereNotNull('id_admin')
                    ->orWhere('status_baru', '!=', StatusReservasi::Dibatalkan->value))
                ->orderBy('id_riwayat')
                ->limit(self::MAKS_NOTIFIKASI)
                ->get()
                ->each(function (RiwayatStatus $h) use (&$notifikasi, $judul) {
                    [$jenis, $teks] = $judul[$h->status_baru->value] ?? ['info', 'Status reservasi diperbarui'];
                    $notifikasi[] = [
                        'jenis' => $jenis,
                        'judul' => $teks,
                        'pesan' => $h->reservasi->tarifSewa->fasilitas->nama_fasilitas
                            .' ('.$h->reservasi->kode_reservasi.') kini berstatus '.$h->status_baru->value.'.',
                        'url'   => route('customer.reservasi-saya.show', $h->reservasi->kode_reservasi),
                    ];
                });
        }

        return [
            'notifikasi' => $notifikasi,
            'kursor'     => self::kursorAwal(),
        ];
    }
}
