<?php

namespace App\Http\Controllers;

use App\Models\Reservasi;
use App\Support\Denah;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bukti Reservasi (bukan faktur/invoice pembayaran — lihat Admin\FakturController untuk itu):
 * dokumen ringkas yang bisa diunduh pemesan sendiri dari halaman Cek Status, langsung
 * tersedia begitu reservasi dibuat (tidak menunggu persetujuan admin), untuk ditunjukkan
 * sebagai bukti reservasi saat survei lapangan di lokasi BITC. Fokus pada kode reservasi
 * yang besar & jelas, bukan rincian pembayaran maupun status persetujuan.
 *
 * SATU dokumen per kode transaksi: bila pemesan memesan beberapa ruangan sekaligus,
 * seluruh ruangan pada transaksi yang sama digabung dalam satu PDF — konsisten dengan
 * pola "satu kode reservasi/transaksi" yang sudah dipakai di Admin\FakturController.
 * Dibuat on-the-fly (tidak disimpan ke tabel baru); kode_transaksi/kode_reservasi yang
 * sudah ada dipakai langsung sebagai nomor referensi dokumen.
 */
class BuktiReservasiController extends Controller
{
    private const RELASI = ['pemesan', 'tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa'];

    public function unduh(string $kodeReservasi): Response|RedirectResponse
    {
        $ref = Reservasi::where('kode_reservasi', $kodeReservasi)->first();

        if (! $ref) {
            return redirect()->route('cek-status.form')->with('error', 'Kode reservasi tidak ditemukan.');
        }

        // Bukti hanya diterbitkan untuk reservasi yang masih Menunggu atau sudah Disetujui.
        $pesanTidakTersedia = "Bukti reservasi tidak dapat diunduh karena status reservasi {$ref->kode_reservasi} adalah "
            .$ref->status_reservasi->value.'. Bukti hanya tersedia untuk reservasi yang menunggu verifikasi atau sudah disetujui.';
        if (! $ref->buktiTersedia()) {
            return redirect()->to(url()->previous(route('cek-status.form')))->with('error', $pesanTidakTersedia);
        }

        // Ruangan lain pada transaksi yang sama yang sudah ditolak/dibatalkan/selesai/kedaluwarsa
        // tidak ikut dicantumkan, supaya tidak tampil seolah masih berlaku.
        $items = Reservasi::where('kode_transaksi', $ref->kode_transaksi)
            ->whereIn('status_reservasi', Reservasi::statusBuktiTersedia())
            ->orderBy('id_reservasi')
            ->with(self::RELASI)
            ->get();

        $anchor = $items->first();

        $tglId = fn ($d) => $d->locale('id')->translatedFormat('d F Y');
        $waktu = fn ($r) => $r->jam_mulai
            ? str_replace(':', '.', substr($r->jam_mulai, 0, 5)).' - '.str_replace(':', '.', substr($r->jam_selesai, 0, 5)).' WIB'
            : null;

        $baris = $items->values()->map(function (Reservasi $r, int $i) use ($tglId, $waktu) {
            $fas = $r->tarifSewa->fasilitas;
            $satuan = $r->tarifSewa->jenisSewa->satuan->value;

            return [
                'no'       => $i + 1,
                'kode'     => $r->kode_reservasi,
                'unit'     => Denah::labelPendek($fas->kode_fasilitas),
                'nama'     => $fas->nama_fasilitas,
                'kategori' => $fas->kategori_fasilitas,
                'lantai'   => $fas->lantai->nomor_lantai,
                'tanggal'  => $r->tanggal_selesai->eq($r->tanggal_mulai)
                    ? $tglId($r->tanggal_mulai)
                    : $tglId($r->tanggal_mulai).' – '.$tglId($r->tanggal_selesai),
                'waktu'    => $waktu($r),
                'durasi'   => $r->durasi.' '.$satuan,
                'total'    => (float) $r->total_biaya,
            ];
        })->all();

        $data = [
            'inst'          => config('institusi'),
            'kodeTransaksi' => $anchor->kode_transaksi,
            'pemesan'       => $anchor->pemesan->nama_lengkap,
            'items'         => $baris,
            'total'         => $items->sum('total_biaya'),
            'diterbitkan'   => now()->locale('id')->translatedFormat('d F Y, H:i').' WIB',
        ];

        $namaFile = 'bukti-reservasi-'.str_replace(['/', '\\'], '-', $anchor->kode_transaksi).'.pdf';

        return Pdf::loadView('pdf.bukti-reservasi', $data)->download($namaFile);
    }
}
