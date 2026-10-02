<?php

namespace App\Http\Controllers;

use App\Models\Reservasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CekStatusController extends Controller
{
    /** Form input kode. */
    public function form(): View
    {
        return view('cek-status.form');
    }

    /** Validasi kode dari form pencarian, lalu redirect ke URL hasil (GET, stabil & bisa di-refresh). */
    public function cari(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['kode' => ['required', 'string', 'max:30', 'regex:/^'.Reservasi::POLA_KODE.'$/']],
            [
                'kode.required' => 'Kode reservasi wajib diisi, contoh: RS186.',
                'kode.max'      => 'Kode reservasi terlalu panjang. Periksa kembali penulisannya.',
                'kode.regex'    => 'Format kode reservasi tidak valid, contoh: RS186.',
            ],
        );

        return redirect()->route('cek-status.hasil', ['kode' => strtoupper(trim($data['kode']))]);
    }

    /** Cari reservasi by kode_transaksi (banyak baris) ATAU kode_reservasi tunggal. */
    public function hasil(string $kode): View
    {
        $reservasi = Reservasi::query()
            ->with([
                'tarifSewa.fasilitas.lantai',
                'tarifSewa.jenisSewa',
                'pemesan',
                'dokumenPersyaratan',
                'riwayatStatus' => fn ($q) => $q->orderBy('tanggal_perubahan'),
            ])
            ->where('kode_transaksi', $kode)
            ->orWhere('kode_reservasi', $kode)
            ->orderBy('id_reservasi')
            ->get();

        return view('cek-status.hasil', [
            'kode'      => $kode,
            'reservasi' => $reservasi,
        ]);
    }
}
