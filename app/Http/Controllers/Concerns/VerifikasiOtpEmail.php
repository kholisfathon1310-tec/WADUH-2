<?php

namespace App\Http\Controllers\Concerns;

use App\Services\OtpEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Endpoint AJAX "Verifikasi" kode OTP — dipakai bersama halaman daftar, profil pemesan, dan profil admin. */
trait VerifikasiOtpEmail
{
    protected function jawabVerifikasiOtp(Request $request, OtpEmailService $otp, string $tujuan, ?int $pemilik = null): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'kode'  => ['required', 'digits:6'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid. Contoh: nama@email.com.',
            'kode.required'  => 'Masukkan kode OTP yang dikirim ke email Anda.',
            'kode.digits'    => 'Kode OTP terdiri dari 6 angka.',
        ]);

        $galat = $otp->verifikasiDanTandai($tujuan, $data['email'], $data['kode'], $pemilik);

        return $galat === null
            ? response()->json(['ok' => true, 'pesan' => 'Email berhasil diverifikasi dan aktif.'])
            : response()->json(['ok' => false, 'pesan' => $galat], 422);
    }
}
