<?php

namespace Database\Seeders;

use App\Enums\SatuanSewa;
use App\Models\Fasilitas;
use App\Models\Reservasi;
use App\Services\CartService;
use App\Services\StatusOtomatisService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Data dummy satu tahun (2026): 2 admin, beberapa pemesan, dan reservasi Januari–Desember
 * lengkap dengan dokumen, riwayat status, faktur, dan riwayat unduh laporan.
 *
 * PERHATIAN: seeder ini MENGOSONGKAN data transaksi & akun (admin, pemesan, reservasi,
 * dokumen, riwayat, faktur, laporan, keranjang) sebelum mengisi ulang. Lantai, fasilitas,
 * jenis sewa, dan tarif tidak disentuh. Jalankan:
 *     php artisan db:seed --class=DataDummy2026Seeder
 *
 * Data mengikuti aturan sistem: tidak ada jadwal yang bentrok, sewa per jam 08.00–16.00
 * hari kerja, sewa harian hanya hari kerja, Convention Hall hanya per hari (1 hari),
 * sewa bulanan minimal 3 bulan dengan dokumen persyaratan, dan status menyesuaikan waktu
 * saat seeder dijalankan (lewat = Selesai/Kadaluwarsa, akan datang = Disetujui/Menunggu).
 */
class DataDummy2026Seeder extends Seeder
{
    public const SANDI_ADMIN = 'Admin2026!';
    public const SANDI_PEMESAN = 'Pemesan2026!';

    private const JAM_BUKA = 8;
    private const JAM_TUTUP = 16;

    private const ADMIN = [
        ['nama_admin' => 'Rina Kartika', 'email' => 'admin@waduh.test', 'alamat' => 'Jl. Raya Baros No. 78, Cimahi Selatan'],
        ['nama_admin' => 'Dimas Prasetyo', 'email' => 'dimas.admin@waduh.test', 'alamat' => 'Jl. Kolonel Masturi No. 12, Cimahi Utara'],
    ];

    private const PEMESAN = [
        ['Andi Pratama', 'andi.pratama@waduh.test', 'Founder Startup Teknologi', 29, 'Jl. Gatot Subroto No. 21, Cimahi Tengah', '081221345678'],
        ['Siti Rahmawati', 'siti.rahmawati@waduh.test', 'HR Manager', 34, 'Jl. Sangkuriang No. 8, Cimahi Utara', '081322456789'],
        ['Budi Santoso', 'budi.santoso@waduh.test', 'Konsultan Bisnis', 41, 'Jl. Amir Machmud No. 45, Cimahi Tengah', '081223567890'],
        ['Dewi Lestari', 'dewi.lestari@waduh.test', 'Penyelenggara Acara', 31, 'Jl. Cihanjuang No. 102, Cimahi Utara', '085721678901'],
        ['Rizky Firmansyah', 'rizky.firmansyah@waduh.test', 'Pengembang Perangkat Lunak', 26, 'Jl. Leuwigajah No. 17, Cimahi Selatan', '087822789012'],
        ['Nadia Putri', 'nadia.putri@waduh.test', 'Desainer Grafis Lepas', 24, 'Jl. Pesantren No. 33, Cimahi Utara', '081394890123'],
        ['Fajar Nugraha', 'fajar.nugraha@waduh.test', 'Pemilik UMKM Kuliner', 38, 'Jl. Encep Kartawiria No. 9, Cimahi Utara', '082115901234'],
        ['Maya Anggraini', 'maya.anggraini@waduh.test', 'Dosen', 45, 'Jl. Terusan Jenderal Sudirman No. 4, Cimahi Tengah', '081220012345'],
        ['Hendra Wijaya', 'hendra.wijaya@waduh.test', 'Manajer Proyek', 36, 'Jl. Melong Raya No. 56, Cimahi Selatan', '081312123456'],
        ['Lina Marlina', 'lina.marlina@waduh.test', 'Pelatih Kewirausahaan', 33, 'Jl. Kebon Kopi No. 28, Cimahi Selatan', '085224234567'],
    ];

