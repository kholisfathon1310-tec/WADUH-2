<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\VerifikasiOtpEmail;
use App\Http\Controllers\Controller;
use App\Services\OtpEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AkunController extends Controller
{
    use VerifikasiOtpEmail;

    public function profil(OtpEmailService $otp): View
    {
        $pemesan = Auth::guard('customer')->user();

        return view('customer.akun.profil', [
            'pemesan'   => $pemesan,
            'otpStatus'        => $otp->status(OtpEmailService::TUJUAN_UBAH_EMAIL, old('email'), $pemesan->id_pemesan),
            'otpTerverifikasi' => $otp->sudahTerverifikasi(OtpEmailService::TUJUAN_UBAH_EMAIL, old('email'), $pemesan->id_pemesan),
        ]);
    }

    /** AJAX: kirim kode OTP ke email BARU sebelum email akun boleh diganti. */
    public function kirimOtpEmail(Request $request, OtpEmailService $otp): JsonResponse
    {
        $pemesan = Auth::guard('customer')->user();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:150', Rule::unique('pemesan', 'email')->ignore($pemesan->id_pemesan, 'id_pemesan')],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid. Contoh: nama@email.com.',
            'email.unique'   => 'Email tersebut sudah digunakan akun lain.',
        ]);

        if (mb_strtolower($data['email']) === mb_strtolower($pemesan->email)) {
            return response()->json(['ok' => false, 'errors' => ['email' => ['Email baru sama dengan email saat ini.']]], 422);
        }

        $hasil = $otp->kirim(OtpEmailService::TUJUAN_UBAH_EMAIL, $data['email'], $pemesan->id_pemesan);

        return response()->json($hasil, $hasil['ok'] ? 200 : 422);
    }

    /** AJAX: cek kode OTP email baru; bila valid email baru ditandai terverifikasi. */
    public function verifikasiOtpEmail(Request $request, OtpEmailService $otp): JsonResponse
    {
        return $this->jawabVerifikasiOtp($request, $otp, OtpEmailService::TUJUAN_UBAH_EMAIL, Auth::guard('customer')->id());
    }

    public function updateProfil(Request $request, OtpEmailService $otp): RedirectResponse
    {
        $pemesan = Auth::guard('customer')->user();

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150', 'regex:/^[\p{L}\s.\'-]+$/u'],
            'alamat'       => ['required', 'string', 'max:500'],
            'usia'         => ['required', 'integer', 'min:17', 'max:120'],
            'pekerjaan'    => ['required', 'string', 'max:100'],
            'no_telepon'   => ['required', 'string', 'regex:/^\+?[0-9]{8,20}$/'],
            'email'        => ['required', 'email', 'max:150', Rule::unique('pemesan', 'email')->ignore($pemesan->id_pemesan, 'id_pemesan')],
        ], [
            'nama_lengkap.regex' => 'Nama lengkap hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.',
            'no_telepon.regex'   => 'No. telepon hanya boleh berisi angka (8–20 digit), contoh: 081234567890.',
            'usia.min'           => 'Usia minimal 17 tahun.',
            'usia.max'           => 'Usia maksimal 120 tahun.',
            'email.unique'       => 'Email tersebut sudah digunakan akun lain.',
        ]);

        // Email diganti → wajib verifikasi kode OTP yang dikirim ke email BARU.
        $gantiEmail = mb_strtolower($data['email']) !== mb_strtolower($pemesan->email);
        if ($gantiEmail && ! $otp->sudahTerverifikasi(OtpEmailService::TUJUAN_UBAH_EMAIL, $data['email'], $pemesan->id_pemesan)) {
            return redirect()->route('customer.akun.profil')
                ->withInput()
                ->withErrors(['email' => 'Email belum diverifikasi. Tekan "Kirim OTP", masukkan kode dari email, lalu tekan "Verifikasi".']);
        }

        $pemesan->update($data);
        if ($gantiEmail) {
            $otp->lupakanVerifikasi(OtpEmailService::TUJUAN_UBAH_EMAIL);
        }

        return redirect()->route('customer.akun.profil')->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Form ubah kata sandi kini berupa modal di halaman Profil — alamat lama ini tetap
     * dilayani (tautan/bookmark lama) dengan mengarahkan ke Profil dan langsung membuka modalnya.
     */
    public function password(): RedirectResponse
    {
        return redirect()->route('customer.akun.profil', ['ubah' => 'sandi']);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $pemesan = Auth::guard('customer')->user();

        // `same` (bukan `confirmed`) supaya pesan ketidakcocokan menempel pada kolom
        // konfirmasi itu sendiri, bukan pada kolom kata sandi baru.
        $data = $request->validate([
            'password_lama'         => ['required', 'string'],
            'password'              => ['required', 'string', Password::min(8)->letters()->numbers()],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ], [
            'password_lama.required'         => 'Kata sandi lama wajib diisi.',
            'password.required'              => 'Kata sandi baru wajib diisi.',
            'password_confirmation.required' => 'Konfirmasi kata sandi baru wajib diisi.',
            'password_confirmation.same'     => 'Konfirmasi kata sandi tidak sama dengan kata sandi baru.',
        ], [
            'password' => 'Kata sandi baru',
        ]);

        // Hash::check() MELEMPAR RuntimeException (bukan sekadar false) kalau hash tersimpan
        // tidak berformat bcrypt yang valid (mis. baris pernah tersentuh langsung lewat
        // database). Ditangani di sini supaya tidak jadi error 500 — cukup anggap kata sandi
        // lama tidak cocok, seperti kondisi salah kata sandi biasa.
        try {
            $lamaCocok = Hash::check($data['password_lama'], $pemesan->password);
        } catch (\RuntimeException $e) {
            $lamaCocok = false;
        }
        if (! $lamaCocok) {
            return redirect()->route('customer.akun.profil')
                ->withErrors(['password_lama' => 'Kata sandi lama tidak sesuai.']);
        }

        if ($data['password'] === $data['password_lama']) {
            return redirect()->route('customer.akun.profil')
                ->withErrors(['password' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.']);
        }

        $pemesan->password = $data['password'];
        $pemesan->save();

        return redirect()->route('customer.akun.profil')->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
