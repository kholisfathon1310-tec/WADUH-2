<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Pemesan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Profil & kata sandi — Pemesan dan Admin. DatabaseTransactions me-rollback seluruh
 * perubahan (termasuk kata sandi admin yang di-set di dalam tes) setelah tiap tes.
 */
class ProfilTest extends TestCase
{
    use DatabaseTransactions;

    private const SANDI_LAMA = 'Lama12345';
    private const SANDI_BARU = 'Baru67890';

    private function pemesan(): Pemesan
    {
        return Pemesan::factory()->create(['password' => self::SANDI_LAMA]);
    }

    private function admin(): Admin
    {
        $admin = Admin::firstOrFail();
        $admin->password = self::SANDI_LAMA;
        $admin->save();

        return $admin;
    }

    // ------------------------------------------------------------------ Pemesan

    public function test_pemesan_halaman_profil_memuat_kedua_modal(): void
    {
        $this->actingAs($this->pemesan(), 'customer')
            ->get(route('customer.akun.profil'))
            ->assertOk()
            ->assertSee('id="modalEditProfil"', false)
            ->assertSee('id="modalUbahPassword"', false)
            ->assertDontSee('readonly', false);
    }

    public function test_pemesan_alamat_lama_ubah_sandi_diarahkan_ke_profil(): void
    {
        $this->actingAs($this->pemesan(), 'customer')
            ->get('/customer/profil/password')
            ->assertRedirect(route('customer.akun.profil', ['ubah' => 'sandi']));
    }

    public function test_pemesan_perbarui_profil(): void
    {
        $pemesan = $this->pemesan();

        $this->actingAs($pemesan, 'customer')
            ->put(route('customer.akun.profil.update'), [
                'nama_lengkap' => 'Budi Santoso',
                // Email tidak diganti — penggantian email wajib OTP (lihat OtpEmailTest).
                'email'        => $pemesan->email,
                'no_telepon'   => '081234567890',
                'usia'         => 30,
                'pekerjaan'    => 'Karyawan Swasta',
                'alamat'       => 'Jl. Contoh No. 1, Cimahi',
            ])
            ->assertRedirect(route('customer.akun.profil'))
            ->assertSessionHas('success');

        $this->assertSame('Budi Santoso', $pemesan->fresh()->nama_lengkap);
    }

    public function test_pemesan_profil_tidak_valid_ditolak_per_kolom(): void
    {
        $this->actingAs($this->pemesan(), 'customer')
            ->put(route('customer.akun.profil.update'), [
                'nama_lengkap' => '',
                'email'        => 'bukan-email',
                'no_telepon'   => 'abc',
                'usia'         => 10,
                'pekerjaan'    => '',
                'alamat'       => '',
            ])
            ->assertSessionHasErrors(['nama_lengkap', 'email', 'no_telepon', 'usia', 'pekerjaan', 'alamat']);
    }

