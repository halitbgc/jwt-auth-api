<?php
namespace App\Services\Mail;


require_once __DIR__ . '/../../vendor/autoload.php';

class MailServiceFactory
{
    public static function create(): MailServiceInterface
    {
        $driver = env('MAIL_DRIVER') ?: 'smtp';

        return match ($driver) {
            'smtp' => new SmtpMailService(),
            'aws'  => new AwsMailService(),
            default => throw new \Exception("Unsupported mail driver: $driver")
        };
    }
}
