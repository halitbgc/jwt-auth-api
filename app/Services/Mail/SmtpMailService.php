<?php

namespace App\Services\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class SmtpMailService implements MailServiceInterface
{
    public function send(string $to, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);
        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST');       // SMTP server address
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME');   // Your mail adress
            $mail->Password   = env('MAIL_PASSWORD');   // Your mail password
            $mail->SMTPSecure = env('MAIL_ENCRYPTION'); // Encryption type
            $mail->Port       = env('MAIL_PORT');       // SMTP port

            $mail->setFrom(env('MAIL_USERNAME'), 'PHP App');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;            

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Exception 
            return false;
        }
    }
}