    public function test_pemesan_ubah_kata_sandi(): void
    {
        $pemesan = $this->pemesan();

        $this->actingAs($pemesan, 'customer')
            ->put(route('customer.akun.password.update'), [
                'password_lama'         => self::SANDI_LAMA,
                'password'              => self::SANDI_BARU,
                'password_confirmation' => self::SANDI_BARU,
            ])
            ->assertRedirect(route('customer.akun.profil'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check(self::SANDI_BARU, $pemesan->fresh()->password));
    }

    public function test_pemesan_kata_sandi_lama_salah_jadi_galat_kolom(): void
    {
        $pemesan = $this->pemesan();

        $this->actingAs($pemesan, 'customer')
            ->put(route('customer.akun.password.update'), [
                'password_lama'         => 'Salah12345',
                'password'              => self::SANDI_BARU,
                'password_confirmation' => self::SANDI_BARU,
            ])
            ->assertRedirect(route('customer.akun.profil'))
            ->assertSessionHasErrors('password_lama');

        $this->assertTrue(Hash::check(self::SANDI_LAMA, $pemesan->fresh()->password));
    }

    public function test_pemesan_konfirmasi_tidak_cocok_ditolak(): void
    {
        $this->actingAs($this->pemesan(), 'customer')
            ->put(route('customer.akun.password.update'), [
                'password_lama'         => self::SANDI_LAMA,
                'password'              => self::SANDI_BARU,
                'password_confirmation' => 'Lain67890',
            ])
            ->assertSessionHasErrors('password_confirmation');
    }

    public function test_pemesan_kata_sandi_baru_lemah_atau_sama_ditolak(): void
    {
        $pemesan = $this->pemesan();

        $this->actingAs($pemesan, 'customer')
            ->put(route('customer.akun.password.update'), [
                'password_lama'         => self::SANDI_LAMA,
                'password'              => 'pendek1',
                'password_confirmation' => 'pendek1',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($pemesan, 'customer')
            ->put(route('customer.akun.password.update'), [
                'password_lama'         => self::SANDI_LAMA,
                'password'              => self::SANDI_LAMA,
                'password_confirmation' => self::SANDI_LAMA,
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_pemesan_modal_sandi_terbuka_kembali_saat_galat(): void
    {
        $pemesan = $this->pemesan();

        $this->actingAs($pemesan, 'customer')
            ->followingRedirects()
            ->put(route('customer.akun.password.update'), [
                'password_lama'         => 'Salah12345',
                'password'              => self::SANDI_BARU,
                'password_confirmation' => self::SANDI_BARU,
            ])
            ->assertOk()
            ->assertSee('Kata sandi lama tidak sesuai.')
            ->assertSee('getElementById("modalUbahPassword")', false)
            ->assertDontSee('Mohon periksa kembali');
    }

    // ------------------------------------------------------------------ Admin

    public function test_admin_halaman_profil_memuat_kedua_modal(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.profil'))
            ->assertOk()
            ->assertSee('id="modalEditProfil"', false)
            ->assertSee('id="modalUbahPassword"', false)
            ->assertDontSee('readonly', false);
    }

    public function test_admin_perbarui_profil(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.profil.update'), [
                'nama_admin'  => 'Admin Uji',
                // Email tidak diganti — penggantian email wajib verifikasi OTP (lihat OtpEmailTest).
                'email'       => $admin->email,
                'no_whatsapp' => '081234567890',
                'alamat'      => 'Jl. Contoh No. 2, Cimahi',
            ])
            ->assertRedirect(route('admin.profil'))
            ->assertSessionHas('success');

        $this->assertSame('Admin Uji', $admin->fresh()->nama_admin);
    }

    public function test_admin_ubah_kata_sandi(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.profil.password'), [
                'password_lama'              => self::SANDI_LAMA,
                'password_baru'              => self::SANDI_BARU,
                'password_baru_confirmation' => self::SANDI_BARU,
            ])
            ->assertRedirect(route('admin.profil'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check(self::SANDI_BARU, $admin->fresh()->password));
    }

    public function test_admin_kata_sandi_lama_salah_jadi_galat_kolom(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.profil.password'), [
                'password_lama'              => 'Salah12345',
                'password_baru'              => self::SANDI_BARU,
                'password_baru_confirmation' => self::SANDI_BARU,
            ])
            ->assertRedirect(route('admin.profil'))
            ->assertSessionHasErrors('password_lama')
            ->assertSessionMissing('error');

        $this->assertTrue(Hash::check(self::SANDI_LAMA, $admin->fresh()->password));
    }

    public function test_admin_konfirmasi_tidak_cocok_ditolak(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.profil.password'), [
                'password_lama'              => self::SANDI_LAMA,
                'password_baru'              => self::SANDI_BARU,
                'password_baru_confirmation' => 'Lain67890',
            ])
            ->assertSessionHasErrors('password_baru_confirmation');
    }

    public function test_admin_modal_sandi_terbuka_kembali_tanpa_ringkasan_ganda(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->followingRedirects()
            ->put(route('admin.profil.password'), [
                'password_lama'              => 'Salah12345',
                'password_baru'              => self::SANDI_BARU,
                'password_baru_confirmation' => self::SANDI_BARU,
            ])
            ->assertOk()
            ->assertSee('Kata sandi lama tidak sesuai.')
            ->assertSee('getElementById("modalUbahPassword")', false)
            ->assertDontSee('Mohon periksa kembali');
    }
}
