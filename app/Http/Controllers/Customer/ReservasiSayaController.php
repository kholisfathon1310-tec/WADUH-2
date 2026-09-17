<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Reservasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReservasiSayaController extends Controller
{
    /**
     * Daftar seluruh reservasi milik Pemesan yang sedang login — SATU kartu per ruangan
     * (bukan digabung per transaksi, supaya status tiap ruangan selalu akurat walau baru
     * sebagian dari satu transaksi multi-ruangan yang dibatalkan/disetujui). Dipisah:
     * seluruh ruangan dari kode_transaksi paling baru masuk "Reservasi Terbaru", sisanya
     * "Sebelumnya" — jadi transaksi 2-ruangan tetap muncul berdampingan di Terbaru, hanya
     * sebagai 2 kartu terpisah, bukan 1 kartu gabungan.
     */
    public function index(): View
    {
        $pemesan = Auth::guard('customer')->user();

        $semua = $pemesan->reservasi()
            ->with('tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa')
            ->latest('created_at')
            ->get();

        $kodeTerbaru = $semua->first()?->kode_transaksi;

        $terbaru = $kodeTerbaru !== null
            ? $semua->where('kode_transaksi', $kodeTerbaru)->values()
            : collect();
        $sebelumnya = $kodeTerbaru !== null
            ? $semua->where('kode_transaksi', '!=', $kodeTerbaru)->values()
            : collect();

        return view('customer.reservasi-saya.index', compact('terbaru', 'sebelumnya'));
    }

    /**
     * Detail 1 reservasi (bisa dicari via kode_reservasi maupun kode_transaksi, sama seperti
     * Cek Status) — hanya menampilkan seluruh baris pada transaksi yang sama, dan wajib milik
     * Pemesan yang sedang login.
     */
    public function show(string $kode): View
    {
        $pemesan = Auth::guard('customer')->user();

        $kodeTransaksi = Reservasi::where('id_pemesan', $pemesan->id_pemesan)
            ->where(fn ($q) => $q->where('kode_transaksi', $kode)->orWhere('kode_reservasi', $kode))
            ->value('kode_transaksi');

        abort_if($kodeTransaksi === null, 404, 'Reservasi tidak ditemukan.');

        $reservasi = Reservasi::where('id_pemesan', $pemesan->id_pemesan)
            ->where('kode_transaksi', $kodeTransaksi)
            ->with(['tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa', 'dokumenPersyaratan', 'riwayatStatus'])
            ->get();

        return view('customer.reservasi-saya.show', ['kode' => $kodeTransaksi, 'reservasi' => $reservasi]);
    }
}
