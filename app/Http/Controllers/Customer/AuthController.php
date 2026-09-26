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

        // Auth::attempt() memanggil Hash::check() di baliknya, yang MELEMPAR RuntimeException
        // (bukan sekadar false) kalau hash di kolom password tidak berformat bcrypt yang valid —
        // mis. baris itu pernah tersentuh langsung lewat database, bukan lewat aplikasi. Tanpa
        // try/catch ini, kondisi itu bikin login gagal dengan error 500 terus-menerus alih-alih
        // pesan "email atau kata sandi salah" yang wajar.
        try {
            $berhasil = Auth::guard('customer')->attempt($credentials, $request->boolean('remember'));
        } catch (\RuntimeException $e) {
            $berhasil = false;
        }

        if (! $berhasil) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau kata sandi salah.');
        }

        $request->session()->regenerate();

        $nama = Auth::guard('customer')->user()?->nama_lengkap;

        // Selalu ke dashboard, BUKAN redirect()->intended() — kalau sebelumnya ada percobaan
        // akses halaman lain saat belum login (mis. link lama/tab lain), pemesan bisa
        // "terlempar" ke halaman itu alih-alih dashboard begitu berhasil login, membingungkan.
        return redirect()->route('customer.dashboard')
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

        // Sengaja TIDAK langsung login otomatis setelah daftar — pemesan diarahkan kembali ke
        // halaman masuk supaya jelas bahwa akunnya sudah dibuat dan perlu login sendiri (bukan
        // seolah "menyelinap" masuk tanpa memasukkan kata sandi yang baru saja mereka buat).
        return redirect()->route('customer.login')
            ->with('success', "Akun \"{$pemesan->nama_lengkap}\" berhasil dibuat. Silakan masuk dengan email dan kata sandi Anda.");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login')->with('success', 'Anda berhasil keluar. Sampai jumpa lagi!');
    }
}
