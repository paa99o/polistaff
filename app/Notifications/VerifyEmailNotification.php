<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        return (new MailMessage)
            ->subject('[POLISTAFF] Sahkan alamat emel anda')
            ->greeting('Salam '.$notifiable->name.',')
            ->line('Sahkan alamat emel ini untuk mengaktifkan akses penuh ke portal POLISTAFF.')
            ->action('Sahkan Alamat Emel', $url)
            ->line('Pautan pengesahan ini sah selama 60 minit.')
            ->line('Jika anda tidak mendaftar akaun ini, abaikan emel ini.');
    }
}
