<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusReservasi;
use App\Http\Controllers\Controller;
use App\Models\Faktur;
use App\Models\Reservasi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * SATU faktur per kode reservasi/transaksi: bila pemesan memesan beberapa ruangan
 * sekaligus, seluruh baris Disetujui digabung sebagai item dalam SATU PDF faktur.
 * Baris Faktur di DB ditautkan ke baris reservasi pertama (anchor) — kolom
 * id_reservasi UNIQUE tetap terpenuhi.
 *
 * Tombol "Cetak Faktur" idempotent: klik pertama menerbitkan nomor, klik
 * berikutnya mengunduh PDF yang sama.
 */
class FakturController extends Controller
{
    private const RELASI = ['pemesan', 'tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa', 'admin'];

    public function cetak(string $kodeReservasi): Response|RedirectResponse
    {
        $ref = Reservasi::where('kode_reservasi', $kodeReservasi)->firstOrFail();

        $items = Reservasi::where('kode_transaksi', $ref->kode_transaksi)
            ->whereIn('status_reservasi', [StatusReservasi::Disetujui->value, StatusReservasi::Selesai->value])
            ->orderBy('id_reservasi')
            ->with(self::RELASI)
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Faktur hanya dapat diterbitkan untuk reservasi berstatus Disetujui atau Selesai.');
        }

        // Anchor = baris pertama transaksi; buat faktur sekali, selanjutnya pakai yang sama.
        $anchor = $items->first();
        $faktur = Faktur::firstOrCreate(
            ['id_reservasi' => $anchor->id_reservasi],
            ['nomor_faktur' => $this->nomorFaktur(), 'tanggal_faktur' => now()],
        );

        return $this->pdf($faktur, $items);
    }

    /** Unduh ulang faktur transaksi ini (tanpa membuat baris baru). */
    public function unduh(string $kodeReservasi): Response|RedirectResponse
    {
        $ref = Reservasi::where('kode_reservasi', $kodeReservasi)->firstOrFail();

        $items = Reservasi::where('kode_transaksi', $ref->kode_transaksi)
            ->whereIn('status_reservasi', [StatusReservasi::Disetujui->value, StatusReservasi::Selesai->value])
            ->orderBy('id_reservasi')
            ->with(self::RELASI)
            ->get();

        $faktur = $items->isNotEmpty()
            ? Faktur::where('id_reservasi', $items->first()->id_reservasi)->first()
            : null;

        if (! $faktur) {
            return back()->with('error', 'Faktur untuk reservasi ini belum diterbitkan.');
        }

        return $this->pdf($faktur, $items);
    }

    /** Generate nomor unik format INV/{tahun}/{urutan}. */
    private function nomorFaktur(): string
    {
        $tahun = now()->year;
        $seq = Faktur::whereYear('tanggal_faktur', $tahun)->count() + 1;

        do {
            $nomor = sprintf('INV/%d/%04d', $tahun, $seq);
            $seq++;
        } while (Faktur::where('nomor_faktur', $nomor)->exists());

        return $nomor;
    }

    /** Siapkan data sesuai kontrak template resources/views/admin/pdf/faktur.blade.php. */
    private function pdf(Faktur $faktur, $items): Response
    {
        $anchor = $items->first();
        $total = $items->sum('total_biaya');
        $adaKolomWaktu = $items->contains(fn ($r) => $r->tarifSewa->jenisSewa->satuan->value === 'Jam');

        $tglId = fn ($d) => $d->locale('id')->translatedFormat('d F Y');
        $waktu = fn ($r) => $r->jam_mulai
            ? str_replace(':', '.', substr($r->jam_mulai, 0, 5)).' - '.str_replace(':', '.', substr($r->jam_selesai, 0, 5)).' WIB'
            : null;

        $barisItems = $items->values()->map(function ($r, $i) use ($tglId, $waktu) {
            $fas = $r->tarifSewa->fasilitas;
            $satuan = $r->tarifSewa->jenisSewa->satuan->value;

            return [
                'no'      => $i + 1,
                'uraian'  => "Sewa {$fas->nama_fasilitas} {$fas->kategori_fasilitas}, Lantai {$fas->lantai->nomor_lantai}",
                'tanggal' => $r->tanggal_selesai->eq($r->tanggal_mulai)
                    ? $tglId($r->tanggal_mulai)
                    : $tglId($r->tanggal_mulai).' – '.$tglId($r->tanggal_selesai),
                'waktu'   => $satuan === 'Jam' ? $waktu($r) : null,
                'durasi'  => $r->durasi.' '.$satuan,
                'tarif'   => (float) $r->harga_satuan,
                'jumlah'  => (float) $r->total_biaya,
            ];
        })->all();

        $data = [
            'inst'    => config('institusi'),
            'faktur'  => [
                'no'      => $faktur->nomor_faktur,
                'tanggal' => $tglId($faktur->tanggal_faktur),
                // Kotak Waktu hanya untuk kasus satu fasilitas jenis Jam (sesuai referensi).
                'waktu'   => ($items->count() === 1 && $adaKolomWaktu) ? $waktu($anchor) : null,
            ],
            'pemesan' => ['nama' => $anchor->pemesan->nama_lengkap, 'alamat' => $anchor->pemesan->alamat],
            'items'   => $barisItems,
            'total'   => $total,
            'terbilang' => \App\Helpers\NumberToWords::terbilang((int) floor($total)),
            'adaKolomWaktu' => $adaKolomWaktu,
        ];

        $namaFile = str_replace(['/', '\\'], '-', 'faktur-'.$faktur->nomor_faktur);

        return Pdf::loadView('admin.pdf.faktur', $data)->download($namaFile.'.pdf');
    }
}
