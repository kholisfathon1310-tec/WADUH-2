<?php

namespace App\Models;

use App\Enums\StatusAktif;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Fasilitas extends Model
{
    use HasFactory;

    protected $table = 'fasilitas';
    protected $primaryKey = 'id_fasilitas';

    protected $fillable = [
        'id_lantai',
        'kode_fasilitas',
        'nama_fasilitas',
        'kategori_fasilitas',
        'kapasitas',
        'luas',
        'foto',
        'deskripsi',
        'status_aktif',
    ];

    protected $casts = [
        'luas' => 'decimal:2',
        'status_aktif' => StatusAktif::class,
        'foto' => 'array',
    ];

    public function lantai(): BelongsTo
    {
        return $this->belongsTo(Lantai::class, 'id_lantai', 'id_lantai');
    }

    public function tarifSewa(): HasMany
    {
        return $this->hasMany(TarifSewa::class, 'id_fasilitas', 'id_fasilitas');
    }

    /** Seluruh reservasi ruangan ini (lewat tarif sewa) — dipakai utk hitung fasilitas populer. */
    public function reservasi(): HasManyThrough
    {
        return $this->hasManyThrough(
            Reservasi::class,
            TarifSewa::class,
            'id_fasilitas', 'id_tarif_sewa', 'id_fasilitas', 'id_tarif_sewa',
        );
    }

    /** URL foto ruangan (public/images/*); jatuh ke gambar stok kategori kalau belum ada foto. */
    public function fotoUrls(): array
    {
        if (! empty($this->foto)) {
            return collect($this->foto)->map(fn (string $f) => asset('images/'.$f))->all();
        }

        return [asset(\App\Support\KategoriMeta::get($this->kategori_fasilitas)['gambar'])];
    }
}
