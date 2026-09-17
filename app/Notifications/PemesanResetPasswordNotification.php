<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PemesanResetPasswordNotification extends Notification
{
    public function __construct(private readonly string $url)
    {
    }

    /** @return string[] */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ubah Kata Sandi Pemesan — WADUH')
            ->greeting('Halo, '.$notifiable->nama_lengkap.'!')
            ->line('Kami menerima permintaan untuk mengubah kata sandi akun Pemesan Anda.')
            ->action('Ubah Kata Sandi', $this->url)
            ->line('Tautan ini berlaku selama 60 menit.')
            ->line('Jika Anda tidak meminta perubahan kata sandi, abaikan email ini.');
    }
}
