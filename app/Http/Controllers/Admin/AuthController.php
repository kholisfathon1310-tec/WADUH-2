<?php

namespace App\Http\Controllers\Admin;

use App\Support\AturanKolom;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_email'    => AturanKolom::email(255),
            'admin_password' => ['required', 'string', 'max:100'],
        ]);
        $credentials = ['email' => $data['admin_email'], 'password' => $data['admin_password']];

        // Auth::attempt() memanggil Hash::check() di baliknya, yang MELEMPAR RuntimeException
        // (bukan sekadar false) kalau hash yang tersimpan di kolom password tidak berformat
        // bcrypt yang valid — mis. kalau baris itu pernah tersentuh langsung lewat database
        // (bukan lewat aplikasi) sehingga isinya kebetulan plaintext atau rusak. Tanpa try/catch
        // ini, kondisi itu bikin login gagal dengan error 500 terus-menerus alih-alih pesan
        // "email atau kata sandi salah" yang wajar.
        try {
            $berhasil = Auth::guard('admin')->attempt($credentials, $request->boolean('remember'));
        } catch (\RuntimeException $e) {
            $berhasil = false;
        }

        if (! $berhasil) {
            return back()
                ->withInput($request->only('admin_email'))
                ->with('error', 'Email atau kata sandi salah.');
        }

        $request->session()->regenerate();

        $nama = Auth::guard('admin')->user()?->nama_admin;

        // Selalu ke dashboard, BUKAN redirect()->intended() — kalau sebelumnya ada percobaan
        // akses halaman lain saat belum login (mis. link lama/tab lain), pemesan/admin bisa
        // "terlempar" ke halaman itu alih-alih dashboard begitu berhasil login, membingungkan.
        return redirect()->route('admin.dashboard')
            ->with('success', "Selamat datang, {$nama}.");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
