<?php

namespace App\Http\Controllers\Admin;

use App\Support\AturanKolom;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('admin.auth.forgot-password');
    }

    /** Kirim email berisi tautan ubah kata sandi (hanya bila email terdaftar). */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => AturanKolom::email(255),
        ]);

        $status = Password::broker('admins')->sendResetLink(
            $request->only('email')
        );

        // Email terdaftar maupun tidak mendapat jawaban yang SAMA, supaya halaman ini tidak bisa
        // dipakai menebak email mana yang terdaftar. Email hanya benar-benar dikirim bila terdaftar.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->with('error', 'Tautan baru saja dikirim. Silakan tunggu sekitar 1 menit sebelum meminta tautan lagi.');
        }

        return back()->with('success', 'Apabila email tersebut terdaftar, tautan pengaturan ulang kata sandi telah dikirim ke email Anda. Periksa kotak masuk atau folder spam.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        $email = $request->query('email');

        // Tautan yang sudah dipakai, kedaluwarsa (lebih dari 60 menit), atau tidak cocok
        // dengan email langsung ditolak saat dibuka — formulir tidak ditampilkan.
        $broker = Password::broker('admins');
        $akun = is_string($email) ? $broker->getUser(['email' => $email]) : null;
        $tautanBerlaku = $akun && $broker->tokenExists($akun, $token);

        return view('admin.auth.reset-password', [
            'token'         => $token,
            'email'         => $email,
            'tautanBerlaku' => $tautanBerlaku,
        ]);
    }

    /** Simpan kata sandi baru. Sukses redirect ke login (memicu pop up sukses di sana). */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => AturanKolom::email(255),
            'password' => [...AturanKolom::sandiBaru(), 'confirmed'],
        ], [
            'token.required'     => 'Tautan tidak valid.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ], [
            'password' => 'Kata sandi baru',
        ]);

        $status = Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Admin $admin, string $password) {
                $admin->password = $password;
                $admin->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('success', 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.')
            : back()->withInput($request->only('email'))->with('error', 'Tautan tidak valid atau sudah kedaluwarsa. Silakan ajukan tautan baru.');
    }
}
