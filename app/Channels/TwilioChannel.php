<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;

class TwilioChannel
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toTwilio($notifiable);
        
        $twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );

        $twilio->messages->create(
            $notifiable->telephone,
            [
                'from' => config('services.twilio.number'),
                'body' => $message
            ]
        );
    }
}