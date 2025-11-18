<?php

namespace App\Contracts\Notifications;

interface SmsServiceInterface extends NotificationServiceInterface
{
    public function sendWithSenderId(string $to, string $message, string $senderId): bool;
}