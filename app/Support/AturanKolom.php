<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Aturan validasi kolom yang dipakai bersama oleh seluruh form (pemesan maupun admin),
 * supaya satu jenis kolom selalu divalidasi dengan cara dan pesan yang sama.
 * Pesan galatnya ada di lang/id/validation.php (bagian 'custom'), dan atribut HTML
 * yang setara (pattern, minlength, dst.) dipasang di form masing-masing.
 */
class AturanKolom
{
    /** Pola yang sama dipakai di atribut `pattern` form (tanpa pembatas /…/u). */
    public const POLA_NAMA = "^[\\p{L}\\s.'\\-]+$";
    public const POLA_TELEPON = '^\\+?[0-9]{10,15}$';

    public static function nama(): array
    {
        return ['required', 'string', 'min:3', 'max:100', 'regex:/'.self::POLA_NAMA.'/u'];
    }

    /** Email wajib berformat nama@domain.tld (email:filter menolak "nama@domain" tanpa titik). */
    public static function email(int $maks = 150): array
    {
        return ['required', 'string', 'email:filter', 'max:'.$maks];
    }

    public static function telepon(): array
    {
        return ['required', 'string', 'regex:/'.self::POLA_TELEPON.'/'];
    }

    public static function usia(): array
    {
        return ['required', 'integer', 'min:17', 'max:100'];
    }

    public static function pekerjaan(): array
    {
        return ['required', 'string', 'min:3', 'max:100', "regex:/^[\\p{L}\\s.,'\\/&()\\-]+$/u"];
    }

    public static function alamat(): array
    {
        return ['required', 'string', 'min:10', 'max:500'];
    }

    /** Kata sandi baru: minimal 8 karakter, mengandung huruf dan angka. */
    public static function sandiBaru(): array
    {
        return ['required', 'string', 'max:100', Password::min(8)->letters()->numbers()];
    }
}
