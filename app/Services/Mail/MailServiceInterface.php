<?php
namespace App\Services\Mail;

interface MailServiceInterface
{
    public function send(string $to, string $subject, string $body): bool;
}
