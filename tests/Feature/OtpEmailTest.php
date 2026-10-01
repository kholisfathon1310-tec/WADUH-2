<?php

namespace Tests\Feature;

use App\Mail\KodeOtpMail;
use App\Models\Admin;
use App\Models\Pemesan;
use App\Services\OtpEmailService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Alur OTP: Kirim OTP → masukkan kode → Verifikasi (centang) → baru boleh daftar / simpan email baru. */
class OtpEmailTest extends TestCase
{
    use DatabaseTransactions;

    private string $kodeTerkirim = '';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    private function kirimKode(string $url, string $email): void
    {
        $this->postJson($url, ['email' => $email])->assertOk()->assertJson(['ok' => true]);
        Mail::assertSent(KodeOtpMail::class, function (KodeOtpMail $m) use ($email) {
            $this->kodeTerkirim = $m->kode;

            return $m->hasTo($email);
        });
    }

    private function kodeSalah(): string
    {
        return $this->kodeTerkirim === '000000' ? '111111' : '000000';
    }

    private function dataDaftar(string $email): array
    {
        return [
            'nama_lengkap' => 'Calon Pemesan', 'usia' => 25, 'pekerjaan' => 'Wirausaha',
            'email' => $email, 'no_telepon' => '081234567890', 'alamat' => 'Jl. Contoh No. 1',
            'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123',
        ];
    }

    public function test_daftar_kirim_verifikasi_lalu_akun_aktif(): void
    {
        $email = 'otp.valid@contoh.test';
        $this->kirimKode(route('customer.register.otp'), $email);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->kodeTerkirim);

        // Kode salah → ditolak, belum terverifikasi.
        $this->postJson(route('customer.register.otp.verifikasi'), ['email' => $email, 'kode' => $this->kodeSalah()])
            ->assertStatus(422)->assertJson(['ok' => false, 'pesan' => 'Kode OTP tidak sesuai. Sisa percobaan: 4.']);

        // Kode benar → terverifikasi (centang).
        $this->postJson(route('customer.register.otp.verifikasi'), ['email' => $email, 'kode' => $this->kodeTerkirim])
            ->assertOk()->assertJson(['ok' => true]);

