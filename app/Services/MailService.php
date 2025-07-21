<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class MailService
{
    public static function sendCode(string $to, string $code): bool
    {
        $mail = new PHPMailer(true);

        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'halitbagci88@gmail.com';
            $mail->Password   = 'awskqgwpcvzogwnk'; 
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('halitbagci88@gmail.com', 'PHP project');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code';
            $mail->Body    = "<b>Your Verification Code:</b> {$code}";            

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Exception 
            return false;
        }
    }
}
