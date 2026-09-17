<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Pemesan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('customer.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau kata sandi salah.');
        }

        $request->session()->regenerate();

        $nama = Auth::guard('customer')->user()?->nama_lengkap;

        return redirect()->intended(route('customer.dashboard'))
            ->with('success', "Selamat datang, {$nama}!");
    }

    public function showRegister(): View
    {
        return view('customer.auth.register');
    }

    /**
     * Registrasi Pemesan. Kalau email sudah pernah dipakai untuk reservasi guest sebelum
     * fitur login ada, baris Pemesan yang sama diklaim (updateOrCreate by email) — riwayat
     * reservasi lama otomatis langsung terhubung ke akun baru ini.
     */
    public function register(Request $request): RedirectResponse
    {
        $existing = Pemesan::where('email', $request->input('email'))->first();
        if ($existing && $existing->password) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'Email ini sudah terdaftar. Silakan masuk.']);
        }

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'alamat'       => ['required', 'string', 'max:500'],
            'usia'         => ['required', 'integer', 'min:17', 'max:120'],
            'pekerjaan'    => ['required', 'string', 'max:100'],
            'no_telepon'   => ['required', 'string', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'email'        => ['required', 'email', 'max:150'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'no_telepon.regex' => 'Format nomor telepon tidak valid, gunakan angka saja, contoh 0812xxxxxxx.',
        ]);

        $pemesan = Pemesan::updateOrCreate(
            ['email' => $data['email']],
            [
                'nama_lengkap' => $data['nama_lengkap'],
                'alamat'       => $data['alamat'],
                'usia'         => $data['usia'],
                'pekerjaan'    => $data['pekerjaan'],
                'no_telepon'   => $data['no_telepon'],
                'password'     => $data['password'],
            ],
        );

        Auth::guard('customer')->login($pemesan);
        $request->session()->regenerate();

        return redirect()->route('customer.dashboard')->with('success', "Selamat datang, {$pemesan->nama_lengkap}!");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login')->with('success', 'Anda berhasil keluar. Sampai jumpa lagi!');
    }
}
