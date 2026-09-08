<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('[POLISTAFF] Reset kata laluan')
            ->greeting('Salam '.$notifiable->name.',')
            ->line('Kami menerima permintaan untuk menetapkan semula kata laluan akaun POLISTAFF anda.')
            ->action('Tetapkan Kata Laluan Baharu', $url)
            ->line('Pautan ini akan tamat tempoh dalam '.config('auth.passwords.users.expire').' minit.')
            ->line('Jika anda tidak membuat permintaan ini, abaikan emel ini. Kata laluan anda tidak akan berubah.');
    }
}
