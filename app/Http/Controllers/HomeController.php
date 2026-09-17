<?php

namespace App\Http\Controllers;

use App\Enums\StatusAktif;
use App\Models\Admin;
use App\Models\Lantai;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Landing page WADUH — Wadah Akses Digital Unit Hunian BITC.
     * Menampilkan info per lantai (kategori, jumlah ruang, jumlah tersedia) dari data real.
     */
    public function __invoke(): View
    {
        $lantai = Lantai::with('fasilitas')
            ->orderBy('id_lantai')
            ->get()
            ->map(function (Lantai $l) {
                $aktif = $l->fasilitas->where('status_aktif', StatusAktif::Aktif);

                // Lantai bisa campur kategori (mis. 3A/3B: mayoritas Co-Working + beberapa
                // Working Space) — tampilkan kategori dengan jumlah ruangan terbanyak, bukan
                // sekadar baris pertama yang urutannya bisa acak.
                $kategoriUtama = $l->fasilitas
                    ->countBy('kategori_fasilitas')
                    ->sortDesc()
                    ->keys()
                    ->first();

                // Kapasitas yang ditampilkan mengikuti kategori utama saja (bukan seluruh
                // ruangan di lantai itu) — kalau tidak, sisa ruangan kategori minoritas
                // (mis. Working Space di lantai yang mayoritas Co-Working) ikut melebarkan
                // rentang kapasitas jadi tidak mencerminkan kategori yang ditampilkan.
                $aktifKategoriUtama = $aktif->where('kategori_fasilitas', $kategoriUtama);

                return [
                    'id'           => $l->id_lantai,
                    'nomor'        => $l->nomor_lantai,
                    'kategori'     => $kategoriUtama ?? '-',
                    'total'        => $l->fasilitas->count(),
                    'tersedia'     => $aktif->count(),
                    'kap_min'      => $aktifKategoriUtama->min('kapasitas'),
                    'kap_maks'     => $aktifKategoriUtama->max('kapasitas'),
                ];
            });

        // Kontak WhatsApp/alamat di section Kontak diambil dari biodata admin (halaman Profil),
        // supaya tombol WA pemesan langsung ke nomor admin yang sebenarnya, bukan placeholder.
        $admin = Admin::orderBy('id_admin')->first();

        return view('home', ['daftarLantai' => $lantai, 'adminKontak' => $admin]);
    }
}
