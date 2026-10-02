<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Pemesan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Validasi kolom yang seragam untuk pemesan & admin (lihat App\Support\AturanKolom):
 * telepon hanya angka, email wajib nama@domain.tld, nama/pekerjaan hanya huruf,
 * alamat minimal 10 karakter, dan kata sandi baru berisi huruf + angka.
 */
class ValidasiKolomTest extends TestCase
{
    use DatabaseTransactions;

    private function profilPemesan(array $ubah = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Siti Rahmawati',
            'email'        => 'siti.uji@contoh.test',
            'no_telepon'   => '081234567890',
            'usia'         => 30,
            'pekerjaan'    => 'HR Manager',
            'alamat'       => 'Jl. Sangkuriang No. 8, Cimahi',
        ], $ubah);
    }

    private ?Pemesan $pemesan = null;

    private function kirimProfilPemesan(array $ubah)
    {
        $this->pemesan ??= Pemesan::factory()->create(['email' => 'siti.uji@contoh.test']);

        return $this->actingAs($this->pemesan, 'customer')
            ->put(route('customer.akun.profil.update'), $this->profilPemesan($ubah));
    }

    public function test_no_telepon_menolak_huruf_dan_simbol(): void
    {
        foreach (['08123abc4567', '0812-3456-7890', '(0812) 34567890', '++6281234567', '081234'] as $nilai) {
            $this->kirimProfilPemesan(['no_telepon' => $nilai])
                ->assertSessionHasErrors(['no_telepon' => 'No. telepon harus berupa angka 10–15 digit.']);
        }

        $this->kirimProfilPemesan(['no_telepon' => '+6281234567890'])->assertSessionHasNoErrors();
    }

    public function test_email_wajib_berformat_nama_at_domain(): void
    {
        foreach (['bukan-email', 'nama@domain', 'nama@@email.com', 'nama email@email.com'] as $nilai) {
            $this->kirimProfilPemesan(['email' => $nilai])
                ->assertSessionHasErrors(['email' => 'Format email tidak valid, contoh: nama@email.com.']);
        }
    }

    public function test_nama_pekerjaan_usia_alamat_divalidasi(): void
    {
        $this->kirimProfilPemesan([
            'nama_lengkap' => 'Siti 123',
            'pekerjaan'    => '12345',
            'usia'         => 'tiga puluh',
            'alamat'       => 'Cimahi',
        ])->assertSessionHasErrors([
            'nama_lengkap' => 'Nama lengkap hanya boleh berisi huruf.',
            'pekerjaan'    => 'Pekerjaan hanya boleh berisi huruf.',
            'usia'         => 'Usia harus berupa angka.',
            'alamat'       => 'Alamat minimal 10 karakter.',
        ]);

        $this->kirimProfilPemesan(['usia' => 101])->assertSessionHasErrors(['usia' => 'Usia maksimal 100 tahun.']);
        $this->kirimProfilPemesan(['nama_lengkap' => "Siti Nur'aini Al-Hasan"])->assertSessionHasNoErrors();
    }

    public function test_registrasi_memakai_aturan_yang_sama(): void
    {
        $this->post(route('customer.register.attempt'), [
            'nama_lengkap'          => 'A1',
            'email'                 => 'daftar@domain',
            'no_telepon'            => '0812abc',
            'usia'                  => 15,
            'pekerjaan'             => '',
            'alamat'                => 'Rumah',
            'password'              => 'hanyahuruf',
            'password_confirmation' => 'hanyahuruf',
        ])->assertSessionHasErrors([
            'nama_lengkap' => 'Nama lengkap minimal 3 karakter.',
            'email'        => 'Format email tidak valid, contoh: nama@email.com.',
            'no_telepon'   => 'No. telepon harus berupa angka 10–15 digit.',
            'usia'         => 'Usia minimal 17 tahun.',
            'pekerjaan'    => 'Pekerjaan wajib diisi.',
            'alamat'       => 'Alamat minimal 10 karakter.',
            'password'     => 'Kata sandi harus mengandung minimal satu angka.',
        ]);
    }

    public function test_profil_admin_divalidasi(): void
    {
        $this->actingAs(Admin::firstOrFail(), 'admin')
            ->put(route('admin.profil.update'), [
                'nama_admin'  => 'Admin 01',
                'email'       => 'admin@waduh',
                'no_whatsapp' => '0812 3456 7890',
                'alamat'      => 'Cimahi',
            ])
            ->assertSessionHasErrors([
                'nama_admin'  => 'Nama hanya boleh berisi huruf.',
                'email'       => 'Format email tidak valid, contoh: nama@email.com.',
                'no_whatsapp' => 'No. WhatsApp harus berupa angka 10–15 digit.',
                'alamat'      => 'Alamat minimal 10 karakter.',
            ]);
    }

    public function test_login_menolak_email_tanpa_domain(): void
    {
        $this->post(route('customer.login.attempt'), ['email' => 'nama@domain', 'password' => 'Rahasia123'])
            ->assertSessionHasErrors(['email' => 'Format email tidak valid, contoh: nama@email.com.']);

        $this->post(route('admin.login.attempt'), ['admin_email' => 'admin', 'admin_password' => 'x'])
            ->assertSessionHasErrors(['admin_email' => 'Format email tidak valid, contoh: nama@email.com.']);
    }

    public function test_kode_cek_status_wajib_berformat_rsv(): void
    {
        $this->post(route('cek-status.cari'), ['kode' => 'ABC123'])
            ->assertSessionHasErrors(['kode' => 'Format kode reservasi tidak valid, contoh: RS186.']);

        $this->post(route('cek-status.cari'), ['kode' => 'rs186'])->assertSessionHasNoErrors()->assertRedirect(route('cek-status.hasil', 'RS186'));
        $this->post(route('cek-status.cari'), ['kode' => 'RS-186'])->assertSessionHasErrors('kode');
        // Kode format lama tetap dapat dilacak.
        $this->post(route('cek-status.cari'), ['kode' => 'rsv-7k3m'])->assertSessionHasNoErrors();
    }
}
