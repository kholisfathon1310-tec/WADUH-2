<?php

namespace App\Services;

use App\Enums\SatuanSewa;
use App\Models\KeranjangPemesan;
use App\Models\TarifSewa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Keranjang reservasi pra-checkout. Session tetap menjadi tempat kerja, tetapi untuk pemesan
 * yang login setiap perubahan juga disalin ke tabel keranjang_pemesan — sehingga keranjang
 * yang belum diajukan tetap ada setelah pemesan keluar lalu masuk kembali (dimuat ulang
 * secara malas pada akses pertama di session baru, lihat muat()).
 */
class CartService
{
    private const SESSION_KEY = 'reservasi_cart';

    /**
     * Dokumen persyaratan sewa bulanan — satu lampiran berlaku untuk SEMUA ruangan Bulan
     * dalam keranjang ini (bukan per ruangan), diisi lewat form "Isi Jadwal" begitu ada
     * ruangan Bulan, cukup sekali walau menambah beberapa ruangan Bulan.
     */
    private const DOKUMEN_SESSION_KEY = 'reservasi_cart_dokumen';

    /** Penanda di session: keranjang tersimpan milik pemesan ini sudah dimuat. */
    private const DIMUAT_SESSION_KEY = 'reservasi_cart_dimuat';

    public function __construct(
        private FasilitasBawaanService $bawaan,
        private AvailabilityService $availability,
    ) {
    }

    /** @return array<int, array> */
    public function items(): array
    {
        $this->muat();

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
        $this->simpanKeTabel();
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
        $this->simpanKeTabel();

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
        $this->simpanKeTabel();

        return true;
    }

    /** Kosongkan daftar item keranjang. Dokumen (lihat clearDokumen()) sengaja TIDAK ikut
     *  dihapus di sini — pemanggil yang menentukan apakah dokumennya masih relevan (mis.
     *  checkout sukses: file sudah "dipindah-tangan" ke DokumenPersyaratan, jangan dihapus). */
    public function clear(): void
    {
        $this->muat();
        session()->forget(self::SESSION_KEY);
        $this->simpanKeTabel();
    }

    /** @return array<int, array{path: string, nama: string}> */
    public function dokumen(): array
    {
        $this->muat();

        return array_values(session()->get(self::DOKUMEN_SESSION_KEY, []));
    }

    /** @param  array<int, array{path: string, nama: string}>  $daftar */
    public function simpanDokumen(array $daftar): void
    {
        $this->muat();
        session()->put(self::DOKUMEN_SESSION_KEY, array_values($daftar));
        $this->simpanKeTabel();
    }

    /**
     * Lupakan daftar dokumen dari session. $hapusFile=true juga menghapus file fisiknya
     * dari storage (dipakai saat keranjang benar-benar dikosongkan/item Bulan terakhir
     * dihapus — filenya jadi yatim). Saat checkout SUKSES, panggil dengan false karena
     * path filenya sudah dipakai sebagai lokasi_file di baris DokumenPersyaratan.
     */
    public function clearDokumen(bool $hapusFile): void
    {
        if ($hapusFile) {
            foreach ($this->dokumen() as $d) {
                Storage::disk('public')->delete($d['path']);
            }
        }
        session()->forget(self::DOKUMEN_SESSION_KEY);
        $this->simpanKeTabel();
    }

    // ---------------------------------------------------------------------
    // Penyimpanan keranjang pemesan di database
    // ---------------------------------------------------------------------

    private function idPemesan(): ?int
    {
        $id = Auth::guard('customer')->id();

        return $id === null ? null : (int) $id;
    }

