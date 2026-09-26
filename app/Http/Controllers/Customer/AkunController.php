<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function profil(): View
    {
        return view('customer.akun.profil', ['pemesan' => Auth::guard('customer')->user()]);
    }

    public function updateProfil(Request $request): RedirectResponse
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
            'nama_lengkap.regex' => 'Nama hanya boleh berisi huruf dan tanda baca umum (spasi, titik, apostrof, strip).',
            'no_telepon.regex'   => 'No. Telepon wajib angka, boleh diawali tanda +, contoh 0812xxxxxxx.',
        ]);

        $pemesan->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(): View
    {
        return view('customer.akun.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $pemesan = Auth::guard('customer')->user();

        $data = $request->validate([
            'password_lama' => ['required', 'string'],
            'password'      => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
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
            return back()->withErrors(['password_lama' => 'Kata sandi lama salah.']);
        }

        if (Hash::check($data['password'], $pemesan->password)) {
            return back()->withErrors(['password' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.']);
        }

        $pemesan->password = $data['password'];
        $pemesan->save();

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
