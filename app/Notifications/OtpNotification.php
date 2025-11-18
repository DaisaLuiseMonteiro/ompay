<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public $otp;
    public $channel;

    public function __construct($otp, $channel)
    {
        $this->otp = $otp;
        $this->channel = $channel;
    }

    public function via($notifiable)
    {
        return [$this->channel === 'sms' ? 'twilio' : 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Votre code de vérification OMPay')
            ->line("Votre code de vérification est : {$this->otp}")
            ->line('Ce code est valable 10 minutes.')
            ->line('Ne partagez jamais ce code avec personne.')
            ->line("Si vous n'avez pas demandé ce code, veuillez ignorer cet email.");
    }

    public function toTwilio($notifiable)
    {
        return "OMPay - Votre code de vérification est : {$this->otp}. Valable 10 minutes. Ne partagez pas ce code.";
    }
}