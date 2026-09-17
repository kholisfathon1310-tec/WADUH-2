<?php

namespace App\Services;

use App\Enums\SatuanSewa;
use App\Models\TarifSewa;
use Illuminate\Support\Carbon;

/**
 * Keranjang reservasi pra-checkout — disimpan di Laravel session (BUKAN database),
 * sesuai db-spec-stage2-pemesan.md.
 */
class CartService
{
    private const SESSION_KEY = 'reservasi_cart';

    public function __construct(private FasilitasBawaanService $bawaan)
    {
    }

    /** @return array<int, array> */
    public function items(): array
    {
        return array_values(session()->get(self::SESSION_KEY, []));
    }

    public function isEmpty(): bool
    {
        return $this->items() === [];
    }

    public function count(): int
    {
        return count($this->items());
    }

    public function total(): float
    {
        return (float) array_sum(array_column($this->items(), 'total_biaya'));
    }

    public function add(array $item): void
    {
        $items = $this->items();
        $items[] = $item;
        session()->put(self::SESSION_KEY, $items);
    }

    public function get(int $index): ?array
    {
        return $this->items()[$index] ?? null;
    }

    public function remove(int $index): ?array
    {
        $items = $this->items();
        if (! isset($items[$index])) {
            return null;
        }
        $removed = $items[$index];
        unset($items[$index]);
        session()->put(self::SESSION_KEY, array_values($items));

        return $removed;
    }

    /**
     * Timpa item keranjang di $index dengan $item baru (dipakai oleh alur "Ubah" jadwal),
     * tanpa mengubah urutan/index item lain.
     */
    public function replace(int $index, array $item): bool
    {
        $items = $this->items();
        if (! isset($items[$index])) {
            return false;
        }
        $items[$index] = $item;
        session()->put(self::SESSION_KEY, $items);

        return true;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function hasBulan(): bool
    {
        foreach ($this->items() as $item) {
            if ($item['satuan'] === SatuanSewa::Bulan->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ruangan (id_fasilitas) yang sama di keranjang dengan jadwal yang BENTROK (bukan cuma
     * identik persis) dengan $item — dicek pakai overlap yang sama dengan pengecekan
     * ketersediaan (AvailabilityService::slotsConflict), supaya mis. 09.00–11.00 vs
     * 09.00–10.00 pada ruangan yang sama tetap tertangkap walau jam_selesai-nya beda.
     * Ruangan berbeda dengan jadwal bentrok, atau ruangan sama dengan jadwal TIDAK bentrok,
     * TIDAK dianggap konflik.
     */
    public function hasConflict(array $item, AvailabilityService $availability, ?int $excludeIndex = null): bool
    {
        foreach ($this->items() as $index => $existing) {
            if ($index === $excludeIndex) {
                continue;
            }
            if (
                (int) $existing['id_fasilitas'] === (int) $item['id_fasilitas']
                && $availability->slotsConflict($existing, $item)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bangun satu item keranjang lengkap dari tarif + input jadwal yang sudah tervalidasi.
     * Menghitung tanggal_selesai (Jam), durasi, dan total_biaya sesuai satuan.
     *
     * @param  array  $data  Field jadwal tervalidasi (tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, durasi, jumlah_pengguna, keperluan)
     */
    public function buildItem(TarifSewa $tarif, array $data): array
    {
        $tarif->loadMissing('fasilitas.lantai', 'jenisSewa');
        $satuan = $tarif->jenisSewa->satuan; // enum SatuanSewa
        $harga = (float) $tarif->harga;

        $tanggalMulai = $data['tanggal_mulai'];
        $jamMulai = null;
        $jamSelesai = null;

        switch ($satuan) {
            case SatuanSewa::Jam:
                $tanggalSelesai = $tanggalMulai; // per jam = satu hari
                $jamMulai = $data['jam_mulai'];
                $jamSelesai = $data['jam_selesai'];
                $durasi = $this->hitungJam($tanggalMulai, $jamMulai, $jamSelesai);
                break;

            case SatuanSewa::Hari:
                $tanggalSelesai = $data['tanggal_selesai'];
                $durasi = Carbon::parse($tanggalMulai)->diffInDays(Carbon::parse($tanggalSelesai)) + 1;
                break;

            case SatuanSewa::Bulan:
            default:
                $tanggalSelesai = $data['tanggal_selesai'];
                $durasi = max(1, Carbon::parse($tanggalMulai)->diffInMonths(Carbon::parse($tanggalSelesai)));
                break;
        }

        $total = $harga * $durasi;

        return [
            'id_fasilitas'    => $tarif->id_fasilitas,
            'id_tarif_sewa'   => $tarif->id_tarif_sewa,
            'id_jenis_sewa'   => $tarif->id_jenis_sewa,
            'satuan'          => $satuan->value,
            'nama_fasilitas'  => $tarif->fasilitas->nama_fasilitas,
            'kategori'        => $tarif->fasilitas->kategori_fasilitas,
            'fasilitas_bawaan' => $this->bawaan->untuk($tarif->fasilitas, $satuan->value),
            'lantai_nomor'    => $tarif->fasilitas->lantai->nomor_lantai ?? null,
            'tanggal_mulai'   => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'jam_mulai'       => $jamMulai,
            'jam_selesai'     => $jamSelesai,
            'durasi'          => (int) $durasi,
            'jumlah_pengguna' => (int) $data['jumlah_pengguna'],
            'keperluan'       => $data['keperluan'],
            'harga_satuan'    => $harga,
            'total_biaya'     => $total,
        ];
    }

    private function hitungJam(string $tanggal, string $jamMulai, string $jamSelesai): int
    {
        $mulai = Carbon::parse("$tanggal $jamMulai");
        $selesai = Carbon::parse("$tanggal $jamSelesai");

        // Dibulatkan ke atas ke jam penuh, minimal 1 jam.
        return max(1, (int) ceil($mulai->diffInMinutes($selesai) / 60));
    }
}