    private const KEPERLUAN = [
        'Working Space'    => ['Rapat koordinasi tim', 'Wawancara calon karyawan', 'Rapat dengan klien', 'Pelatihan internal tim', 'Presentasi proyek', 'Diskusi perencanaan produk', 'Kantor operasional tim'],
        'Co-Working Space' => ['Kerja harian tim kecil', 'Ruang kerja startup', 'Sesi mentoring', 'Pengerjaan proyek klien', 'Kantor sementara', 'Diskusi kelompok'],
        'Convention Hall'  => ['Seminar kewirausahaan', 'Workshop digital marketing', 'Peluncuran produk', 'Pelatihan UMKM se-Kota Cimahi', 'Talkshow teknologi', 'Rapat umum anggota'],
    ];

    private const ALASAN_TOLAK = [
        'Dokumen persyaratan belum lengkap. Silakan ajukan kembali dengan dokumen legalitas yang sesuai.',
        'Ruangan dijadwalkan untuk pemeliharaan pada periode tersebut.',
        'Jumlah peserta kegiatan tidak sesuai dengan ketentuan penggunaan ruangan.',
        'Keperluan kegiatan tidak sesuai dengan peruntukan ruangan.',
    ];

    /** @var array<int, array<string, array<int, array{0:int,1:int}>>> okupansi [id_fasilitas][Y-m-d] => [[menitMulai, menitSelesai], ...] */
    private array $okupansi = [];

    /** @var string[] kode transaksi yang sudah dipakai */
    private array $kodeTerpakai = [];

    private Carbon $sekarang;

    public function run(): void
    {
        mt_srand(2026);
        $this->sekarang = now();

        // TRUNCATE di MySQL melakukan commit implisit, jadi dijalankan di luar transaksi.
        $this->kosongkan();

        DB::transaction(function () {
            $admin = $this->buatAdmin();
            $pemesan = $this->buatPemesan();
            $transaksi = $this->buatReservasi($admin, $pemesan);
            $this->buatFaktur($transaksi);
            $this->buatLaporan($admin);
            $this->rapikanTanggalDaftarPemesan();
        });

        $this->ringkasan();
    }

    // ------------------------------------------------------------------
    // Persiapan
    // ------------------------------------------------------------------

    private function kosongkan(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['faktur', 'riwayat_status', 'dokumen_persyaratan', 'laporan', 'reservasi', 'keranjang_pemesan', 'pemesan', 'admin', 'password_reset_tokens'] as $tabel) {
            DB::table($tabel)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Storage::disk('public')->deleteDirectory('dokumen/dummy-2026');
    }

    /** @return int[] id_admin */
    private function buatAdmin(): array
    {
        $dibuat = Carbon::create(2026, 1, 2, 8, 0);
        $ids = [];
        foreach (self::ADMIN as $i => $a) {
            $ids[] = DB::table('admin')->insertGetId([
                'nama_admin'  => $a['nama_admin'],
                'email'       => $a['email'],
                'password'    => Hash::make(self::SANDI_ADMIN),
                // Admin pertama dipakai sebagai kontak di beranda — nomor sama dengan config institusi.
                'no_whatsapp' => $i === 0 ? '+'.config('institusi.whatsapp') : '+6281298765432',
                'alamat'      => $a['alamat'],
                'created_at'  => $dibuat,
                'updated_at'  => $dibuat,
            ]);
        }

        return $ids;
    }

