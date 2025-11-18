<?php

namespace App\Services\Sms;

use App\Contracts\Notifications\SmsServiceInterface;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioSmsService implements SmsServiceInterface
{
    protected $twilioClient;
    protected $fromNumber;

    public function __construct()
    {
        $this->twilioClient = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
        $this->fromNumber = config('services.twilio.number');
    }

    public function send(string $to, string $message, array $data = []): bool
    {
        try {
            $this->twilioClient->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $message
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Twilio SMS Error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendWithSenderId(string $to, string $message, string $senderId): bool
    {
        return $this->send($to, $message, ['sender_id' => $senderId]);
    }

    public function canSend(): bool
    {
        return config('services.twilio.enabled', false) && 
               !empty(config('services.twilio.sid')) &&
               !empty(config('services.twilio.auth_token')) &&
               !empty(config('services.twilio.number'));
    }
}