<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(): View
    {
        return view('admin.profil.index', ['me' => Auth::guard('admin')->user()]);
    }

    /** Ubah nama & email — konfirmasi cukup lewat pop up di form, tanpa perlu masukkan kata sandi. */
    public function update(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $data = $request->validate([
            'nama_admin'  => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', Rule::unique('admin', 'email')->ignore($admin->id_admin, 'id_admin')],
            'no_whatsapp' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{8,20}$/'],
            'alamat'      => ['required', 'string', 'max:500'],
        ], [
            'no_whatsapp.regex' => 'No. WhatsApp wajib angka, boleh diawali tanda +.',
        ]);

        $admin->nama_admin = $data['nama_admin'];
        $admin->email = $data['email'];
        $admin->no_whatsapp = $data['no_whatsapp'];
        $admin->alamat = $data['alamat'];
        $admin->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /** Ganti kata sandi — form terpisah, verifikasi lewat kata sandi lama itu sendiri. */
    public function password(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $data = $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        // Hash::check() MELEMPAR RuntimeException (bukan sekadar false) kalau hash tersimpan
        // tidak berformat bcrypt yang valid (mis. baris pernah tersentuh langsung lewat
        // database). Ditangani di sini supaya tidak jadi error 500 — cukup anggap kata sandi
        // lama tidak cocok, seperti kondisi salah kata sandi biasa.
        try {
            $lamaCocok = Hash::check($data['password_lama'], $admin->password);
        } catch (\RuntimeException $e) {
            $lamaCocok = false;
        }
        if (! $lamaCocok) {
            return back()->with('error', 'Kata sandi lama salah, kata sandi tidak diubah.');
        }

        if (Hash::check($data['password_baru'], $admin->password)) {
            return back()->withErrors(['password_baru' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.']);
        }

        $admin->password = $data['password_baru'];
        $admin->save();

        return back()->with('success', 'Kata sandi berhasil diubah.');
    }
}