    /** @return array<int, array{id:int, nama:string}> */
    private function buatPemesan(): array
    {
        $hasil = [];
        foreach (self::PEMESAN as [$nama, $email, $pekerjaan, $usia, $alamat, $telp]) {
            $dibuat = Carbon::create(2026, 1, 3, 9, 0);
            $hasil[] = [
                'id' => DB::table('pemesan')->insertGetId([
                    'nama_lengkap' => $nama,
                    'alamat'       => $alamat,
                    'usia'         => $usia,
                    'pekerjaan'    => $pekerjaan,
                    'no_telepon'   => $telp,
                    'email'        => $email,
                    'password'     => Hash::make(self::SANDI_PEMESAN),
                    'created_at'   => $dibuat,
                    'updated_at'   => $dibuat,
                ]),
                'nama' => $nama,
            ];
        }

        return $hasil;
    }

    // ------------------------------------------------------------------
    // Reservasi
    // ------------------------------------------------------------------

    /**
     * Susun transaksi per hari kerja sepanjang 2026, lalu simpan dengan status yang sesuai waktu.
     *
     * @return array<int, array{kode:string, status:string, anchor:int, diproses:?Carbon}>
     */
    private function buatReservasi(array $admin, array $pemesan): array
    {
        $fasilitas = Fasilitas::with(['tarifSewa' => fn ($q) => $q->where('status_aktif', 'Aktif'), 'tarifSewa.jenisSewa'])
            ->where('status_aktif', 'Aktif')
            ->get()
            ->groupBy('kategori_fasilitas');

        $transaksi = [];
        $hari = Carbon::create(2026, 1, 12);
        $akhir = Carbon::create(2026, 12, 31);

        for (; $hari->lte($akhir); $hari->addDay()) {
            if ($hari->isWeekend()) {
                continue;
            }

            // Pemesanan untuk tanggal yang masih jauh di depan lebih sedikit (belum banyak yang memesan).
            $jarakHari = $this->sekarang->copy()->startOfDay()->diffInDays($hari, false);
            $peluang = match (true) {
                $jarakHari > 60 => [55, 85],
                $jarakHari > 21 => [35, 75],
                default         => [20, 60],
            };

            $acak = mt_rand(1, 100);
            $jumlah = $acak <= $peluang[0] ? 0 : ($acak <= $peluang[1] ? 1 : 2);

            for ($n = 0; $n < $jumlah; $n++) {
                $hasil = $this->satuTransaksi($hari->copy(), $fasilitas, $admin, $pemesan);
                if ($hasil) {
                    $transaksi[] = $hasil;
                }
            }

            // Sewa bulanan: rata-rata ±1 per dua minggu, dimulai pada hari kerja.
            if ($hari->month <= 10 && mt_rand(1, 100) <= 10) {
                $hasil = $this->satuTransaksi($hari->copy(), $fasilitas, $admin, $pemesan, SatuanSewa::Bulan);
                if ($hasil) {
                    $transaksi[] = $hasil;
                }
            }
        }

        return $transaksi;
    }

