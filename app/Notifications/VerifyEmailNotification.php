<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $token = bin2hex(random_bytes(32));
        $now = now();

        DB::table('email_verification_tokens')->updateOrInsert(
            ['user_id' => $notifiable->getKey()],
            [
                'token_hash' => hash('sha256', $token),
                'expires_at' => $now->copy()->addMinutes(60),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $relativeUrl = URL::route('verification.verify', [
            'id' => $notifiable->getKey(),
            'token' => $token,
        ], absolute: false);
        $url = rtrim((string) config('app.url'), '/').$relativeUrl;

        return (new MailMessage)
            ->subject('[POLIBEST] Sahkan alamat emel anda')
            ->greeting('Salam '.$notifiable->name.',')
            ->line('Sahkan alamat emel ini untuk mengaktifkan akses penuh ke portal POLIBEST.')
            ->action('Sahkan Alamat Emel', $url)
            ->line('Pautan pengesahan ini sah selama 60 minit.')
            ->line('Jika anda tidak mendaftar akaun ini, abaikan emel ini.');
    }
}
