<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;

/**
 * Email Verification Notification
 * Ez küldi ki az email megerősítő linket a regisztráció után
 * A létező Blade template-et használja (resources/views/emails/verify-email.blade.php)
 */
class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Email csatornán keresztül küldjük
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
        // Aláírt URL generálása 60 perces érvényességgel
        $verificationUrl = $this->verificationUrl($notifiable);

        // Blade template használata a Mail::send helyett
        return (new MailMessage)
            ->subject('Email cím megerősítése - CampSite')
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl
            ]);
    }

    /**
     * Aláírt verification URL generálása
     * Ez biztosítja, hogy csak érvényes, nem módosított linkek működjenek
     */
    protected function verificationUrl($notifiable)
    {
        // API endpoint-ra mutat, 60 perc érvényesség
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
