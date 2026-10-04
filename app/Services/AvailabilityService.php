<?php

namespace App\Services;

use App\Enums\SatuanSewa;
use App\Enums\StatusReservasi;
use App\Models\Fasilitas;
use App\Models\Reservasi;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Logika ketersediaan fasilitas + hold pra-checkout (Cache).
 *
 * Dua sumber "blokir" ketersediaan:
 *   1. Baris Reservasi aktif (status Menunggu/Disetujui) yang jadwalnya bentrok.
 *   2. Cache hold aktif milik SESSION LAIN (hold pra-checkout, TTL 15 menit).
 *
 * Hold disimpan di Cache (bukan tabel) karena Reservasi.id_pemesan NOT NULL sedangkan
 * data pemesan baru tersedia saat checkout — lihat db-spec-stage2-pemesan.md.
 */
class AvailabilityService
{
    /** Menit TTL hold pra-checkout. */
    public const HOLD_TTL_MINUTES = 15;

    /** Jam operasional gedung (08.00-16.00). */
    public const JAM_BUKA = 8;

    public const JAM_TUTUP = 16;

    /**
     * Status reservasi yang dianggap masih memblokir slot.
     * (Method, bukan const — PHP 8.1 tak mengizinkan Enum->value di ekspresi konstan.)
     *
     * @return string[]
     */
    public static function statusAktif(): array
    {
        return [
            StatusReservasi::Menunggu->value,
            StatusReservasi::Disetujui->value,
        ];
    }

    // ---------------------------------------------------------------------
    // Hold pra-checkout (Cache)
    // ---------------------------------------------------------------------

    public function holdKey(int $fasilitasId, int $tarifId, string $tanggalMulai, ?string $jamMulai): string
    {
        // Format sesuai spesifikasi: hold:{id_fasilitas}:{id_tarif_sewa}:{tanggal_mulai}:{jam_mulai}
        return sprintf('hold:%d:%d:%s:%s', $fasilitasId, $tarifId, $tanggalMulai, $jamMulai ?: 'full');
    }

    private function indexKey(int $fasilitasId): string
    {
        return 'hold_index:'.$fasilitasId;
    }

    /**
     * Pasang hold untuk satu item keranjang. Value = session id pemilik hold.
     * TTL 15 menit di-refresh tiap pemanggilan.
     */
    public function putHold(array $item, string $sessionId): void
    {
        $key = $this->holdKey($item['id_fasilitas'], $item['id_tarif_sewa'], $item['tanggal_mulai'], $item['jam_mulai'] ?? null);
        $ttl = now()->addMinutes(self::HOLD_TTL_MINUTES);

        Cache::put($key, $sessionId, $ttl);

        // Index per fasilitas supaya hold bisa ditelusuri saat cek bentrok (driver file tak bisa scan key).
        $indexKey = $this->indexKey($item['id_fasilitas']);
        $index = Cache::get($indexKey, []);
        $index[$key] = [
            'key'             => $key,
            'id_tarif_sewa'   => $item['id_tarif_sewa'],
            'tanggal_mulai'   => $item['tanggal_mulai'],
            'tanggal_selesai' => $item['tanggal_selesai'] ?? $item['tanggal_mulai'],
            'jam_mulai'       => $item['jam_mulai'] ?? null,
            'jam_selesai'     => $item['jam_selesai'] ?? null,
            'session_id'      => $sessionId,
        ];
        Cache::put($indexKey, $index, $ttl);
    }

    /** Lepas hold satu item (mis. item dihapus dari keranjang atau sudah jadi Reservasi). */
    public function releaseHold(array $item): void
    {
        $key = $this->holdKey($item['id_fasilitas'], $item['id_tarif_sewa'], $item['tanggal_mulai'], $item['jam_mulai'] ?? null);
        Cache::forget($key);

        $indexKey = $this->indexKey($item['id_fasilitas']);
        $index = Cache::get($indexKey, []);
        unset($index[$key]);
        if ($index) {
            Cache::put($indexKey, $index, now()->addMinutes(self::HOLD_TTL_MINUTES));
        } else {
            Cache::forget($indexKey);
        }
    }

