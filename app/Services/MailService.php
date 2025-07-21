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
            // SMTP ayarları
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // SMTP sunucusu
            $mail->SMTPAuth   = true;
            $mail->Username   = 'halitbagci88@gmail.com'; // Gönderen email
            $mail->Password   = 'awskqgwpcvzogwnk';     // Gmail app password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('halitbagci88@gmail.com', 'PHP project');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = 'Şifre Sıfırlama Kodu';
            $mail->Body    = "<b>Doğrulama Kodun:</b> {$code}";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Hata varsa loglayabilirsin
            return false;
        }
    }
}
