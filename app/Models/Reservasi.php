<?php

namespace App\Models;

use App\Enums\LockStatus;
use App\Enums\StatusReservasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservasi extends Model
{
    use HasFactory;

    protected $table = 'reservasi';
    protected $primaryKey = 'id_reservasi';

    /**
     * Keterangan opsional untuk baris Riwayat_Status yang dibuat otomatis oleh observer
     * saat status berubah. Bukan kolom DB — dideklarasikan sebagai properti asli agar tidak
     * diperlakukan sebagai atribut Eloquent. Diisi mis. "Dibatalkan oleh pemesan".
     */
    public ?string $keteranganRiwayat = null;

    protected $fillable = [
        'id_pemesan',
        'id_tarif_sewa',
        'id_admin',
        'kode_reservasi',
        'kode_transaksi',       // DELTA #2
        'tanggal_mulai',
        'tanggal_selesai',
        'jam_mulai',            // DELTA #1
        'jam_selesai',
        'durasi',
        'jumlah_pengguna',
        'keperluan',
        'harga_satuan',
        'total_biaya',
        'status_reservasi',
        'lock_status',          // DELTA #3
        'lock_expires_at',      // DELTA #3
        'tanggal_diproses',     // DELTA #5
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'durasi' => 'integer',
        'jumlah_pengguna' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total_biaya' => 'decimal:2',
        'status_reservasi' => StatusReservasi::class,
        'lock_status' => LockStatus::class,     // DELTA #3
        'lock_expires_at' => 'datetime',        // DELTA #3
        'tanggal_diproses' => 'datetime',       // DELTA #5
        // jam_mulai & jam_selesai (TIME) sengaja tidak di-cast datetime agar tidak diprefiks tanggal.
    ];

    /**
     * Status yang masih boleh mengunduh Bukti Reservasi: Menunggu (sedang diverifikasi) dan
     * Disetujui. Ditolak, Dibatalkan, Selesai, dan Kadaluwarsa tidak lagi diterbitkan buktinya.
     *
     * @return string[]
     */
    public static function statusBuktiTersedia(): array
    {
        return [StatusReservasi::Menunggu->value, StatusReservasi::Disetujui->value];
    }

    public function buktiTersedia(): bool
    {
        return in_array($this->status_reservasi->value, self::statusBuktiTersedia(), true);
    }

    /** Huruf tanpa I & O supaya tidak tertukar dengan angka 1 & 0. */
    public const HURUF_KODE = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    /** Pola kode untuk validasi Cek Status: format baru (RS186, RS186A) dan format lama (RSV-7K3M, RSV-7K3M-1). */
    public const POLA_KODE = '([A-Za-z]{2}[0-9]{3}[A-Za-z]{0,2}|[Rr][Ss][Vv]-[A-Za-z0-9]{4}(-[0-9]+)?)';

    /** Kode dasar acak tanpa tanda hubung: 2 huruf + 3 angka, mis. RS186 atau KT085. */
    public static function kodeAcak(): string
    {
        $huruf = self::HURUF_KODE;

        return $huruf[random_int(0, 23)].$huruf[random_int(0, 23)].sprintf('%03d', random_int(0, 999));
    }

    /**
     * Kode per baris/ruangan. Checkout satu ruangan memakai kode dasar apa adanya; checkout
     * beberapa ruangan diberi huruf berurutan di belakang (RS186A, RS186B, ...) karena
     * kode_reservasi UNIQUE per baris. Setelah Z lanjut AA, AB, ... (praktis tidak pernah tercapai).
     */
    public static function kodeRuangan(string $kodeDasar, int $index, bool $multi): string
    {
        if (! $multi) {
            return $kodeDasar;
        }

        $sufiks = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $sufiks = chr(65 + ($n - 1) % 26).$sufiks;
        }

        return $kodeDasar.$sufiks;
    }

    public function pemesan(): BelongsTo
    {
        return $this->belongsTo(Pemesan::class, 'id_pemesan', 'id_pemesan');
    }

    public function tarifSewa(): BelongsTo
    {
        return $this->belongsTo(TarifSewa::class, 'id_tarif_sewa', 'id_tarif_sewa');
    }

    public function admin(): BelongsTo
    {
        // Nullable sampai reservasi diproses.
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }

    public function dokumenPersyaratan(): HasMany
    {
        return $this->hasMany(DokumenPersyaratan::class, 'id_reservasi', 'id_reservasi');
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class, 'id_reservasi', 'id_reservasi');
    }

    public function faktur(): HasOne
    {
        return $this->hasOne(Faktur::class, 'id_reservasi', 'id_reservasi');
    }

    /**
     * Reservasi yang locknya masih EFEKTIF memblokir ketersediaan fasilitas.
     *
     * Business rule: baris dengan lock_status='temporary_hold' yang lock_expires_at-nya
     * sudah lewat waktu sekarang dianggap TIDAK memblokir, jadi dikeluarkan dari scope ini.
     * Baris 'released' juga tidak memblokir.
     */
    public function scopeTersedia(Builder $query): Builder
    {
        return $query
            ->where('lock_status', '!=', LockStatus::Released->value)
            ->where(function (Builder $q) {
                // Hold yang belum expired ATAU lock non-temporary (pending_approval/confirmed).
                $q->where('lock_status', '!=', LockStatus::TemporaryHold->value)
                    ->orWhereNull('lock_expires_at')
                    ->orWhere('lock_expires_at', '>', now());
            });
    }
}
