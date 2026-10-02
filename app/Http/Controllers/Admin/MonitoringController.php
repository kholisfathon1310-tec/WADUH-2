<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusAktif;
use App\Enums\StatusReservasi;
use App\Http\Controllers\Controller;
use App\Models\Fasilitas;
use App\Models\Lantai;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability)
    {
    }

    public function index(Request $request): View
    {
        $daftarLantai = Lantai::orderBy('id_lantai')->get();
        $lantaiId = (int) $request->input('lantai', $daftarLantai->first()?->id_lantai);
        $lantai = $daftarLantai->firstWhere('id_lantai', $lantaiId) ?? $daftarLantai->first();

        $slot = $this->slot($request);

        $fasilitas = $lantai
            ? Fasilitas::where('id_lantai', $lantai->id_lantai)->orderBy('nama_fasilitas')->get()
            : collect();

        $sessionId = $request->session()->getId();
        $status = $fasilitas->mapWithKeys(fn (Fasilitas $f) => [
            $f->id_fasilitas => $f->status_aktif === StatusAktif::Aktif
                ? $this->availability->statusFasilitas($f, $slot, $sessionId)
                : 'merah', // Tidak Aktif selalu merah (tidak bisa dipesan).
        ]);

        // Filter interaktif: request AJAX cukup dibalas fragmen denah, tanpa layout.
        if ($request->ajax()) {
            return view('admin.monitoring.partials.hasil', compact('lantai', 'fasilitas', 'status', 'slot'));
        }

        return view('admin.monitoring.index', compact('daftarLantai', 'lantai', 'fasilitas', 'status', 'slot'));
    }

    public function detail(Request $request, Fasilitas $fasilitas): View
    {
        $slot = $this->slot($request);

        // Hanya reservasi aktif yang relevan dengan rentang tanggal yang sedang dilihat.
        $reservasiAktif = $fasilitas->tarifSewa()
            ->with(['reservasi' => fn ($q) => $q
                ->whereIn('status_reservasi', [StatusReservasi::Menunggu->value, StatusReservasi::Disetujui->value])
                ->whereDate('tanggal_mulai', '<=', $slot['tanggal_selesai'])
                ->whereDate('tanggal_selesai', '>=', $slot['tanggal_mulai'])
                ->with('pemesan')
                ->orderBy('tanggal_mulai')])
            ->get()
            ->pluck('reservasi')
            ->flatten();

        // Tarif aktif — ditampilkan di kartu info supaya admin tahu harga sewa tanpa buka menu lain.
        $tarifAktif = $fasilitas->tarifSewa()
            ->where('status_aktif', StatusAktif::Aktif->value)
            ->with('jenisSewa')
            ->get();

        return view('admin.monitoring.detail', [
            'fasilitas'      => $fasilitas->load('lantai'),
            'reservasiAktif' => $reservasiAktif,
            'tarifAktif'     => $tarifAktif,
            'slot'           => $slot,
        ]);
    }

    /** Bangun slot dari input admin: pakai jam kalau diisi, kalau tidak evaluasi harian. */
    private function slot(Request $request): array
    {
        // Nilai dari URL bisa saja diutak-atik: tanggal/jam yang tidak valid diabaikan
        // (kembali ke hari ini / evaluasi harian) alih-alih memicu galat.
        $mulai = $this->tanggalValid($request->input('tanggal_mulai')) ?? Carbon::today()->toDateString();
        $selesai = $this->tanggalValid($request->input('tanggal_selesai')) ?? $mulai;
        if ($selesai < $mulai) {
            $selesai = $mulai;
        }
        $jamMulai = $this->jamValid($request->input('jam_mulai'));
        $jamSelesai = $this->jamValid($request->input('jam_selesai'));

        $pakaiJam = $jamMulai && $jamSelesai && $jamMulai < $jamSelesai;

        return [
            'tanggal_mulai'   => $mulai,
            'tanggal_selesai' => $pakaiJam ? $mulai : $selesai,
            'jam_mulai'       => $pakaiJam ? $jamMulai : null,
            'jam_selesai'     => $pakaiJam ? $jamSelesai : null,
        ];
    }

    /** Tanggal berformat Y-m-d yang benar-benar ada (mis. bukan 2026-13-45), atau null. */
    private function tanggalValid(mixed $nilai): ?string
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }
        [$t, $b, $h] = array_map('intval', explode('-', $nilai));

        return checkdate($b, $h, $t) ? $nilai : null;
    }

    /** Jam berformat HH:MM yang valid, atau null. */
    private function jamValid(mixed $nilai): ?string
    {
        return is_string($nilai) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $nilai) ? $nilai : null;
    }
}
