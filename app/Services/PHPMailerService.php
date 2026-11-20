<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;
use Throwable;

class PHPMailerService
{
    public function sendOTP(string $toEmail, string $otpCode): bool
    {
        $mail = new PHPMailer(true);

        try {
            $port = (int) config('mail.mailers.smtp.port', 443);
            $scheme = strtolower((string) config('mail.mailers.smtp.scheme', 'tls'));

            if ($port === 465 || $scheme === 'ssl' || $scheme === 'smtps') {
                $encryption = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $encryption = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->isSMTP();
            $mail->Host       = config('mail.mailers.smtp.host', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = config('mail.mailers.smtp.username');
            $mail->Password   = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = $encryption;
            $mail->Port       = $port;
            $mail->Timeout    = 15;

            $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username');
            $fromName    = config('mail.from.name', 'PhishMind Security');

            $mail->setFrom($fromAddress, $fromName);

            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'PhishMind - Account Validation';

            $mail->Body = "
                <div style='font-family:Arial;text-align:center;'>
                    <h2>Welcome to PhishMind</h2>
                    <p>Your verification code is:</p>
                    <h1 style='color:#1a7f64;'>"
                    . htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8') .
                    "</h1>
                    <p>This code expires in 15 minutes.</p>
                </div>
            ";

            $mail->AltBody = "Your PhishMind verification code is: {$otpCode}";

            return $mail->send();

        } catch (Throwable $e) {
            Log::error('OTP email delivery failed', [
                'recipient' => $toEmail,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}