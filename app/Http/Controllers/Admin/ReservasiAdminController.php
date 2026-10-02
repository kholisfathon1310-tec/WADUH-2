<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LockStatus;
use App\Enums\StatusReservasi;
use App\Enums\StatusVerifikasi;
use App\Http\Controllers\Controller;
use App\Models\DokumenPersyaratan;
use App\Models\Fasilitas;
use App\Models\JenisSewa;
use App\Models\Lantai;
use App\Models\Reservasi;
use App\Services\ReservasiApprovalService;
use App\Services\StatusOtomatisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReservasiAdminController extends Controller
{
    public function __construct(
        private readonly ReservasiApprovalService $approval,
        private readonly StatusOtomatisService $statusOtomatis,
    ) {
    }

    /** Daftar seluruh Reservasi, dikelompokkan per kode_transaksi, dengan search & filter. */
    public function index(Request $request): View
    {
        $reservasi = Reservasi::query()
            ->with(['pemesan', 'tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa', 'faktur'])
            ->when($request->filled('pemesan'), fn ($q) => $q->whereHas('pemesan',
                fn ($p) => $p->where('nama_lengkap', 'like', '%'.$request->input('pemesan').'%')))
            ->when($request->filled('kategori'), fn ($q) => $q->whereHas('tarifSewa.fasilitas',
                fn ($f) => $f->where('kategori_fasilitas', $request->input('kategori'))))
            ->when($request->filled('nama_fasilitas'), fn ($q) => $q->whereHas('tarifSewa.fasilitas',
                fn ($f) => $f->where('nama_fasilitas', 'like', '%'.$request->input('nama_fasilitas').'%')))
            ->when($request->filled('lantai'), fn ($q) => $q->whereHas('tarifSewa.fasilitas',
                fn ($f) => $f->where('id_lantai', $request->input('lantai'))))
            ->when($request->filled('jenis_sewa'), fn ($q) => $q->whereHas('tarifSewa.jenisSewa',
                fn ($j) => $j->where('satuan', $request->input('jenis_sewa'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status_reservasi', $request->input('status')))
            ->when($request->filled('tanggal'), fn ($q) => $q
                ->whereDate('tanggal_mulai', '<=', $request->input('tanggal'))
                ->whereDate('tanggal_selesai', '>=', $request->input('tanggal')))
            ->latest('created_at')
            ->get();

        // Kelompokkan per kode_transaksi mempertahankan urutan terbaru.
        $grup = $reservasi->groupBy('kode_transaksi');

        // Filter/pencarian interaktif: request AJAX cukup dibalas fragmen hasil, tanpa layout.
        if ($request->ajax()) {
            return view('admin.reservasi.partials.hasil', compact('grup'));
        }

        return view('admin.reservasi.index', [
            'grup'          => $grup,
            'daftarLantai'  => Lantai::orderBy('id_lantai')->get(),
            'daftarKategori' => Fasilitas::select('kategori_fasilitas')->distinct()->orderBy('kategori_fasilitas')->pluck('kategori_fasilitas'),
            'daftarJenis'   => JenisSewa::orderBy('id_jenis_sewa')->get(),
            'daftarStatus'  => StatusReservasi::cases(),
            'filter'        => $request->only(['pemesan', 'kategori', 'nama_fasilitas', 'lantai', 'jenis_sewa', 'status', 'tanggal']),
        ]);
    }

    /**
     * Detail SATU pemesanan: menampilkan seluruh ruangan dalam kode reservasi/transaksi
     * yang sama (pemesan memesan 2 ruangan → admin cukup melihat 1 halaman detail).
     */
    public function show(string $kodeReservasi): View
    {
        $reservasi = $this->cari($kodeReservasi, ['pemesan', 'faktur']);

        $items = Reservasi::where('kode_transaksi', $reservasi->kode_transaksi)
            ->with(['tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa', 'dokumenPersyaratan', 'riwayatStatus.admin', 'faktur'])
            ->orderBy('id_reservasi')
            ->get();

        // Checklist kelayakan per ruangan (key: id_reservasi).
        $checklists = $items->mapWithKeys(fn (Reservasi $it) => [
            $it->id_reservasi => $this->approval->checklist($it),
        ]);

        $pending = $items->where('status_reservasi', StatusReservasi::Menunggu);
        $bolehTolak = $pending->isNotEmpty();
        $bolehSetujui = $bolehTolak && $pending->every(
            fn (Reservasi $it) => collect($checklists[$it->id_reservasi])->every(fn ($c) => $c['passed'])
        );

        // Riwayat gabungan seluruh ruangan.
        $riwayat = $items->flatMap->riwayatStatus->sortByDesc('id_riwayat');

        return view('admin.reservasi.show', compact('reservasi', 'items', 'checklists', 'bolehSetujui', 'bolehTolak', 'riwayat'));
    }

    /** Setujui SELURUH ruangan Menunggu dalam pemesanan ini (checklist semua harus lolos). */
    public function setujui(string $kodeReservasi): RedirectResponse
    {
        $reservasi = $this->cari($kodeReservasi);

        // Pastikan ruangan yang sudah lewat batas persetujuannya berstatus Kadaluwarsa lebih
        // dulu, supaya tidak ikut disetujui/ditolak walau halaman detail dibuka sebelum batasnya.
        $this->statusOtomatis->kadaluwarsakanRuanganTerlambat();

        $pending = Reservasi::where('kode_transaksi', $reservasi->kode_transaksi)
            ->where('status_reservasi', StatusReservasi::Menunggu->value)
            ->orderBy('id_reservasi')
            ->get();

        if ($pending->isEmpty()) {
            return back()->with('error', 'Tidak ada ruangan berstatus Menunggu pada reservasi ini.');
        }
        foreach ($pending as $item) {
            if (! $this->approval->passes($item)) {
                return back()->with('error', 'Persyaratan belum terpenuhi seluruhnya. Periksa checklist pada setiap ruangan.');
            }
        }

        foreach ($pending as $item) {
            // Set id_admin sebelum status agar Observer mengisi tanggal_diproses & Riwayat_Status.
            $item->id_admin = Auth::guard('admin')->id();
            $item->lock_status = LockStatus::Confirmed;
            $item->status_reservasi = StatusReservasi::Disetujui;
            $item->save();
        }

        // Tetap di halaman detail (back()) — bukan redirect ke daftar. Duplikasi entri histori
        // browser (yang bikin tombol back perlu 2x tekan) ditangani di sisi klien: form ini
        // dikirim lewat fetch + location.replace() (lihat data-nav-replace di admin/layouts/app).
        return back()->with('success', "Reservasi {$reservasi->kode_transaksi} berhasil disetujui ({$pending->count()} ruangan).");
    }

    /** Tolak SELURUH ruangan Menunggu dalam pemesanan ini; alasan tercatat via Observer. */
    public function tolak(Request $request, string $kodeReservasi): RedirectResponse
    {
        $data = $request->validate(
            ['alasan' => ['required', 'string', 'min:10', 'max:1000']],
            [
                'alasan.required' => 'Alasan penolakan wajib diisi. Alasan ini akan ditampilkan kepada pemesan pada riwayat status.',
                'alasan.min'      => 'Alasan penolakan minimal 10 karakter.',
                'alasan.max'      => 'Alasan penolakan maksimal 1000 karakter.',
            ],
        );

        $reservasi = $this->cari($kodeReservasi);

        // Pastikan ruangan yang sudah lewat batas persetujuannya berstatus Kadaluwarsa lebih
        // dulu, supaya tidak ikut disetujui/ditolak walau halaman detail dibuka sebelum batasnya.
        $this->statusOtomatis->kadaluwarsakanRuanganTerlambat();

        $pending = Reservasi::where('kode_transaksi', $reservasi->kode_transaksi)
            ->where('status_reservasi', StatusReservasi::Menunggu->value)
            ->get();

        if ($pending->isEmpty()) {
            return back()->with('error', 'Tidak ada ruangan berstatus Menunggu pada reservasi ini.');
        }

        foreach ($pending as $item) {
            $item->id_admin = Auth::guard('admin')->id();
            $item->keteranganRiwayat = $data['alasan'];
            $item->lock_status = LockStatus::Released;
            $item->status_reservasi = StatusReservasi::Ditolak;
            $item->save();
        }

        return back()->with('success', "Reservasi {$reservasi->kode_transaksi} telah ditolak ({$pending->count()} ruangan).");
    }

    /**
     * Hapus SELURUH pemesanan (semua ruangan dalam kode_transaksi ini) secara permanen —
     * konsisten dengan Setujui/Tolak yang juga berlaku untuk seluruh transaksi. Anak-anak
     * baris (dokumen, riwayat status, faktur) di-restrictOnDelete di DB, jadi dihapus lebih
     * dulu; file dokumen fisik di disk 'public' ikut dibersihkan supaya tidak jadi sampah.
     */
    public function hapus(string $kodeReservasi): RedirectResponse
    {
        $reservasi = $this->cari($kodeReservasi);

        $items = Reservasi::where('kode_transaksi', $reservasi->kode_transaksi)
            ->with('dokumenPersyaratan')
            ->get();

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                foreach ($item->dokumenPersyaratan as $dok) {
                    Storage::disk('public')->delete($dok->lokasi_file);
                }
                $item->dokumenPersyaratan()->delete();
                $item->riwayatStatus()->delete();
                $item->faktur()->delete();
                $item->delete();
            }
        });

        return redirect()->route('admin.reservasi.index')
            ->with('success', "Reservasi {$reservasi->kode_transaksi} ({$items->count()} ruangan) berhasil dihapus.");
    }

    /**
     * Dokumen sewa Bulan disalin ke tiap baris Reservasi dalam satu kode_transaksi
     * (lihat ReservasiController::simpanDokumen) supaya checklist kelayakan tiap ruangan
     * tetap independen — tapi di halaman detail cuma DITAMPILKAN satu kartu per file.
     * Makanya verifikasi di sini ikut menyamakan status semua salinannya sekaligus,
     * supaya checklist tiap ruangan tetap konsisten walau yang tampak cuma satu.
     */
    public function verifikasiDokumen(Request $request, DokumenPersyaratan $dokumen): RedirectResponse
    {
        $data = $request->validate([
            'status_verifikasi' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, StatusVerifikasi::cases()))],
        ]);

        // Keputusan Valid/Tidak Valid final — begitu sudah diputuskan, tidak boleh diubah lagi.
        if ($dokumen->status_verifikasi !== StatusVerifikasi::Menunggu) {
            return back()->with('error', 'Dokumen ini telah diverifikasi sebelumnya dan tidak dapat diubah.');
        }

        $kodeTransaksi = $dokumen->reservasi->kode_transaksi;

        DokumenPersyaratan::whereHas('reservasi', fn ($q) => $q->where('kode_transaksi', $kodeTransaksi))
            ->where('nama_file', $dokumen->nama_file)
            ->update(['status_verifikasi' => $data['status_verifikasi']]);

        return back()->with('success', 'Status verifikasi dokumen berhasil diperbarui.');
    }

    /** @param array<int,string> $with */
    private function cari(string $kodeReservasi, array $with = []): Reservasi
    {
        return Reservasi::where('kode_reservasi', $kodeReservasi)->with($with)->firstOrFail();
    }
}
