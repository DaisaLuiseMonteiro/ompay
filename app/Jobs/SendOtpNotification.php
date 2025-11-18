<?php

namespace App\Jobs;

use App\Contracts\Notifications\EmailServiceInterface;
use App\Contracts\Notifications\SmsServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOtpNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $to,
        public string $code,
        public string $type // 'sms' or 'email'
    ) {}

    public function handle(
        SmsServiceInterface $smsService,
        EmailServiceInterface $emailService
    ): void {
        $message = "Votre code de vérification OMPay est : {$this->code}. Ne le partagez avec personne.";

        if ($this->type === 'sms' && $smsService->canSend()) {
            $smsService->send($this->to, $message);
        } elseif ($this->type === 'email' && $emailService->canSend()) {
            $emailService->send($this->to, $message, [
                'subject' => 'Votre code de vérification OMPay'
            ]);
        }
    }
}