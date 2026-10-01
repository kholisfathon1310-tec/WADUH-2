<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Salinan keranjang reservasi pemesan (lihat App\Services\CartService). */
class KeranjangPemesan extends Model
{
    protected $table = 'keranjang_pemesan';
    protected $primaryKey = 'id_pemesan';
    public $incrementing = false;

    protected $fillable = ['id_pemesan', 'item', 'dokumen'];

    protected $casts = [
        'item'    => 'array',
        'dokumen' => 'array',
    ];
}