    private function satuTransaksi(Carbon $tanggal, $fasilitas, array $admin, array $pemesan, ?SatuanSewa $paksaSatuan = null): ?array
    {
        // Pilih kategori & jenis sewa.
        $r = mt_rand(1, 100);
        [$kategori, $satuan] = $paksaSatuan
            ? [mt_rand(1, 100) <= 65 ? 'Co-Working Space' : 'Working Space', $paksaSatuan]
            : match (true) {
                $r <= 10 => ['Convention Hall', SatuanSewa::Hari],
                $r <= 45 => ['Working Space', SatuanSewa::Jam],
                $r <= 65 => ['Co-Working Space', SatuanSewa::Jam],
                $r <= 83 => ['Working Space', SatuanSewa::Hari],
                default  => ['Co-Working Space', SatuanSewa::Hari],
            };

        $slot = $this->slot($tanggal, $satuan, $kategori);
        if ($slot['tanggal_selesai'] > '2026-12-31') {
            return null;
        }
        $jumlahRuangan = ($kategori === 'Co-Working Space' && mt_rand(1, 100) <= 30) ? mt_rand(2, 3) : 1;

        // Cari ruangan yang masih kosong untuk jadwal ini.
        $kandidat = $fasilitas[$kategori]->shuffle();
        $dipilih = [];
        foreach ($kandidat as $f) {
            $tarif = $f->tarifSewa->first(fn ($t) => $t->jenisSewa->satuan === $satuan);
            if ($tarif && $this->kosong($f->id_fasilitas, $slot)) {
                $dipilih[] = [$f, $tarif];
                if (count($dipilih) === $jumlahRuangan) {
                    break;
                }
            }
        }
        if ($dipilih === []) {
            return null;
        }

        $p = $pemesan[array_rand($pemesan)];
        $idAdmin = $admin[mt_rand(0, 100) <= 60 ? 0 : 1];
        $waktu = $this->alurWaktu($slot, $satuan);
        $kode = $this->kodeBaru();
        $multi = count($dipilih) > 1;
        $keperluan = self::KEPERLUAN[$kategori][array_rand(self::KEPERLUAN[$kategori])];
        $dokumen = $satuan === SatuanSewa::Bulan ? $this->buatFileDokumen($kode) : [];

        $anchor = null;
        foreach ($dipilih as $i => [$f, $tarif]) {
            $this->tandai($f->id_fasilitas, $slot);

            $durasi = $this->durasi($slot, $satuan);
            $kapasitas = (int) $f->kapasitas;
            $pengguna = $kategori === 'Convention Hall' ? mt_rand(20, $kapasitas) : mt_rand(max(1, (int) ceil($kapasitas / 3)), $kapasitas);
            $status = $waktu['status'];

            $id = DB::table('reservasi')->insertGetId([
                'id_pemesan'       => $p['id'],
                'id_tarif_sewa'    => $tarif->id_tarif_sewa,
                'id_admin'         => in_array($status, ['Disetujui', 'Selesai', 'Ditolak'], true) ? $idAdmin : null,
                'kode_reservasi'   => Reservasi::kodeRuangan($kode, $i, $multi),
                'kode_transaksi'   => $kode,
                'tanggal_mulai'    => $slot['tanggal_mulai'],
                'tanggal_selesai'  => $slot['tanggal_selesai'],
                'jam_mulai'        => $slot['jam_mulai'],
                'jam_selesai'      => $slot['jam_selesai'],
                'durasi'           => $durasi,
                'jumlah_pengguna'  => $pengguna,
                'keperluan'        => $keperluan,
                'harga_satuan'     => $tarif->harga,
                'total_biaya'      => (float) $tarif->harga * $durasi,
                'status_reservasi' => $status,
                'lock_status'      => match ($status) {
                    'Menunggu'  => 'pending_approval',
                    'Disetujui' => 'confirmed',
                    default     => 'released',
                },
                'lock_expires_at'  => null,
                'tanggal_diproses' => in_array($status, ['Disetujui', 'Selesai', 'Ditolak'], true) ? $waktu['diproses'] : null,
                'created_at'       => $waktu['dibuat'],
                'updated_at'       => $waktu['terakhir'],
            ]);
            $anchor ??= $id;

            $this->buatDokumen($id, $dokumen, $status, $waktu);
            $this->buatRiwayat($id, $status, $idAdmin, $waktu);
        }

        return ['kode' => $kode, 'status' => $waktu['status'], 'anchor' => $anchor, 'diproses' => $waktu['diproses']];
    }

