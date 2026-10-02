<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VerifikasiOtpEmail;
use App\Http\Controllers\Controller;
use App\Services\OtpEmailService;
use App\Support\AturanKolom;
use Illuminate\Http\JsonResponse;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilController extends Controller
{
    use VerifikasiOtpEmail;

    public function edit(OtpEmailService $otp): View
    {
        $me = Auth::guard('admin')->user();

        return view('admin.profil.index', [
            'me'               => $me,
            'otpStatus'        => $otp->status(OtpEmailService::TUJUAN_UBAH_EMAIL_ADMIN, old('email'), $me->id_admin),
            'otpTerverifikasi' => $otp->sudahTerverifikasi(OtpEmailService::TUJUAN_UBAH_EMAIL_ADMIN, old('email'), $me->id_admin),
        ]);
    }

    /** AJAX: kirim kode OTP ke email BARU admin sebelum email akun boleh diganti. */
    public function kirimOtpEmail(Request $request, OtpEmailService $otp): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        $data = $request->validate([
            'email' => [...AturanKolom::email(), Rule::unique('admin', 'email')->ignore($admin->id_admin, 'id_admin')],
        ], [
            'email.unique' => 'Email tersebut sudah digunakan akun lain.',
        ]);

        if (mb_strtolower($data['email']) === mb_strtolower($admin->email)) {
            return response()->json(['ok' => false, 'errors' => ['email' => ['Email baru sama dengan email saat ini.']]], 422);
        }

        $hasil = $otp->kirim(OtpEmailService::TUJUAN_UBAH_EMAIL_ADMIN, $data['email'], $admin->id_admin);

        return response()->json($hasil, $hasil['ok'] ? 200 : 422);
    }

    /** AJAX: cek kode OTP email baru admin. */
    public function verifikasiOtpEmail(Request $request, OtpEmailService $otp): JsonResponse
    {
        return $this->jawabVerifikasiOtp($request, $otp, OtpEmailService::TUJUAN_UBAH_EMAIL_ADMIN, Auth::guard('admin')->id());
    }

    /** Ubah data identitas — konfirmasi cukup lewat dialog di form, tanpa perlu memasukkan kata sandi. */
    public function update(Request $request, OtpEmailService $otp): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $data = $request->validate([
            'nama_admin'  => AturanKolom::nama(),
            'email'       => [...AturanKolom::email(), Rule::unique('admin', 'email')->ignore($admin->id_admin, 'id_admin')],
            'no_whatsapp' => AturanKolom::telepon(),
            'alamat'      => AturanKolom::alamat(),
        ], [
            'email.unique' => 'Email tersebut sudah digunakan akun lain.',
        ]);

        // Email diganti → wajib sudah diverifikasi dengan kode OTP yang dikirim ke email BARU.
        $gantiEmail = mb_strtolower($data['email']) !== mb_strtolower($admin->email);
        if ($gantiEmail && ! $otp->sudahTerverifikasi(OtpEmailService::TUJUAN_UBAH_EMAIL_ADMIN, $data['email'], $admin->id_admin)) {
            return redirect()->route('admin.profil')
                ->withInput()
                ->withErrors(['email' => 'Email belum diverifikasi. Tekan "Kirim OTP", masukkan kode dari email, lalu tekan "Verifikasi".']);
        }

        $admin->nama_admin = $data['nama_admin'];
        $admin->email = $data['email'];
        $admin->no_whatsapp = $data['no_whatsapp'];
        $admin->alamat = $data['alamat'];
        $admin->save();
        if ($gantiEmail) {
            $otp->lupakanVerifikasi(OtpEmailService::TUJUAN_UBAH_EMAIL_ADMIN);
        }

        return redirect()->route('admin.profil')->with('success', 'Profil berhasil diperbarui.');
    }

    /** Ganti kata sandi — form terpisah, verifikasi lewat kata sandi lama itu sendiri. */
    public function password(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        // `same` (bukan `confirmed`) supaya pesan ketidakcocokan menempel pada kolom
        // konfirmasi itu sendiri, bukan pada kolom kata sandi baru.
        $data = $request->validate([
            'password_lama'              => ['required', 'string', 'max:100'],
            'password_baru'              => AturanKolom::sandiBaru(),
            'password_baru_confirmation' => ['required', 'string', 'same:password_baru'],
        ], [
            'password_lama.required'              => 'Kata sandi lama wajib diisi.',
            'password_baru.required'              => 'Kata sandi baru wajib diisi.',
            'password_baru_confirmation.required' => 'Konfirmasi kata sandi baru wajib diisi.',
            'password_baru_confirmation.same'     => 'Konfirmasi kata sandi tidak cocok.',
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
        // Dikembalikan sebagai galat KOLOM (bukan flash) supaya modal terbuka kembali dengan
        // pesan tepat di bawah kolom kata sandi lama — tidak perlu membuka modal ulang.
        if (! $lamaCocok) {
            return redirect()->route('admin.profil')
                ->withErrors(['password_lama' => 'Kata sandi lama tidak sesuai.']);
        }

        if ($data['password_baru'] === $data['password_lama']) {
            return redirect()->route('admin.profil')
                ->withErrors(['password_baru' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.']);
        }

        $admin->password = $data['password_baru'];
        $admin->save();

        return redirect()->route('admin.profil')->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
