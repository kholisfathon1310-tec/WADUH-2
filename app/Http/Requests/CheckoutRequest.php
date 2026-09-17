<?php

namespace App\Http\Requests;

use App\Services\CartService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Data diri Pemesan sudah dikumpulkan & disimpan sebelumnya lewat form "Isi Jadwal"
 * (TambahKeranjangRequest) saat item ditambahkan ke keranjang — checkout jadi murni
 * konfirmasi + (kalau ada item Bulan) upload dokumen persyaratan.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi login Pemesan sudah ditangani middleware `auth:customer` pada route.
    }

    public function rules(): array
    {
        return [
            // File dokumen (kalau ada): satu lampiran multi-file untuk seluruh transaksi
            // (berlaku untuk semua ruangan Bulan sekaligus, bukan per ruangan).
            'dokumen'   => ['array'],
            'dokumen.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var CartService $cart */
            $cart = app(CartService::class);

            // Kalau ada minimal 1 item Bulan di keranjang, wajib minimal 1 dokumen terupload
            // (satu lampiran berlaku untuk semua ruangan Bulan pada transaksi ini).
            if ($cart->hasBulan()) {
                $files = $this->file('dokumen', []);
                $files = array_filter(is_array($files) ? $files : [$files]);
                if ($files === []) {
                    $v->errors()->add(
                        'dokumen',
                        'Wajib melampirkan minimal 1 dokumen persyaratan (Company Profile / legalitas / KTP penanggung jawab) untuk sewa bulanan.'
                    );
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'dokumen.*' => 'dokumen',
        ];
    }

    public function messages(): array
    {
        return [
            'dokumen.*.mimes' => 'Dokumen harus berformat PDF, JPG, atau PNG.',
            'dokumen.*.max'   => 'Ukuran tiap dokumen maksimal 5 MB.',
        ];
    }
}
