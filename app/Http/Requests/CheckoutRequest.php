<?php

namespace App\Http\Requests;

use App\Services\CartService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Data diri Pemesan & dokumen persyaratan Bulan (kalau ada) sudah dikumpulkan & disimpan
 * sebelumnya lewat form "Isi Jadwal" (TambahKeranjangRequest, lihat CartService::dokumen())
 * begitu ruangan Bulan ditambahkan ke keranjang — checkout jadi murni konfirmasi kirim,
 * tanpa input apa pun lagi (keranjang HANYA untuk mengirim reservasi).
 *
 * Pengecekan dokumen di sini murni jaring pengaman (seharusnya tidak pernah kena, karena
 * sudah diwajibkan di langkah jadwal) — mis. kalau item Bulan sempat masuk keranjang lewat
 * jalur lain tanpa dokumen.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi login Pemesan sudah ditangani middleware `auth:customer` pada route.
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var CartService $cart */
            $cart = app(CartService::class);

            if ($cart->hasBulan() && $cart->dokumen() === []) {
                $v->errors()->add(
                    'dokumen',
                    'Dokumen persyaratan sewa bulanan belum lengkap. Buka "Ubah" pada item bulanan di keranjang untuk melengkapinya.'
                );
            }
        });
    }
}
