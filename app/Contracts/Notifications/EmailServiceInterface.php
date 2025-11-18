<?php

namespace App\Contracts\Notifications;

interface EmailServiceInterface extends NotificationServiceInterface
{
    public function sendWithTemplates(string $to, string $template, array $data = []): bool;
}