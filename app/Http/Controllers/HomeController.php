<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Support\RingkasanLantai;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Landing page WADUH — Wadah Akses Digital Unit Hunian BITC.
     * Menampilkan info per lantai (kategori, jumlah ruang, jumlah tersedia) dari data real.
     */
    public function __invoke(): View
    {
        $lantai = RingkasanLantai::semua();

        // Nomor WhatsApp di section Kontak diambil dari biodata admin (halaman Profil),
        // supaya tombol WA pemesan langsung ke nomor admin yang sebenarnya, bukan placeholder.
        $admin = Admin::orderBy('id_admin')->first();

        return view('home', ['daftarLantai' => $lantai, 'adminKontak' => $admin]);
    }
}
