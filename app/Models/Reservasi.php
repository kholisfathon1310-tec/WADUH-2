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
