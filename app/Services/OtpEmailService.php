<?php

namespace App\Services;

use App\Mail\KodeOtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Kode OTP verifikasi email (6 angka) — dipakai saat registrasi pemesan dan saat pemesan
 * mengganti email di profil, untuk memastikan alamat email benar-benar aktif dan miliknya.
 *
 * Disimpan di Cache (bukan tabel) karena sifatnya sementara: kode di-hash, berlaku
 * BERLAKU_MENIT menit, sekali pakai, dibatasi MAKS_PERCOBAAN percobaan salah, dan pengiriman
 * ulang baru boleh setelah JEDA_DETIK detik.
 */
class OtpEmailService
{
    public const BERLAKU_MENIT = 5;
    public const JEDA_DETIK = 60;
    public const MAKS_PERCOBAAN = 5;

    public const TUJUAN_REGISTRASI = 'registrasi';
    public const TUJUAN_UBAH_EMAIL = 'ubah_email';

    public const TUJUAN_UBAH_EMAIL_ADMIN = 'ubah_email_admin';

    /**
     * Kirim kode OTP baru ke email.
     *
     * @return array{ok: bool, pesan: string, berlaku_detik?: int, jeda_detik?: int}
     */
    public function kirim(string $tujuan, string $email, ?int $pemilik = null): array
    {
        $kunci = $this->kunci($tujuan, $email, $pemilik);
        $lama = Cache::get($kunci);

        if ($lama && now()->getTimestamp() < $lama['boleh_kirim_ulang']) {
            $tunggu = $lama['boleh_kirim_ulang'] - now()->getTimestamp();

            return [
                'ok'            => false,
                'pesan'         => "Kode OTP baru saja dikirim. Silakan tunggu {$tunggu} detik sebelum mengirim ulang.",
                'berlaku_detik' => max(0, $lama['kedaluwarsa'] - now()->getTimestamp()),
                'jeda_detik'    => $tunggu,
            ];
        }

        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            Mail::to($email)->send(new KodeOtpMail($kode, $tujuan, self::BERLAKU_MENIT));
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'pesan' => 'Kode OTP gagal dikirim. Pastikan alamat email benar, lalu coba beberapa saat lagi.'];
        }

        Cache::put($kunci, [
            'hash'              => $this->hash($kode),
            'kedaluwarsa'       => now()->getTimestamp() + self::BERLAKU_MENIT * 60,
            'boleh_kirim_ulang' => now()->getTimestamp() + self::JEDA_DETIK,
            'percobaan'         => 0,
        ], now()->addMinutes(self::BERLAKU_MENIT + 30));

        return [
            'ok'            => true,
            'pesan'         => "Kode OTP telah dikirim ke {$email}. Periksa kotak masuk atau folder spam.",
            'berlaku_detik' => self::BERLAKU_MENIT * 60,
            'jeda_detik'    => self::JEDA_DETIK,
        ];
    }

    /**
     * Sisa waktu kode yang sedang berlaku (untuk menampilkan hitung mundur setelah halaman
     * dimuat ulang), atau null bila belum ada kode.
     *
     * @return array{berlaku_detik: int, jeda_detik: int}|null
     */
    public function status(string $tujuan, ?string $email, ?int $pemilik = null): ?array
    {
        if (! $email) {
            return null;
        }
        $data = Cache::get($this->kunci($tujuan, $email, $pemilik));

        return $data ? [
            'berlaku_detik' => max(0, $data['kedaluwarsa'] - now()->getTimestamp()),
            'jeda_detik'    => max(0, $data['boleh_kirim_ulang'] - now()->getTimestamp()),
        ] : null;
    }

    /** Periksa kode. Null = valid (kode langsung hangus); selain itu pesan kesalahannya. */
    public function verifikasi(string $tujuan, string $email, ?string $kode, ?int $pemilik = null): ?string
    {
        $kunci = $this->kunci($tujuan, $email, $pemilik);
        $data = Cache::get($kunci);

        if (! $data) {
            return 'Kode OTP belum dikirim ke email ini. Tekan "Kirim OTP" terlebih dahulu.';
        }
        if (now()->getTimestamp() > $data['kedaluwarsa']) {
            Cache::forget($kunci);

            return 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang kode OTP.';
        }
        if (hash_equals($data['hash'], $this->hash((string) $kode))) {
            Cache::forget($kunci);

            return null;
        }

        $data['percobaan']++;
        if ($data['percobaan'] >= self::MAKS_PERCOBAAN) {
            Cache::forget($kunci);

            return 'Kode OTP salah sebanyak '.self::MAKS_PERCOBAAN.' kali. Silakan kirim ulang kode OTP yang baru.';
        }
        Cache::put($kunci, $data, now()->addMinutes(self::BERLAKU_MENIT + 30));

        return 'Kode OTP tidak sesuai. Sisa percobaan: '.(self::MAKS_PERCOBAAN - $data['percobaan']).'.';
    }

    // ---------------------------------------------------------------------
    // Status "email terverifikasi" — disimpan di session setelah kode OTP dicek lewat
    // tombol Verifikasi, supaya form utama (daftar / simpan profil) cukup memeriksa
    // bahwa email yang dikirim memang email yang baru saja diverifikasi.
    // ---------------------------------------------------------------------

    public const VERIFIKASI_BERLAKU_MENIT = 30;

    /** Periksa kode; bila valid, tandai email terverifikasi di session. Null = berhasil. */
    public function verifikasiDanTandai(string $tujuan, string $email, ?string $kode, ?int $pemilik = null): ?string
    {
        $galat = $this->verifikasi($tujuan, $email, $kode, $pemilik);
        if ($galat === null) {
            session()->put('otp_terverifikasi.'.$tujuan, [
                'email'   => mb_strtolower(trim($email)),
                'pemilik' => $pemilik,
                'sampai'  => now()->addMinutes(self::VERIFIKASI_BERLAKU_MENIT)->getTimestamp(),
            ]);
        }

        return $galat;
    }

    public function sudahTerverifikasi(string $tujuan, ?string $email, ?int $pemilik = null): bool
    {
        $data = session('otp_terverifikasi.'.$tujuan);

        return $email !== null
            && is_array($data)
            && $data['email'] === mb_strtolower(trim($email))
            && $data['pemilik'] === $pemilik
            && now()->getTimestamp() <= $data['sampai'];
    }

    public function lupakanVerifikasi(string $tujuan): void
    {
        session()->forget('otp_terverifikasi.'.$tujuan);
    }

    private function kunci(string $tujuan, string $email, ?int $pemilik): string
    {
        return 'otp:'.$tujuan.':'.($pemilik ?? '-').':'.sha1(mb_strtolower(trim($email)));
    }

    private function hash(string $kode): string
    {
        return hash_hmac('sha256', $kode, (string) config('app.key'));
    }
}
