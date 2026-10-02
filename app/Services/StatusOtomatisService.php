<?php

namespace App\Services;

use App\Enums\LockStatus;
use App\Enums\SatuanSewa;
use App\Enums\StatusReservasi;
use App\Models\Reservasi;
use App\Models\RiwayatStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Perubahan status Reservasi yang dipicu waktu (bukan oleh admin/pemesan):
 *   - Disetujui -> Selesai     : setelah masa penggunaan berakhir.
 *   - Menunggu  -> Kadaluwarsa : setelah batas persetujuan terlewati (beda per jenis sewa).
 *   - lock temporary_hold yang lewat lock_expires_at -> released.
 *
 * Dipanggil dari dua tempat supaya status tetap "realtime" walau scheduler tidak berjalan
 * (mis. di Laragon/Windows tanpa cron): command terjadwal dan middleware web
 * JalankanStatusOtomatis (dibatasi sekali per beberapa detik).
 */
class StatusOtomatisService
{
    /**
     * Jam tutup operasional gedung — sama dengan batas jam_selesai di TambahKeranjangRequest
     * (08.00–16.00). Reservasi Harian/Bulanan tidak punya jam_selesai, jadi waktu penggunaannya
     * dianggap berakhir begitu gedung tutup, bukan tengah malam.
     */
    public const JAM_TUTUP_OPERASIONAL = '16:00:00';

    private const KUNCI = 'reservasi:status-otomatis';

    /**
     * Jalankan seluruh transisi. Dikunci (cache lock) supaya dua proses yang berjalan
     * bersamaan tidak memproses baris yang sama dan mencatat Riwayat_Status ganda.
     *
     * @return array{selesai:int, kadaluwarsa:int, lock_dilepas:int}|null null bila proses lain sedang berjalan.
     */
    public function jalankan(): ?array
    {
        return Cache::lock(self::KUNCI, 60)->get(fn () => [
            'selesai'      => $this->selesaikanMasaPenggunaanBerakhir(),
            'kadaluwarsa'  => $this->kadaluwarsakanRuanganTerlambat(),
            'lock_dilepas' => $this->lepasLockKedaluwarsa(),
        ]);
    }

    /**
     * Disetujui -> Selesai: masa penggunaan berakhir begitu tanggal_selesai (+ jam_selesai
     * kalau Per Jam, atau jam tutup operasional untuk Harian/Bulanan) sudah lewat. Dievaluasi
     * per baris (bukan per pemesanan) karena tiap ruangan selesai dipakai pada tanggalnya
     * masing-masing, walau disetujui bersamaan dalam satu pemesanan.
     *
     * Diubah satu per satu lewat save() — BUKAN update() massal — supaya ReservasiObserver
     * tetap mencatat baris Riwayat_Status untuk tiap perubahan.
     */
    public function selesaikanMasaPenggunaanBerakhir(): int
    {
        $items = Reservasi::query()
            ->where('status_reservasi', StatusReservasi::Disetujui->value)
            ->whereRaw(
                'TIMESTAMP(tanggal_selesai, COALESCE(jam_selesai, ?)) <= ?',
                [self::JAM_TUTUP_OPERASIONAL, now()->toDateTimeString()],
            )
            ->get();

        foreach ($items as $item) {
            $item->keteranganRiwayat = 'Waktu penggunaan reservasi ini sudah berakhir.';
            $item->status_reservasi = StatusReservasi::Selesai;
            $item->lock_status = LockStatus::Released;
            $item->save();
        }

        return $items->count();
    }

    /**
     * Menunggu -> Kadaluwarsa, dievaluasi per RUANGAN/BARIS — bukan digabung per pemesanan.
     * Kalau 1 pemesanan berisi 2+ ruangan berbeda, tiap ruangan kadaluwarsa mengikuti batas
     * waktunya sendiri-sendiri.
     *
     * Batas waktu per ruangan beda per jenis sewa (lihat batasPersetujuan()).
     */
    public function kadaluwarsakanRuanganTerlambat(): int
    {
        $now = now();

        // Semua batas persetujuan jatuh pada/setelah tanggal_mulai, jadi baris yang
        // tanggal_mulai-nya masih di masa depan tidak perlu dimuat sama sekali.
        $menunggu = Reservasi::query()
            ->where('status_reservasi', StatusReservasi::Menunggu->value)
            ->whereDate('tanggal_mulai', '<=', $now->toDateString())
            ->with('tarifSewa.jenisSewa')
            ->get();

        $count = 0;

        foreach ($menunggu as $item) {
            if (self::batasPersetujuan($item)->gt($now)) {
                continue;
            }

            $item->keteranganRiwayat = 'Tidak diproses admin sampai melewati batas waktu persetujuan ruangan ini.';
            $item->status_reservasi = StatusReservasi::Kadaluwarsa;
            $item->lock_status = LockStatus::Released;
            $item->save();
            $count++;
        }

        return $count;
    }

    /** temporary_hold yang sudah lewat lock_expires_at -> released (tidak mengubah status). */
    public function lepasLockKedaluwarsa(): int
    {
        return Reservasi::query()
            ->where('lock_status', LockStatus::TemporaryHold->value)
            ->whereNotNull('lock_expires_at')
            ->where('lock_expires_at', '<=', now())
            ->update(['lock_status' => LockStatus::Released->value]);
    }

    /**
     * Batas waktu admin memproses satu ruangan berstatus Menunggu:
     *   - Per Jam   : begitu jam_selesai (pada tanggal_mulai) yang diajukan sudah lewat.
     *   - Per Hari  : begitu jam operasional (16.00) di tanggal_mulai sudah lewat.
     *   - Per Bulan : begitu tanggal_mulai sudah terlewati (jadi keesokan harinya).
     */
    public static function batasPersetujuan(Reservasi $reservasi): Carbon
    {
        $satuan = $reservasi->tarifSewa->jenisSewa->satuan;
        $tanggalMulai = $reservasi->tanggal_mulai->toDateString();

        return match ($satuan) {
            SatuanSewa::Jam   => Carbon::parse($tanggalMulai.' '.($reservasi->jam_selesai ?: self::JAM_TUTUP_OPERASIONAL)),
            SatuanSewa::Hari  => Carbon::parse($tanggalMulai.' '.self::JAM_TUTUP_OPERASIONAL),
            SatuanSewa::Bulan => $reservasi->tanggal_mulai->copy()->addDay()->startOfDay(),
        };
    }

    /**
     * Sidik jari data reservasi — berubah setiap ada baris yang ditambah, dihapus, atau
     * diperbarui. Dipakai halaman untuk mendeteksi perubahan tanpa memuat ulang terus-menerus.
     */
    public function versi(): string
    {
        $r = Reservasi::query()
            ->selectRaw('COUNT(*) AS jumlah, MAX(updated_at) AS terakhir, COALESCE(SUM(id_reservasi), 0) AS total_id')
            ->first();

        // Setiap perubahan status mencatat satu Riwayat_Status, jadi jumlahnya ikut dihitung
        // agar dua perubahan dalam detik yang sama tetap terdeteksi.
        $riwayat = RiwayatStatus::query()->max('id_riwayat');

        return md5($r->jumlah.'|'.$r->terakhir.'|'.$r->total_id.'|'.$riwayat);
    }
}