    /** @return array{tanggal_mulai:string, tanggal_selesai:string, jam_mulai:?string, jam_selesai:?string} */
    private function slot(Carbon $tanggal, SatuanSewa $satuan, string $kategori): array
    {
        if ($satuan === SatuanSewa::Jam) {
            $mulai = mt_rand(self::JAM_BUKA, self::JAM_TUTUP - 1);
            $selesai = min(self::JAM_TUTUP, $mulai + mt_rand(1, 4));

            return [
                'tanggal_mulai'   => $tanggal->toDateString(),
                'tanggal_selesai' => $tanggal->toDateString(),
                'jam_mulai'       => sprintf('%02d:00', $mulai),
                'jam_selesai'     => sprintf('%02d:00', $selesai),
            ];
        }

        if ($satuan === SatuanSewa::Bulan) {
            $bulan = [3, 3, 3, 4, 6][mt_rand(0, 4)];
            // Seluruh data berada di tahun 2026: masa sewa dipersingkat bila melewati akhir tahun.
            while ($bulan > 3 && $tanggal->copy()->addMonthsNoOverflow($bulan)->year > 2026) {
                $bulan--;
            }

            return [
                'tanggal_mulai'   => $tanggal->toDateString(),
                'tanggal_selesai' => $tanggal->copy()->addMonthsNoOverflow($bulan)->toDateString(),
                'jam_mulai'       => null,
                'jam_selesai'     => null,
            ];
        }

        // Per Hari: hanya hari kerja (tidak melewati akhir pekan); Convention Hall selalu 1 hari.
        $sisaMingguIni = 5 - $tanggal->dayOfWeekIso; // Jumat = 0
        $lama = $kategori === 'Convention Hall' ? 0 : min(mt_rand(0, 2), $sisaMingguIni);

        return [
            'tanggal_mulai'   => $tanggal->toDateString(),
            'tanggal_selesai' => $tanggal->copy()->addDays($lama)->toDateString(),
            'jam_mulai'       => null,
            'jam_selesai'     => null,
        ];
    }

    private function durasi(array $slot, SatuanSewa $satuan): int
    {
        return match ($satuan) {
            SatuanSewa::Jam   => (int) $slot['jam_selesai'] - (int) $slot['jam_mulai'],
            SatuanSewa::Hari  => Carbon::parse($slot['tanggal_mulai'])->diffInDays(Carbon::parse($slot['tanggal_selesai'])) + 1,
            SatuanSewa::Bulan => max(1, CartService::hitungBulan($slot['tanggal_mulai'], $slot['tanggal_selesai'])['ditagih']),
        };
    }