    /**
     * Hold aktif untuk sebuah fasilitas. Entri yang key utamanya sudah expired dibersihkan.
     * $excludeSessionId → keluarkan hold milik session tsb (hold sendiri tidak memblokir diri).
     */
    public function activeHoldsFor(int $fasilitasId, ?string $excludeSessionId = null): array
    {
        $indexKey = $this->indexKey($fasilitasId);
        $index = Cache::get($indexKey, []);
        $active = [];
        $changed = false;

        foreach ($index as $key => $descriptor) {
            if (! Cache::has($key)) {
                unset($index[$key]);
                $changed = true;
                continue;
            }
            if ($excludeSessionId !== null && ($descriptor['session_id'] ?? null) === $excludeSessionId) {
                continue;
            }
            $active[] = $descriptor;
        }

        if ($changed) {
            $index
                ? Cache::put($indexKey, $index, now()->addMinutes(self::HOLD_TTL_MINUTES))
                : Cache::forget($indexKey);
        }

        return $active;
    }

    // ---------------------------------------------------------------------
    // Deteksi bentrok
    // ---------------------------------------------------------------------

    /**
     * Ada Reservasi aktif yang bentrok dengan slot yang diminta?
     * $status: status yang dianggap menempati jadwal (default Menunggu + Disetujui).
     */
    public function hasReservationConflict(int $fasilitasId, array $slot, ?int $ignoreReservasiId = null, ?array $status = null): bool
    {
        $rows = Reservasi::query()
            ->whereHas('tarifSewa', fn ($q) => $q->where('id_fasilitas', $fasilitasId))
            ->whereIn('status_reservasi', $status ?? self::statusAktif())
            ->whereDate('tanggal_mulai', '<=', $slot['tanggal_selesai'])
            ->whereDate('tanggal_selesai', '>=', $slot['tanggal_mulai'])
            ->when($ignoreReservasiId, fn ($q) => $q->where('id_reservasi', '!=', $ignoreReservasiId))
            ->get(['id_reservasi', 'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai']);

        foreach ($rows as $r) {
            $existing = [
                'tanggal_mulai'   => $r->tanggal_mulai->toDateString(),
                'tanggal_selesai' => $r->tanggal_selesai->toDateString(),
                'jam_mulai'       => $r->jam_mulai,
                'jam_selesai'     => $r->jam_selesai,
            ];
            if ($this->slotsConflict($slot, $existing)) {
                return true;
            }
        }

        return false;
    }

    /** Ada hold aktif dari session lain yang bentrok dengan slot yang diminta? */
    public function hasHoldConflict(int $fasilitasId, array $slot, string $currentSessionId): bool
    {
        foreach ($this->activeHoldsFor($fasilitasId, $currentSessionId) as $hold) {
            if ($this->slotsConflict($slot, $hold)) {
                return true;
            }
        }

        return false;
    }

    /** Slot benar-benar bebas: tidak bentrok Reservasi aktif maupun hold session lain. */
    public function slotAvailable(int $fasilitasId, array $slot, string $currentSessionId, ?int $ignoreReservasiId = null): bool
    {
        return ! $this->hasReservationConflict($fasilitasId, $slot, $ignoreReservasiId)
            && ! $this->hasHoldConflict($fasilitasId, $slot, $currentSessionId);
    }

    // ---------------------------------------------------------------------
    // Kondisi fasilitas (Tersedia / Sebagian Terisi / Terisi)
    // ---------------------------------------------------------------------

    /**
     * Seluruh blok jadwal yang memblokir fasilitas pada rentang tanggal: Reservasi aktif
     * (Menunggu/Disetujui) + hold keranjang milik session lain. Satu query untuk seluruh rentang.
     *
     * @return array<int, array{tanggal_mulai: string, tanggal_selesai: string, jam_mulai: ?string, jam_selesai: ?string}>
     */
    public function blokTerisi(int $fasilitasId, string $tanggalMulai, string $tanggalSelesai, ?string $currentSessionId = null, ?int $ignoreReservasiId = null): array
    {
        $blok = Reservasi::query()
            ->whereHas('tarifSewa', fn ($q) => $q->where('id_fasilitas', $fasilitasId))
            ->whereIn('status_reservasi', self::statusAktif())
            ->whereDate('tanggal_mulai', '<=', $tanggalSelesai)
            ->whereDate('tanggal_selesai', '>=', $tanggalMulai)
            ->when($ignoreReservasiId, fn ($q) => $q->where('id_reservasi', '!=', $ignoreReservasiId))
            ->get(['id_reservasi', 'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai'])
            ->map(fn (Reservasi $r) => [
                'tanggal_mulai'   => $r->tanggal_mulai->toDateString(),
                'tanggal_selesai' => $r->tanggal_selesai->toDateString(),
                'jam_mulai'       => $r->jam_mulai ? substr($r->jam_mulai, 0, 5) : null,
                'jam_selesai'     => $r->jam_selesai ? substr($r->jam_selesai, 0, 5) : null,
            ])
            ->all();

        foreach ($this->activeHoldsFor($fasilitasId, $currentSessionId) as $hold) {
            if ($hold['tanggal_mulai'] <= $tanggalSelesai && $hold['tanggal_selesai'] >= $tanggalMulai) {
                $blok[] = [
                    'tanggal_mulai'   => $hold['tanggal_mulai'],
                    'tanggal_selesai' => $hold['tanggal_selesai'],
                    'jam_mulai'       => ! empty($hold['jam_mulai']) ? substr($hold['jam_mulai'], 0, 5) : null,
                    'jam_selesai'     => ! empty($hold['jam_selesai']) ? substr($hold['jam_selesai'], 0, 5) : null,
                ];
            }
        }

        return $blok;
    }

