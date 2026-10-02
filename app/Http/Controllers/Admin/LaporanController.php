<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusReservasi;
use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Reservasi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laporan Data Reservasi — rekap reservasi yang SUDAH DISETUJUI (bukan lagi rekap
 * inventaris fasilitas), difilter per bulan. Begitu admin menyetujui sebuah reservasi,
 * baris itu otomatis muncul di laporan bulan tanggal_mulai-nya (tanpa langkah tambahan,
 * karena data selalu diquery langsung dari tabel Reservasi).
 */
class LaporanController extends Controller
{
    private const BULAN_ID = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** Banyaknya tahun ke belakang yang selalu tersedia di filter, walau belum ada datanya. */
    private const RENTANG_TAHUN_LALU = 5;

    /** Batas bawah tahun yang diterima dari query string. */
    private const TAHUN_MINIMUM = 2000;

    public function index(Request $request): View
    {
        $data = $this->build($request);

        // Filter interaktif: request AJAX cukup dibalas fragmen hasil, tanpa layout.
        if ($request->ajax()) {
            return view('admin.laporan.partials.hasil', $data);
        }

        return view('admin.laporan.index', array_merge($data, [
            'daftarBulan' => self::BULAN_ID,
            'daftarTahun' => $this->daftarTahun(),
        ]));
    }

    public function exportPdf(Request $request): Response
    {
        $data = $this->build($request);

        Laporan::create([
            'id_admin'      => Auth::guard('admin')->id(),
            'tanggal'       => now(),
            'jenis_laporan' => $data['deskripsi'],
        ]);

        return Pdf::loadView('admin.pdf.laporan', $data)
            ->setPaper('a4', 'portrait')
            ->download('laporan-reservasi-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array{rows: \Illuminate\Support\Collection, totalPendapatan: float,
     *               bulan: int, tahun: int, judulBulan: string, deskripsi: string, adminNama: ?string, cetakWaktu: string}
     */
    private function build(Request $request): array
    {
        [$bulan, $tahun] = $this->periode($request);

        // Hanya reservasi yang SUDAH DISETUJUI (atau sudah Selesai — tetap tercatat pernah
        // disetujui) yang masuk laporan; Menunggu/Ditolak/Dibatalkan tidak dianggap "sudah dipesan".
        $rows = Reservasi::query()
            ->whereIn('status_reservasi', [StatusReservasi::Disetujui->value, StatusReservasi::Selesai->value])
            ->whereYear('tanggal_mulai', $tahun)
            ->whereMonth('tanggal_mulai', $bulan)
            ->with(['pemesan', 'tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa'])
            ->orderBy('tanggal_mulai')
            ->orderBy('kode_reservasi')
            ->get()
            ->map(function (Reservasi $r, int $i) {
                $fasilitas = $r->tarifSewa->fasilitas;

                // Sewa per Jam: tanggal_mulai selalu sama dengan tanggal_selesai, jadi rentang
                // tanggal tidak informatif — cukup satu tanggal plus jam mulai/selesai. Sewa
                // Harian/Bulanan sebaliknya tidak punya jam, jadi tampilkan rentang tanggalnya.
                if ($r->tarifSewa->jenisSewa->satuan->value === 'Jam') {
                    $periode = $r->tanggal_mulai->translatedFormat('d M Y');
                    if ($r->jam_mulai) {
                        $periode .= ', '.substr($r->jam_mulai, 0, 5).'–'.substr($r->jam_selesai, 0, 5).' WIB';
                    }
                } else {
                    $periode = $r->tanggal_mulai->translatedFormat('d M Y').' – '.$r->tanggal_selesai->translatedFormat('d M Y');
                }

                return [
                    'no'              => $i + 1,
                    'kode_reservasi'  => $r->kode_reservasi,
                    'nama'            => $r->pemesan?->nama_lengkap,
                    'uraian'          => $fasilitas->nama_fasilitas.' (Lantai '.$fasilitas->lantai->nomor_lantai.')',
                    'periode'         => $periode,
                    'total_harga'     => (float) $r->total_biaya,
                    'keterangan'      => 'Isi',
                    'volume'          => (float) $fasilitas->luas,
                ];
            });

        return [
            'rows'            => $rows,
            'totalPendapatan' => (float) $rows->sum('total_harga'),
            'bulan'           => $bulan,
            'tahun'           => $tahun,
            'judulBulan'      => self::BULAN_ID[$bulan].' '.$tahun,
            'deskripsi'       => 'Laporan Data Reservasi Bulan '.self::BULAN_ID[$bulan].' '.$tahun,
            'adminNama'       => Auth::guard('admin')->user()?->nama_admin,
            'cetakWaktu'      => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Bulan & tahun dari query string. Nilai di luar rentang (mis. ?bulan=13 atau tahun asal)
     * dikembalikan ke bulan berjalan supaya tidak memicu galat indeks BULAN_ID.
     *
     * @return array{0:int, 1:int}
     */
    private function periode(Request $request): array
    {
        $bulan = (int) $request->input('bulan');
        $tahun = (int) $request->input('tahun');

        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }
        if ($tahun < self::TAHUN_MINIMUM || $tahun > now()->year + 1) {
            $tahun = now()->year;
        }

        return [$bulan, $tahun];
    }

    /**
     * Pilihan tahun untuk filter, terbaru di atas: minimal RENTANG_TAHUN_LALU tahun ke belakang
     * (atau sejak reservasi pertama bila lebih lama) sampai tahun depan. Nilainya jumlah
     * reservasi yang masuk laporan pada tahun itu, untuk keterangan di pilihan.
     *
     * @return array<int, int> [tahun => jumlah reservasi]
     */
    private function daftarTahun(): array
    {
        $jumlahPerTahun = Reservasi::query()
            ->whereIn('status_reservasi', [StatusReservasi::Disetujui->value, StatusReservasi::Selesai->value])
            ->selectRaw('YEAR(tanggal_mulai) AS tahun, COUNT(*) AS jumlah')
            ->groupBy('tahun')
            ->pluck('jumlah', 'tahun');

        $awal = Reservasi::min('tanggal_mulai');
        $tahunAwal = min(
            $awal ? Carbon::parse($awal)->year : now()->year,
            now()->year - self::RENTANG_TAHUN_LALU,
        );
        $tahunAwal = max($tahunAwal, self::TAHUN_MINIMUM);

        $daftar = [];
        foreach (range(now()->year + 1, $tahunAwal) as $t) {
            $daftar[$t] = (int) ($jumlahPerTahun[$t] ?? 0);
        }

        return $daftar;
    }
}