    /**
     * Tentukan status akhir dan waktu tiap perubahannya, konsisten dengan aturan sistem:
     * batas persetujuan (Kadaluwarsa) dan berakhirnya masa penggunaan (Selesai).
     *
     * @return array{status:string, dibuat:Carbon, diproses:?Carbon, berubah:?Carbon, terakhir:Carbon, berakhir:Carbon}
     */
    private function alurWaktu(array $slot, SatuanSewa $satuan): array
    {
        $mulai = Carbon::parse($slot['tanggal_mulai'].' '.($slot['jam_mulai'] ?? '08:00'));
        $berakhir = Carbon::parse($slot['tanggal_selesai'].' '.($slot['jam_selesai'] ?? StatusOtomatisService::JAM_TUTUP_OPERASIONAL));
        $batas = match ($satuan) {
            SatuanSewa::Jam   => Carbon::parse($slot['tanggal_mulai'].' '.$slot['jam_selesai']),
            SatuanSewa::Hari  => Carbon::parse($slot['tanggal_mulai'].' '.StatusOtomatisService::JAM_TUTUP_OPERASIONAL),
            SatuanSewa::Bulan => Carbon::parse($slot['tanggal_mulai'])->addDay()->startOfDay(),
        };

        // Waktu pengajuan: 3–30 hari sebelum mulai, tidak sebelum 5 Januari 2026 dan tidak setelah sekarang.
        $dibuat = $mulai->copy()->subDays(mt_rand(3, 30))->setTime(mt_rand(8, 20), mt_rand(0, 59));
        $dibuat = $dibuat->max(Carbon::create(2026, 1, 5, 9, 0))->min($this->sekarang->copy()->subMinutes(mt_rand(30, 600)));
        if ($dibuat->gte($mulai)) {
            $dibuat = $mulai->copy()->subHours(6);
        }

        // Diproses admin 1 jam – 2 hari setelah diajukan, selalu sebelum jadwal dimulai.
        $diproses = $dibuat->copy()->addMinutes(mt_rand(60, 2880))->min($mulai->copy()->subHour());
        if ($diproses->lte($dibuat)) {
            $diproses = $dibuat->copy()->addMinutes(20);
        }

        $sudahLewat = $berakhir->lte($this->sekarang);
        $acak = mt_rand(1, 100);

        if ($sudahLewat) {
            $status = match (true) {
                $acak <= 76 => 'Selesai',
                $acak <= 84 => 'Ditolak',
                $acak <= 91 => 'Dibatalkan',
                default     => 'Kadaluwarsa',
            };
        } elseif ($batas->lte($this->sekarang)) {
            // Sedang berlangsung: hanya yang sudah disetujui yang masih aktif.
            $status = 'Disetujui';
        } else {
            $status = match (true) {
                $acak <= 50 => 'Disetujui',
                $acak <= 82 => 'Menunggu',
                $acak <= 91 => 'Ditolak',
                default     => 'Dibatalkan',
            };
        }

        // Perubahan oleh admin/pemesan tidak boleh terjadi di masa depan.
        if (in_array($status, ['Disetujui', 'Selesai', 'Ditolak'], true) && $diproses->gt($this->sekarang)) {
            $diproses = $dibuat->copy()->addMinutes(15)->min($this->sekarang->copy()->subMinutes(5));
        }

        $berubah = match ($status) {
            'Selesai'     => $berakhir->copy(),
            'Kadaluwarsa' => $batas->copy()->addMinutes(mt_rand(1, 30)),
            'Dibatalkan'  => $dibuat->copy()->addMinutes(mt_rand(60, 1440))->min($mulai->copy()->subHours(2))->min($this->sekarang->copy()->subMinutes(5))->max($dibuat->copy()->addMinutes(10)),
            default       => null,
        };

        return [
            'status'   => $status,
            'dibuat'   => $dibuat,
            'diproses' => in_array($status, ['Disetujui', 'Selesai', 'Ditolak'], true) ? $diproses : null,
            'berubah'  => $berubah,
            'terakhir' => $berubah ?? (in_array($status, ['Disetujui', 'Ditolak'], true) ? $diproses : $dibuat),
            'berakhir' => $berakhir,
        ];
    }

    private function buatRiwayat(int $idReservasi, string $status, int $idAdmin, array $waktu): void
    {
        $baris = [];
        $tambah = function (string $dari, string $ke, ?int $admin, Carbon $kapan, ?string $ket) use (&$baris, $idReservasi) {
            $baris[] = [
                'id_reservasi'      => $idReservasi,
                'id_admin'          => $admin,
                'status_sebelumnya' => $dari,
                'status_baru'       => $ke,
                'tanggal_perubahan' => $kapan,
                'keterangan'        => $ket,
                'created_at'        => $kapan,
                'updated_at'        => $kapan,
            ];
        };

        match ($status) {
            'Disetujui'   => $tambah('Menunggu', 'Disetujui', $idAdmin, $waktu['diproses'], null),
            'Selesai'     => [
                $tambah('Menunggu', 'Disetujui', $idAdmin, $waktu['diproses'], null),
                $tambah('Disetujui', 'Selesai', $idAdmin, $waktu['berubah'], 'Waktu penggunaan reservasi ini sudah berakhir.'),
            ],
            'Ditolak'     => $tambah('Menunggu', 'Ditolak', $idAdmin, $waktu['diproses'], self::ALASAN_TOLAK[array_rand(self::ALASAN_TOLAK)]),
            'Dibatalkan'  => $tambah('Menunggu', 'Dibatalkan', null, $waktu['berubah'], 'Dibatalkan oleh pemesan'),
            'Kadaluwarsa' => $tambah('Menunggu', 'Kadaluwarsa', null, $waktu['berubah'], 'Tidak diproses admin sampai melewati batas waktu persetujuan ruangan ini.'),
            default       => null,
        };

        if ($baris !== []) {
            DB::table('riwayat_status')->insert($baris);
        }
    }