    /**
     * Kondisi tiap hari pada rentang tanggal:
     *   bebas    = tidak ada jadwal sama sekali
     *   sebagian = ada reservasi per jam, tetapi jam operasional belum habis
     *   penuh    = ada reservasi harian/bulanan, atau seluruh jam operasional sudah terisi
     *
     * @return array<string, array{status: string, jam: array<int, array{mulai: string, selesai: string}>}>
     */
    public function kondisiHarian(int $fasilitasId, string $tanggalMulai, string $tanggalSelesai, ?string $currentSessionId = null, ?int $ignoreReservasiId = null): array
    {
        $blok = $this->blokTerisi($fasilitasId, $tanggalMulai, $tanggalSelesai, $currentSessionId, $ignoreReservasiId);
        $hasil = [];

        foreach (CarbonPeriod::create($tanggalMulai, $tanggalSelesai) as $hari) {
            $tgl = $hari->toDateString();
            $seharian = false;
            $jam = [];

            foreach ($blok as $b) {
                if ($b['tanggal_mulai'] > $tgl || $b['tanggal_selesai'] < $tgl) {
                    continue;
                }
                if (empty($b['jam_mulai'])) {
                    $seharian = true;
                    break;
                }
                $jam[] = ['mulai' => $b['jam_mulai'], 'selesai' => $b['jam_selesai']];
            }

            if ($seharian) {
                $hasil[$tgl] = ['status' => 'penuh', 'jam' => []];
                continue;
            }

            $jam = $this->gabungRentangJam($jam);
            $terisi = 0;
            for ($h = self::JAM_BUKA; $h < self::JAM_TUTUP; $h++) {
                $awal = sprintf('%02d:00', $h);
                $akhir = sprintf('%02d:00', $h + 1);
                foreach ($jam as $r) {
                    if ($r['mulai'] < $akhir && $r['selesai'] > $awal) {
                        $terisi++;
                        break;
                    }
                }
            }

            $hasil[$tgl] = [
                'status' => $terisi === 0 ? 'bebas' : ($terisi >= self::JAM_TUTUP - self::JAM_BUKA ? 'penuh' : 'sebagian'),
                'jam'    => $jam,
            ];
        }

        return $hasil;
    }

    /**
     * Status warna sebuah fasilitas untuk slot yang sedang dilihat:
     *   hijau  = Tersedia (seluruh rentang bebas)
     *   kuning = Sebagian Terisi
     *   merah  = Terisi (seluruh rentang penuh)
     * Slot ber-jam dievaluasi per blok 1 jam, slot tanpa jam dievaluasi per hari.
     */
    public function statusFasilitas(Fasilitas $fasilitas, array $slot, string $currentSessionId): string
    {
        $fasilitasId = $fasilitas->id_fasilitas;

        if (! empty($slot['jam_mulai'])) {
            $tgl = $slot['tanggal_mulai'];
            $blok = $this->blokTerisi($fasilitasId, $tgl, $tgl, $currentSessionId);
            $mulai = Carbon::parse($tgl.' '.$slot['jam_mulai']);
            $selesai = Carbon::parse($tgl.' '.$slot['jam_selesai']);

            $bebas = 0;
            $bentrok = 0;
            for ($jam = $mulai->copy(); $jam->lt($selesai); $jam->addHour()) {
                $subSlot = [
                    'tanggal_mulai'   => $tgl,
                    'tanggal_selesai' => $tgl,
                    'jam_mulai'       => $jam->format('H:i'),
                    'jam_selesai'     => $jam->copy()->addHour()->min($selesai)->format('H:i'),
                ];
                $kena = false;
                foreach ($blok as $b) {
                    if ($this->slotsConflict($subSlot, $b)) {
                        $kena = true;
                        break;
                    }
                }
                $kena ? $bentrok++ : $bebas++;
            }

            if ($bentrok === 0) {
                return 'hijau';
            }

            return $bebas === 0 ? 'merah' : 'kuning';
        }

        $harian = $this->kondisiHarian($fasilitasId, $slot['tanggal_mulai'], $slot['tanggal_selesai'], $currentSessionId);
        $status = array_column($harian, 'status');

        if (! in_array('sebagian', $status, true) && ! in_array('penuh', $status, true)) {
            return 'hijau';
        }

        return ! in_array('bebas', $status, true) && ! in_array('sebagian', $status, true) ? 'merah' : 'kuning';
    }

