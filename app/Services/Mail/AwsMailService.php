<?php

namespace App\Services\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class AwsMailService implements MailServiceInterface
{
    public function send(string $to, string $subject, string $body): bool
    {
        // This is an example
        // Email sending should be done here with AWS

        return false; // Simulate successful send
    }
}
