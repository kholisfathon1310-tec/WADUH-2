<?php

namespace App\Http\Controllers;

use App\Enums\StatusAktif;
use App\Models\Fasilitas;
use App\Models\Lantai;
use App\Models\TarifSewa;
use App\Services\FasilitasBawaanService;
use Illuminate\View\View;

/**
 * Jelajah fasilitas publik — TERPISAH dari alur reservasi (App\Http\Controllers\ReservasiController).
 * Murni informasi: pilih kategori → pilih lantai → denah (klik = lihat detail) → detail.
 * Tidak ada jenis sewa, jadwal, status ketersediaan booking, atau jalan ke keranjang/checkout.
 */
class FasilitasController extends Controller
{
    /** Urutan tetap kategori fasilitas (bukan alfabetis) — dipakai di semua listing kategori. */
    public const URUTAN_KATEGORI = ['Working Space', 'Co-Working Space', 'Convention Hall'];

    /** Pilih kategori fasilitas. */
    public function index(): View
    {
        $kategori = Fasilitas::query()
            ->where('status_aktif', StatusAktif::Aktif->value)
            ->select('kategori_fasilitas')
            ->distinct()
            ->pluck('kategori_fasilitas')
            ->sortBy(fn ($k) => array_search($k, self::URUTAN_KATEGORI))
            ->values();

        return view('fasilitas.kategori', ['kategori' => $kategori]);
    }

    /** Pilih lantai yang punya fasilitas kategori ini. */
    public function lantai(string $kategori): View
    {
        $this->pastikanKategoriAda($kategori);

        $lantai = Lantai::query()
            ->whereHas('fasilitas', fn ($f) => $f
                ->where('status_aktif', StatusAktif::Aktif->value)
                ->where('kategori_fasilitas', $kategori))
            ->orderBy('nomor_lantai')
            ->get();

        return view('fasilitas.lantai', compact('kategori', 'lantai'));
    }

    /** Denah lantai — klik ruangan langsung ke detail (mode lihat saja, tanpa multi-select/jadwal). */
    public function denah(string $kategori, Lantai $lantai): View
    {
        $this->pastikanKategoriAda($kategori);

        $fasilitas = Fasilitas::query()
            ->where('id_lantai', $lantai->id_lantai)
            ->where('status_aktif', StatusAktif::Aktif->value)
            ->where('kategori_fasilitas', $kategori)
            ->orderBy('nama_fasilitas')
            ->get();

        // Netral — bukan status ketersediaan booking (fitur ini tidak punya konsep jadwal).
        // Fasilitas Tidak Aktif tetap ditandai "terisi" secara otomatis oleh <x-denah>.
        $status = $fasilitas->mapWithKeys(fn (Fasilitas $f) => [$f->id_fasilitas => 'hijau']);

        return view('fasilitas.denah', compact('kategori', 'lantai', 'fasilitas', 'status'));
    }

    /** Detail fasilitas — halaman akhir (dead end), tidak ada jalan ke alur reservasi. */
    public function detail(Fasilitas $fasilitas): View
    {
        abort_if($fasilitas->status_aktif !== StatusAktif::Aktif, 404);

        $tarifPerSatuan = TarifSewa::tersedia()
            ->where('id_fasilitas', $fasilitas->id_fasilitas)
            ->with('jenisSewa')
            ->get()
            ->keyBy(fn (TarifSewa $t) => $t->jenisSewa->satuan->value);

        $bawaanPerSatuan = $tarifPerSatuan->keys()
            ->mapWithKeys(fn (string $satuan) => [
                $satuan => app(FasilitasBawaanService::class)->untuk($fasilitas, $satuan),
            ]);

        return view('fasilitas.detail', [
            'fasilitas'       => $fasilitas->load('lantai'),
            'tarifPerSatuan'  => $tarifPerSatuan,
            'bawaanPerSatuan' => $bawaanPerSatuan,
        ]);
    }

    private function pastikanKategoriAda(string $kategori): void
    {
        $ada = Fasilitas::where('status_aktif', StatusAktif::Aktif->value)
            ->where('kategori_fasilitas', $kategori)
            ->exists();
        abort_unless($ada, 404, 'Kategori fasilitas tidak ditemukan.');
    }
}
