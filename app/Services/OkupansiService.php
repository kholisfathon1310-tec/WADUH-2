<?php

namespace App\Services;

use App\Enums\StatusAktif;
use App\Enums\StatusReservasi;
use App\Models\Fasilitas;
use App\Models\Reservasi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tingkat okupansi fasilitas untuk dashboard Admin.
 *
 * Satu fasilitas dihitung "terisi" pada suatu hari kerja (Senin–Jumat, gedung tutup akhir
 * pekan) bila ada reservasi Disetujui/Selesai yang mencakup tanggal itu. Selesai ikut dihitung
 * supaya okupansi bulan yang sudah lewat tidak surut begitu masa pakainya berakhir; bulan
 * mendatang terisi dari reservasi yang sudah disetujui.
 */
class OkupansiService
{
    /**
     * Okupansi gedung per bulan, Januari–Desember.
     *
     * @return array<int, array{tanggal: Carbon, kunci: string, label: string, hariKerja: int, terisi: int, kapasitas: int, pct: float}>
     */
    public function bulananTahun(int $tahun): array
    {
        $awal = Carbon::create($tahun, 1, 1)->startOfDay();
        $akhir = $awal->copy()->endOfYear()->startOfDay();

        $fasilitas = $this->fasilitasAktif();
        $peta = $this->petaTerisi($awal, $akhir, $fasilitas->pluck('id_fasilitas')->all());

        $terisiPerBulan = array_fill(1, 12, 0);
        foreach ($peta as $tanggalTerisi) {
            foreach (array_keys($tanggalTerisi) as $tanggal) {
                $terisiPerBulan[(int) substr($tanggal, 5, 2)]++;
            }
        }

        $hasil = [];
        for ($b = 1; $b <= 12; $b++) {
            $bulan = Carbon::create($tahun, $b, 1)->startOfDay();
            $hariKerja = $this->hariKerja($bulan);
            $kapasitas = $hariKerja * $fasilitas->count();

            $hasil[] = [
                'tanggal'   => $bulan,
                'kunci'     => $bulan->format('Y-m'),
                'label'     => $bulan->translatedFormat('M'),
                'hariKerja' => $hariKerja,
                'terisi'    => $terisiPerBulan[$b],
                'kapasitas' => $kapasitas,
                'pct'       => $kapasitas > 0 ? round($terisiPerBulan[$b] / $kapasitas * 100, 1) : 0.0,
            ];
        }

        return $hasil;
    }

    /**
     * Okupansi tiap fasilitas aktif pada satu bulan, urut lantai lalu kode (natural: K2 sebelum K10).
     *
     * @return Collection<int, array{id_fasilitas: int, kode: string, nama: string, kategori: string, lantai: string, terisi: int, hariKerja: int, pct: int}>
     */
    public function perFasilitas(Carbon $bulan): Collection
    {
        $awal = $bulan->copy()->startOfMonth()->startOfDay();
        $akhir = $bulan->copy()->endOfMonth()->startOfDay();
        $hariKerja = $this->hariKerja($awal);

        $fasilitas = $this->fasilitasAktif();
        $peta = $this->petaTerisi($awal, $akhir, $fasilitas->pluck('id_fasilitas')->all());

        return $fasilitas
            ->map(function (Fasilitas $f) use ($peta, $hariKerja) {
                $terisi = count($peta[$f->id_fasilitas] ?? []);

                return [
                    'id_fasilitas' => $f->id_fasilitas,
                    'kode'         => $f->kode_fasilitas,
                    'nama'         => $f->nama_fasilitas,
                    'kategori'     => $f->kategori_fasilitas,
                    'lantai'       => (string) ($f->lantai->nomor_lantai ?? '-'),
                    'terisi'       => $terisi,
                    'hariKerja'    => $hariKerja,
                    'pct'          => $hariKerja > 0 ? (int) round($terisi / $hariKerja * 100) : 0,
                ];
            })
            ->sort(fn (array $a, array $b) => strnatcasecmp($a['lantai'], $b['lantai']) ?: strnatcasecmp($a['kode'], $b['kode']))
            ->values();
    }

    /** Jumlah hari kerja (Senin–Jumat) pada bulan dari tanggal yang diberikan. */
    public function hariKerja(Carbon $bulan): int
    {
        $jumlah = 0;
        $akhir = $bulan->copy()->endOfMonth()->startOfDay();
        for ($d = $bulan->copy()->startOfMonth()->startOfDay(); $d->lte($akhir); $d->addDay()) {
            if ($d->isWeekday()) {
                $jumlah++;
            }
        }

        return $jumlah;
    }

    /** @return Collection<int, Fasilitas> */
    private function fasilitasAktif(): Collection
    {
        return Fasilitas::where('status_aktif', StatusAktif::Aktif->value)->with('lantai')->get();
    }

    /**
     * Peta id_fasilitas → himpunan tanggal hari kerja (Y-m-d) yang terisi dalam rentang.
     *
     * @param  int[]  $idFasilitas
     * @return array<int, array<string, true>>
     */
    private function petaTerisi(Carbon $awal, Carbon $akhir, array $idFasilitas): array
    {
        if ($idFasilitas === []) {
            return [];
        }

        $baris = Reservasi::query()
            ->join('tarif_sewa', 'tarif_sewa.id_tarif_sewa', '=', 'reservasi.id_tarif_sewa')
            ->whereIn('tarif_sewa.id_fasilitas', $idFasilitas)
            ->whereIn('reservasi.status_reservasi', [StatusReservasi::Disetujui->value, StatusReservasi::Selesai->value])
            ->whereDate('reservasi.tanggal_mulai', '<=', $akhir->toDateString())
            ->whereDate('reservasi.tanggal_selesai', '>=', $awal->toDateString())
            ->get(['tarif_sewa.id_fasilitas as id_fasilitas', 'reservasi.tanggal_mulai', 'reservasi.tanggal_selesai']);

        $peta = [];
        foreach ($baris as $row) {
            $mulai = $row->tanggal_mulai->copy()->startOfDay()->max($awal);
            $selesai = $row->tanggal_selesai->copy()->startOfDay()->min($akhir);
            for ($d = $mulai->copy(); $d->lte($selesai); $d->addDay()) {
                if ($d->isWeekday()) {
                    $peta[$row->id_fasilitas][$d->toDateString()] = true;
                }
            }
        }

        return $peta;
    }
}