        $this->post(route('customer.register.attempt'), $this->dataDaftar($email))
            ->assertRedirect(route('customer.login'))
            ->assertSessionHasNoErrors();
        $this->assertNotNull(Pemesan::where('email', $email)->value('password'));
    }

    public function test_daftar_tanpa_verifikasi_ditolak_dan_akun_tidak_dibuat(): void
    {
        $email = 'otp.belum@contoh.test';
        $this->from(route('customer.register'))->post(route('customer.register.attempt'), $this->dataDaftar($email))
            ->assertSessionHasErrors('email');

        // Sudah dikirim tetapi belum diverifikasi → tetap ditolak.
        $this->kirimKode(route('customer.register.otp'), $email);
        $this->from(route('customer.register'))->post(route('customer.register.attempt'), $this->dataDaftar($email))
            ->assertSessionHasErrors('email');

        // Verifikasi untuk email A tidak berlaku untuk email B.
        $this->postJson(route('customer.register.otp.verifikasi'), ['email' => $email, 'kode' => $this->kodeTerkirim])->assertOk();
        $this->from(route('customer.register'))->post(route('customer.register.attempt'), $this->dataDaftar('lain@contoh.test'))
            ->assertSessionHasErrors('email');

        $this->assertFalse(Pemesan::whereIn('email', [$email, 'lain@contoh.test'])->exists());
    }

    public function test_kode_otp_kedaluwarsa_setelah_batas_waktu(): void
    {
        $email = 'otp.kedaluwarsa@contoh.test';
        $this->kirimKode(route('customer.register.otp'), $email);

        $this->travel(OtpEmailService::BERLAKU_MENIT * 60 + 1)->seconds();

        $this->postJson(route('customer.register.otp.verifikasi'), ['email' => $email, 'kode' => $this->kodeTerkirim])
            ->assertStatus(422)->assertJson(['pesan' => 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang kode OTP.']);
    }

    public function test_kirim_ulang_dibatasi_jeda_dan_email_terdaftar_ditolak(): void
    {
        $email = 'otp.jeda@contoh.test';
        $this->kirimKode(route('customer.register.otp'), $email);
        $this->postJson(route('customer.register.otp'), ['email' => $email])
            ->assertStatus(422)->assertJson(['ok' => false]);

        $terdaftar = Pemesan::factory()->create(['email' => 'sudah.ada@contoh.test', 'password' => 'Rahasia123']);
        $this->postJson(route('customer.register.otp'), ['email' => $terdaftar->email])
            ->assertStatus(422)->assertJsonPath('errors.email.0', 'Email ini sudah terdaftar. Silakan masuk.');
    }

    public function test_salah_lima_kali_kode_hangus(): void
    {
        $email = 'otp.lima@contoh.test';
        $this->kirimKode(route('customer.register.otp'), $email);

        for ($i = 1; $i < OtpEmailService::MAKS_PERCOBAAN; $i++) {
            $this->postJson(route('customer.register.otp.verifikasi'), ['email' => $email, 'kode' => $this->kodeSalah()])->assertStatus(422);
        }
        $this->postJson(route('customer.register.otp.verifikasi'), ['email' => $email, 'kode' => $this->kodeSalah()])
            ->assertStatus(422)->assertJsonFragment(['ok' => false]);
        // Kode benar pun sudah tidak berlaku.
        $this->postJson(route('customer.register.otp.verifikasi'), ['email' => $email, 'kode' => $this->kodeTerkirim])->assertStatus(422);
    }

    public function test_ubah_email_profil_pemesan_wajib_verifikasi(): void
    {
        $pemesan = Pemesan::factory()->create([
            'email' => 'lama@contoh.test', 'password' => 'Rahasia123', 'nama_lengkap' => 'Pemesan Uji',
            'alamat' => 'Jl. Uji', 'usia' => 30, 'pekerjaan' => 'Penguji', 'no_telepon' => '081200000000',
        ]);
        $this->actingAs($pemesan, 'customer');
        $profil = ['nama_lengkap' => 'Pemesan Uji', 'alamat' => 'Jl. Uji', 'usia' => 30, 'pekerjaan' => 'Penguji', 'no_telepon' => '081200000000'];

        $this->put(route('customer.akun.profil.update'), $profil + ['email' => 'baru@contoh.test'])->assertSessionHasErrors('email');
        $this->assertSame('lama@contoh.test', $pemesan->fresh()->email);

        $this->postJson(route('customer.akun.profil.otp'), ['email' => 'lama@contoh.test'])->assertStatus(422);

        $this->kirimKode(route('customer.akun.profil.otp'), 'baru@contoh.test');
        $this->postJson(route('customer.akun.profil.otp.verifikasi'), ['email' => 'baru@contoh.test', 'kode' => $this->kodeTerkirim])->assertOk();
        $this->put(route('customer.akun.profil.update'), $profil + ['email' => 'baru@contoh.test'])->assertSessionHasNoErrors();
        $this->assertSame('baru@contoh.test', $pemesan->fresh()->email);

        // Perubahan data lain tanpa mengganti email tidak memerlukan OTP.
        $this->put(route('customer.akun.profil.update'), array_merge($profil, ['pekerjaan' => 'Konsultan', 'email' => 'baru@contoh.test']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Konsultan', $pemesan->fresh()->pekerjaan);
    }

    public function test_ubah_email_profil_admin_wajib_verifikasi(): void
    {
        $admin = Admin::firstOrFail();
        $emailLama = $admin->email;
        $this->actingAs($admin, 'admin');
        $profil = ['nama_admin' => $admin->nama_admin, 'no_whatsapp' => '081200000001', 'alamat' => 'Jl. Admin'];

        $this->put(route('admin.profil.update'), $profil + ['email' => 'admin.baru@contoh.test'])->assertSessionHasErrors('email');
        $this->assertSame($emailLama, $admin->fresh()->email);

        $this->kirimKode(route('admin.profil.otp'), 'admin.baru@contoh.test');
        $this->postJson(route('admin.profil.otp.verifikasi'), ['email' => 'admin.baru@contoh.test', 'kode' => $this->kodeTerkirim])->assertOk();
        $this->put(route('admin.profil.update'), $profil + ['email' => 'admin.baru@contoh.test'])->assertSessionHasNoErrors();
        $this->assertSame('admin.baru@contoh.test', $admin->fresh()->email);
    }

    public function test_halaman_menampilkan_tombol_kirim_dan_verifikasi_otp(): void
    {
        $this->get(route('customer.register'))->assertOk()->assertSee('Kirim OTP')->assertSee('Verifikasi');

        $pemesan = Pemesan::factory()->create(['password' => 'Rahasia123']);
        $this->actingAs($pemesan, 'customer')->get(route('customer.akun.profil'))->assertOk()->assertSee('Kirim OTP');
        $this->actingAs(Admin::firstOrFail(), 'admin')->get(route('admin.profil'))->assertOk()->assertSee('Kirim OTP');
    }
}