    // ------------------------------------------------------------------
    // Dokumen persyaratan sewa bulanan
    // ------------------------------------------------------------------

    /** Buat 1–2 berkas PDF kecil di disk publik; satu set berlaku untuk semua ruangan transaksi. */
    private function buatFileDokumen(string $kode): array
    {
        $jenis = [['company-profile', 'Company Profile'], ['ktp-penanggung-jawab', 'KTP Penanggung Jawab'], ['akta-pendirian', 'Akta Pendirian Usaha']];
        $pilih = array_slice($jenis, 0, mt_rand(1, 2));

        return array_map(function ($j) use ($kode) {
            $nama = $j[0].'-'.strtolower($kode).'.pdf';
            $path = 'dokumen/dummy-2026/'.$nama;
            Storage::disk('public')->put($path, $this->pdfSederhana($j[1].' - '.$kode));

            return ['nama' => $nama, 'path' => $path];
        }, $pilih);
    }

    private function buatDokumen(int $idReservasi, array $dokumen, string $status, array $waktu): void
    {
        foreach ($dokumen as $d) {
            DB::table('dokumen_persyaratan')->insert([
                'id_reservasi'      => $idReservasi,
                'jenis_dokumen'     => 'Persyaratan Sewa Bulanan',
                'nama_file'         => $d['nama'],
                'lokasi_file'       => $d['path'],
                'tanggal_upload'    => $waktu['dibuat'],
                // Disetujui/Selesai wajib Valid (syarat checklist); Ditolak karena dokumen tidak valid.
                'status_verifikasi' => match ($status) {
                    'Disetujui', 'Selesai' => 'Valid',
                    'Ditolak'              => 'Tidak Valid',
                    default                => 'Menunggu',
                },
                'created_at'        => $waktu['dibuat'],
                'updated_at'        => $waktu['diproses'] ?? $waktu['dibuat'],
            ]);
        }
    }

