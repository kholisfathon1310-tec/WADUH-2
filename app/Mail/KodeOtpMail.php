<?php

namespace App\Mail;

use App\Services\OtpEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KodeOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $kode,
        public readonly string $tujuan,
        public readonly int $berlakuMenit,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Kode Verifikasi Email WADUH: {$this->kode}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.kode-otp', with: [
            'keperluan' => match ($this->tujuan) {
                OtpEmailService::TUJUAN_UBAH_EMAIL       => 'mengonfirmasi perubahan alamat email akun pemesan Anda',
                OtpEmailService::TUJUAN_UBAH_EMAIL_ADMIN => 'mengonfirmasi perubahan alamat email akun admin Anda',
                default                                  => 'memverifikasi email untuk pendaftaran akun pemesan Anda',
            },
        ]);
    }
}
