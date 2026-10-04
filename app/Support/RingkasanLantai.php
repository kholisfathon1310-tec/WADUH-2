<?php

namespace App\Support;

use App\Enums\StatusAktif;
use App\Enums\StatusReservasi;
use App\Models\Lantai;
use App\Models\Reservasi;
use Illuminate\Support\Collection;

/**
 * Ringkasan per lantai (kategori, jumlah unit, jumlah tersedia hari ini, kapasitas) —
 * satu sumber yang sama untuk Beranda dan menu Fasilitas pemesan, supaya angkanya selalu
 * konsisten di kedua halaman.
 */
class RingkasanLantai
{
    /** @return Collection<int, array{id:int, nomor:string, kategori:string, total:int, tersedia:int, kap_min:?int, kap_maks:?int}> */
    public static function semua(): Collection
    {
        // "Tersedia" mencerminkan kondisi HARI INI: fasilitas dengan reservasi Menunggu/
        // Disetujui yang mencakup tanggal hari ini dianggap sedang terpakai.
        $hariIni = now()->toDateString();
        $idFasilitasTerisiHariIni = Reservasi::whereIn('status_reservasi', [
                StatusReservasi::Menunggu->value,
                StatusReservasi::Disetujui->value,
            ])
            ->whereDate('tanggal_mulai', '<=', $hariIni)
            ->whereDate('tanggal_selesai', '>=', $hariIni)
            ->with('tarifSewa:id_tarif_sewa,id_fasilitas')
            ->get()
            ->pluck('tarifSewa.id_fasilitas')
            ->unique();

        return Lantai::with('fasilitas')
            ->orderBy('id_lantai')
            ->get()
            ->map(function (Lantai $l) use ($idFasilitasTerisiHariIni) {
                $aktif = $l->fasilitas->where('status_aktif', StatusAktif::Aktif);

                // Lantai bisa campur kategori (mis. 3A/3B: mayoritas Co-Working + beberapa
                // Working Space) — tampilkan kategori dengan jumlah ruangan terbanyak.
                $kategoriUtama = $l->fasilitas
                    ->countBy('kategori_fasilitas')
                    ->sortDesc()
                    ->keys()
                    ->first();

                // Kapasitas mengikuti kategori utama saja, supaya rentangnya mencerminkan
                // kategori yang ditampilkan (mis. kubikal 3A = 2 orang, bukan "hingga 10").
                $aktifKategoriUtama = $aktif->where('kategori_fasilitas', $kategoriUtama);

                return [
                    'id'       => $l->id_lantai,
                    'nomor'    => $l->nomor_lantai,
                    'kategori' => $kategoriUtama ?? '-',
                    'total'    => $l->fasilitas->count(),
                    'tersedia' => $aktif->whereNotIn('id_fasilitas', $idFasilitasTerisiHariIni)->count(),
                    'kap_min'  => $aktifKategoriUtama->min('kapasitas'),
                    'kap_maks' => $aktifKategoriUtama->max('kapasitas'),
                ];
            });
    }
}