    private function pdfSederhana(string $judul): string
    {
        $teks = str_replace(['(', ')'], '', $judul);
        $isi = "BT /F1 18 Tf 72 760 Td ({$teks}) Tj 0 -28 Td /F1 11 Tf (Dokumen contoh untuk data dummy sistem WADUH.) Tj ET";

        $objek = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($isi)." >>\nstream\n{$isi}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offset = [];
        foreach ($objek as $i => $o) {
            $offset[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$o}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objek) + 1)."\n0000000000 65535 f \n";
        foreach ($offset as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf.'trailer << /Size '.(count($objek) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    // ------------------------------------------------------------------
    // Faktur & laporan
    // ------------------------------------------------------------------

    /** Faktur untuk ±80% transaksi yang disetujui, bernomor urut sesuai tanggal terbit. */
    private function buatFaktur(array $transaksi): void
    {
        $terbit = collect($transaksi)
            ->filter(fn ($t) => in_array($t['status'], ['Disetujui', 'Selesai'], true) && mt_rand(1, 100) <= 80)
            ->map(fn ($t) => $t + ['tanggal' => $t['diproses']->copy()->addDays(mt_rand(0, 2))->min($this->sekarang)])
            ->sortBy(fn ($t) => $t['tanggal']->timestamp)
            ->values();

        foreach ($terbit as $i => $t) {
            DB::table('faktur')->insert([
                'id_reservasi'   => $t['anchor'],
                'nomor_faktur'   => sprintf('INV/%d/%04d', $t['tanggal']->year, $i + 1),
                'tanggal_faktur' => $t['tanggal']->toDateString(),
                'created_at'     => $t['tanggal'],
                'updated_at'     => $t['tanggal'],
            ]);
        }
    }

    /** Riwayat unduh laporan bulanan: tiap bulan yang sudah lewat diunduh di awal bulan berikutnya. */
    private function buatLaporan(array $admin): void
    {
        $bulanId = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        for ($b = 1; $b <= 12; $b++) {
            $tanggal = Carbon::create(2026, $b, 1)->addMonth()->setTime(9, mt_rand(0, 59));
            while ($tanggal->isWeekend()) {
                $tanggal->addDay();
            }
            if ($tanggal->gt($this->sekarang)) {
                break;
            }

            DB::table('laporan')->insert([
                'id_admin'      => $admin[$b % 2],
                'tanggal'       => $tanggal->toDateString(),
                'jenis_laporan' => "Laporan Data Reservasi Bulan {$bulanId[$b]} 2026",
                'created_at'    => $tanggal,
                'updated_at'    => $tanggal,
            ]);
        }
    }

    /** Tanggal daftar pemesan = sehari sebelum pengajuan reservasi pertamanya. */
    private function rapikanTanggalDaftarPemesan(): void
    {
        $pertama = DB::table('reservasi')->selectRaw('id_pemesan, MIN(created_at) AS awal')->groupBy('id_pemesan')->pluck('awal', 'id_pemesan');
        foreach ($pertama as $id => $awal) {
            $daftar = Carbon::parse($awal)->subDay()->max(Carbon::create(2026, 1, 3, 9, 0));
            DB::table('pemesan')->where('id_pemesan', $id)->update(['created_at' => $daftar, 'updated_at' => $daftar]);
        }
    }

    // ------------------------------------------------------------------
    // Okupansi (pencegah jadwal bentrok)
    // ------------------------------------------------------------------

    /** @return array<string, array{0:int,1:int}> tanggal => [menit mulai, menit selesai] */
    private function blok(array $slot): array
    {
        $blok = [];
        $hari = Carbon::parse($slot['tanggal_mulai']);
        $akhir = Carbon::parse($slot['tanggal_selesai']);
        $rentang = $slot['jam_mulai']
            ? [(int) $slot['jam_mulai'] * 60, (int) $slot['jam_selesai'] * 60]
            : [self::JAM_BUKA * 60, self::JAM_TUTUP * 60];

        for (; $hari->lte($akhir); $hari->addDay()) {
            $blok[$hari->toDateString()] = $rentang;
        }

        return $blok;
    }

    private function kosong(int $idFasilitas, array $slot): bool
    {
        foreach ($this->blok($slot) as $tgl => [$m, $s]) {
            foreach ($this->okupansi[$idFasilitas][$tgl] ?? [] as [$m2, $s2]) {
                if ($m < $s2 && $m2 < $s) {
                    return false;
                }
            }
        }

        return true;
    }

    private function tandai(int $idFasilitas, array $slot): void
    {
        foreach ($this->blok($slot) as $tgl => $rentang) {
            $this->okupansi[$idFasilitas][$tgl][] = $rentang;
        }
    }

    private function kodeBaru(): string
    {
        do {
            $kode = Reservasi::kodeAcak();
        } while (in_array($kode, $this->kodeTerpakai, true));
        $this->kodeTerpakai[] = $kode;

        return $kode;
    }

    private function ringkasan(): void
    {
        if (! $this->command) {
            return;
        }

        $perStatus = DB::table('reservasi')->selectRaw('status_reservasi, COUNT(*) AS n')->groupBy('status_reservasi')->pluck('n', 'status_reservasi');
        $this->command->info('Data dummy 2026 selesai dibuat.');
        $this->command->line('  Reservasi : '.DB::table('reservasi')->count().' baris, '.DB::table('reservasi')->distinct()->count('kode_transaksi').' transaksi');
        foreach ($perStatus as $s => $n) {
            $this->command->line("    - {$s}: {$n}");
        }
        $this->command->line('  Admin     : '.implode(', ', array_column(self::ADMIN, 'email')).' (sandi: '.self::SANDI_ADMIN.')');
        $this->command->line('  Pemesan   : '.count(self::PEMESAN).' akun, mis. '.self::PEMESAN[0][1].' (sandi: '.self::SANDI_PEMESAN.')');
    }
}
