<?php

namespace App\Models;

use App\Notifications\PemesanResetPasswordNotification;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Pemesan login memakai guard `customer` (lihat config/auth.php), mirror pola Admin.
 * Baris Pemesan lama (dibuat guest via checkout) tetap valid — password nullable sampai
 * pemiliknya mendaftar dengan email yang sama (Pemesan::updateOrCreate), sehingga riwayat
 * reservasi lama otomatis ter-link ke akun baru.
 */
class Pemesan extends Authenticatable implements CanResetPasswordContract
{
    use HasFactory, Notifiable, CanResetPasswordTrait;

    protected $table = 'pemesan';
    protected $primaryKey = 'id_pemesan';

    protected $fillable = [
        'nama_lengkap',
        'alamat',
        'usia',
        'pekerjaan',
        'no_telepon',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'usia' => 'integer',
        // Auto-hash (bcrypt) saat atribut password di-set.
        'password' => 'hashed',
    ];

    public function reservasi(): HasMany
    {
        return $this->hasMany(Reservasi::class, 'id_pemesan', 'id_pemesan');
    }

    /**
     * Override default: link reset harus mengarah ke halaman Pemesan (bukan `password.reset`
     * milik guard `users`), pakai notifikasi berbahasa Indonesia tersendiri.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('customer.password.reset', ['token' => $token, 'email' => $this->email]);

        $this->notify(new PemesanResetPasswordNotification($url));
    }
}