    /**
     * Muat keranjang tersimpan ke session satu kali per session (mis. setelah keluar lalu
     * masuk kembali). Item yang tanggal mulainya sudah lewat dibuang, dan hold ketersediaan
     * dipasang ulang atas nama session baru — hold lama terikat ID session sebelumnya dan
     * justru akan memblokir pemesan itu sendiri.
     */
    private function muat(): void
    {
        $id = $this->idPemesan();
        if ($id === null || session()->get(self::DIMUAT_SESSION_KEY) === $id) {
            return;
        }
        session()->put(self::DIMUAT_SESSION_KEY, $id);

        // Session ini sudah punya keranjang sendiri → itu yang berlaku, salin ke tabel.
        if (session()->has(self::SESSION_KEY) || session()->has(self::DOKUMEN_SESSION_KEY)) {
            $this->simpanKeTabel();

            return;
        }

        $baris = KeranjangPemesan::find($id);
        if (! $baris) {
            return;
        }

        $hariIni = Carbon::today()->toDateString();
        $semua = array_values($baris->item ?? []);
        $items = array_values(array_filter($semua, fn (array $i) => ($i['tanggal_mulai'] ?? '') >= $hariIni));

        if ($items !== []) {
            session()->put(self::SESSION_KEY, $items);
        }
        if (! empty($baris->dokumen) && collect($items)->contains('satuan', SatuanSewa::Bulan->value)) {
            session()->put(self::DOKUMEN_SESSION_KEY, array_values($baris->dokumen));
        }

        $sessionId = session()->getId();
        foreach ($items as $item) {
            $this->availability->releaseHold($item);
            $this->availability->putHold($item, $sessionId);
        }

        if (count($items) !== count($semua) || ($items === [] && ! empty($baris->dokumen))) {
            $this->simpanKeTabel();
        }
    }

    /** Salin isi keranjang session ke tabel; keranjang kosong menghapus barisnya. */
    private function simpanKeTabel(): void
    {
        $id = $this->idPemesan();
        if ($id === null) {
            return;
        }

        $items = array_values(session()->get(self::SESSION_KEY, []));
        $dokumen = array_values(session()->get(self::DOKUMEN_SESSION_KEY, []));

        if ($items === [] && $dokumen === []) {
            KeranjangPemesan::whereKey($id)->delete();

            return;
        }

        KeranjangPemesan::updateOrCreate(
            ['id_pemesan' => $id],
            ['item' => $items, 'dokumen' => $dokumen === [] ? null : $dokumen],
        );
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
                $durasi = max(1, self::hitungBulan($tanggalMulai, $tanggalSelesai)['ditagih']);
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
            'kapasitas'       => (int) $tarif->fasilitas->kapasitas,
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

    /**
     * Jumlah bulan sewa dari tanggal mulai s.d. tanggal berakhir (mulai + N bulan = N bulan,
     * mis. 1 Jan – 1 Apr = 3 bulan). Sisa hari di luar bulan penuh ditagih sebagai 1 bulan.
     * Satu sumber untuk validasi durasi minimum dan perhitungan total biaya.
     *
     * @return array{penuh: int, sisa_hari: int, ditagih: int}
     */
    public static function hitungBulan(string $tanggalMulai, string $tanggalSelesai): array
    {
        $mulai = Carbon::parse($tanggalMulai)->startOfDay();
        $selesai = Carbon::parse($tanggalSelesai)->startOfDay();

        if ($selesai->lte($mulai)) {
            return ['penuh' => 0, 'sisa_hari' => 0, 'ditagih' => 0];
        }

        $penuh = 0;
        while ($mulai->copy()->addMonthsNoOverflow($penuh + 1)->lte($selesai)) {
            $penuh++;
        }
        $sisaHari = (int) $mulai->copy()->addMonthsNoOverflow($penuh)->diffInDays($selesai);

        return ['penuh' => $penuh, 'sisa_hari' => $sisaHari, 'ditagih' => $penuh + ($sisaHari > 0 ? 1 : 0)];
    }

    private function hitungJam(string $tanggal, string $jamMulai, string $jamSelesai): int
    {
        $mulai = Carbon::parse("$tanggal $jamMulai");
        $selesai = Carbon::parse("$tanggal $jamSelesai");

        // Dibulatkan ke atas ke jam penuh, minimal 1 jam.
        return max(1, (int) ceil($mulai->diffInMinutes($selesai) / 60));
    }
}