    /** Label kondisi untuk ditampilkan ke pengguna. */
    public static function labelStatus(string $status): string
    {
        return match ($status) {
            'merah'  => 'Terisi',
            'kuning' => 'Sebagian Terisi',
            default  => 'Tersedia',
        };
    }

    /**
     * Aturan pemesanan per jenis sewa berdasarkan kondisi fasilitas:
     *   Tersedia        → Jam, Hari, Bulan dapat dipesan
     *   Sebagian Terisi → hanya Jam (pada jam yang masih kosong)
     *   Terisi          → tidak ada yang dapat dipesan
     */
    public static function bisaDipesan(string $status, ?SatuanSewa $satuan): bool
    {
        return match ($status) {
            'hijau'  => true,
            'kuning' => $satuan === null || $satuan === SatuanSewa::Jam,
            default  => false,
        };
    }

    // ---------------------------------------------------------------------
    // Rincian bentrok + pesan untuk pemesan
    // ---------------------------------------------------------------------

    /**
     * Rincian bentrok slot yang diminta terhadap jadwal tersimpan.
     *
     * @return array{bentrok: bool, tanggal: string[], jam_terisi: array<int, array{mulai: string, selesai: string}>}
     */
    public function detailBentrok(int $fasilitasId, array $slot, ?string $currentSessionId = null, ?int $ignoreReservasiId = null): array
    {
        $harian = $this->kondisiHarian($fasilitasId, $slot['tanggal_mulai'], $slot['tanggal_selesai'], $currentSessionId, $ignoreReservasiId);

        if (! empty($slot['jam_mulai'])) {
            $hari = $harian[$slot['tanggal_mulai']] ?? ['status' => 'bebas', 'jam' => []];
            if ($hari['status'] === 'bebas') {
                return ['bentrok' => false, 'tanggal' => [], 'jam_terisi' => []];
            }
            if ($hari['jam'] === []) {
                // Terisi seharian oleh reservasi harian/bulanan.
                return ['bentrok' => true, 'tanggal' => [$slot['tanggal_mulai']], 'jam_terisi' => []];
            }

            $mulai = substr($this->normTime($slot['jam_mulai']), 0, 5);
            $selesai = substr($this->normTime($slot['jam_selesai']), 0, 5);
            $kena = false;
            foreach ($hari['jam'] as $r) {
                if ($mulai < $r['selesai'] && $selesai > $r['mulai']) {
                    $kena = true;
                    break;
                }
            }

            return ['bentrok' => $kena, 'tanggal' => $kena ? [$slot['tanggal_mulai']] : [], 'jam_terisi' => $hari['jam']];
        }

        $tanggal = [];
        foreach ($harian as $tgl => $hari) {
            if ($hari['status'] !== 'bebas') {
                $tanggal[] = $tgl;
            }
        }

        return ['bentrok' => $tanggal !== [], 'tanggal' => $tanggal, 'jam_terisi' => []];
    }

    /** Kalimat penjelasan bentrok untuk pemesan, lengkap dengan tanggal/jam yang menyebabkannya. */
    public function pesanBentrok(array $slot, array $detail): string
    {
        if (! empty($slot['jam_mulai'])) {
            $tanggal = Carbon::parse($slot['tanggal_mulai'])->translatedFormat('l, j F Y');
            if ($detail['jam_terisi'] === []) {
                return "Tanggal {$tanggal} sudah terisi penuh oleh reservasi lain. Silakan pilih tanggal lain.";
            }

            $diminta = $this->formatJam($slot['jam_mulai']).'–'.$this->formatJam($slot['jam_selesai']);
            $terisi = self::daftar(array_map(
                fn (array $r) => $this->formatJam($r['mulai']).'–'.$this->formatJam($r['selesai']),
                $detail['jam_terisi'],
            ));

            return "Jam {$diminta} pada {$tanggal} tidak tersedia. Jam yang sudah terisi: {$terisi}. Silakan pilih jam lain yang masih kosong.";
        }

        return 'Reservasi tidak dapat dilakukan karena terdapat jadwal sudah terisi pada tanggal '
            .self::ringkasTanggal($detail['tanggal']).'. Silakan ubah periode reservasi Anda.';
    }

