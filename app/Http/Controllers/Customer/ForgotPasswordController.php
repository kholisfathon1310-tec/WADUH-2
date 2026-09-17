<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Pemesan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('customer.auth.forgot-password');
    }

    /** Kirim email berisi tautan ubah kata sandi. Pop up "gagal" muncul kalau email tidak terdaftar. */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email belum benar.',
        ]);

        $status = Password::broker('pemesans')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'Tautan ubah kata sandi sudah dikirim ke email Anda.')
            : back()->withInput()->with('error', 'Email tidak terdaftar sebagai akun pemesan.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('customer.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** Simpan kata sandi baru. Sukses redirect ke login (memicu pop up sukses di sana). */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'token.required'       => 'Tautan tidak valid.',
            'email.required'       => 'Email wajib diisi.',
            'email.email'          => 'Format email belum benar.',
            'password.required'    => 'Kata sandi baru wajib diisi.',
            'password.min'         => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed'   => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $status = Password::broker('pemesans')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Pemesan $pemesan, string $password) {
                $pemesan->password = $password;
                $pemesan->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('customer.login')->with('success', 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.')
            : back()->withInput($request->only('email'))->with('error', 'Tautan sudah kedaluwarsa atau tidak valid. Silakan minta tautan baru.');
    }
}
