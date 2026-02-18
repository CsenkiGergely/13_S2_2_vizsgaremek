<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password Reset Notification
 * Ez küldi ki a jelszó-visszaállító linket
 * A létező Blade template-et használja (resources/views/emails/reset-password.blade.php)
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * A reset token amit a user megkap
     */
    public $token;

    /**
     * Constructor - token beállítása
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Notification csatornák
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Email üzenet felépítése a Blade template használatával
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Frontend URL-re mutat, ahova a user átirányítódik
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        // Blade template használata a meglévő reset-password.blade.php fájlból
        return (new MailMessage)
            ->subject('Jelszó visszaállítás - CampSite')
            ->view('emails.reset-password', [
                'user' => $notifiable,
                'resetUrl' => $resetUrl
            ]);
    }
}