    /**
     * Ringkas daftar tanggal jadi kalimat: tanggal berurutan digabung jadi rentang dan
     * dikelompokkan per bulan, mis. "10–12, 15, dan 20 Januari 2027 serta 3 Februari 2027".
     *
     * @param  string[]  $tanggal  Format Y-m-d.
     */
    public static function ringkasTanggal(array $tanggal): string
    {
        $tanggal = array_values(array_unique($tanggal));
        sort($tanggal);

        $perBulan = [];
        foreach ($tanggal as $tgl) {
            $perBulan[substr($tgl, 0, 7)][] = (int) substr($tgl, 8, 2);
        }

        $bagian = [];
        foreach ($perBulan as $bulan => $hari) {
            $rentang = [];
            $awal = $akhir = null;
            foreach ($hari as $h) {
                if ($awal !== null && $h === $akhir + 1) {
                    $akhir = $h;
                    continue;
                }
                if ($awal !== null) {
                    $rentang[] = $awal === $akhir ? (string) $awal : "{$awal}–{$akhir}";
                }
                $awal = $akhir = $h;
            }
            if ($awal !== null) {
                $rentang[] = $awal === $akhir ? (string) $awal : "{$awal}–{$akhir}";
            }

            $bagian[] = self::daftar($rentang).' '.Carbon::parse($bulan.'-01')->translatedFormat('F Y');
        }

        return count($bagian) <= 2 ? implode(' serta ', $bagian) : implode('; ', $bagian);
    }

    /** "a", "a dan b", "a, b, dan c". */
    private static function daftar(array $item): string
    {
        $item = array_values($item);

        return match (count($item)) {
            0       => '',
            1       => $item[0],
            2       => $item[0].' dan '.$item[1],
            default => implode(', ', array_slice($item, 0, -1)).', dan '.end($item),
        };
    }

    private function formatJam(string $jam): string
    {
        return str_replace(':', '.', substr($jam, 0, 5));
    }

    /**
     * Urutkan & gabungkan rentang jam yang bersinggungan/bertumpuk.
     *
     * @param  array<int, array{mulai: string, selesai: string}>  $rentang
     * @return array<int, array{mulai: string, selesai: string}>
     */
    public function gabungRentangJam(array $rentang): array
    {
        usort($rentang, fn ($a, $b) => strcmp($a['mulai'], $b['mulai']));

        $hasil = [];
        foreach ($rentang as $r) {
            $n = count($hasil);
            if ($n > 0 && $r['mulai'] <= $hasil[$n - 1]['selesai']) {
                $hasil[$n - 1]['selesai'] = max($hasil[$n - 1]['selesai'], $r['selesai']);
            } else {
                $hasil[] = $r;
            }
        }

        return $hasil;
    }

    // ---------------------------------------------------------------------
    // Helper overlap
    // ---------------------------------------------------------------------

    /** Dua slot bentrok? Kalau salah satu memakai seluruh hari, cukup overlap tanggal. */
    public function slotsConflict(array $a, array $b): bool
    {
        if (! $this->datesOverlap($a['tanggal_mulai'], $a['tanggal_selesai'], $b['tanggal_mulai'], $b['tanggal_selesai'])) {
            return false;
        }

        $aHourly = ! empty($a['jam_mulai']);
        $bHourly = ! empty($b['jam_mulai']);

        // Keduanya per jam (pasti satu hari & tanggalnya sudah overlap) → cek overlap jam.
        if ($aHourly && $bHourly) {
            return $this->timesOverlap(
                $this->normTime($a['jam_mulai']), $this->normTime($a['jam_selesai']),
                $this->normTime($b['jam_mulai']), $this->normTime($b['jam_selesai']),
            );
        }

        // Minimal satu sisi memblokir seluruh hari → overlap tanggal saja sudah bentrok.
        return true;
    }

    private function datesOverlap(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $aStart <= $bEnd && $aEnd >= $bStart;
    }

    private function timesOverlap(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $aStart < $bEnd && $aEnd > $bStart;
    }

    private function normTime(string $time): string
    {
        // Samakan ke HH:MM:SS agar perbandingan string konsisten ('09:00' vs '09:00:00').
        return Carbon::parse($time)->format('H:i:s');
    }
}
