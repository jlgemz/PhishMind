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
            $host = config('mail.mailers.smtp.host') ?: env('MAIL_HOST', 'smtp.gmail.com');
            $username = config('mail.mailers.smtp.username') ?: env('MAIL_USERNAME');
            $password = config('mail.mailers.smtp.password') ?: env('MAIL_PASSWORD');
            $port = (int) (config('mail.mailers.smtp.port') ?: env('MAIL_PORT', 465));
            $scheme = strtolower((string) (config('mail.mailers.smtp.scheme') ?: env('MAIL_ENCRYPTION', '')));

            // If port 465 or scheme ssl, use SMTPS (465); otherwise use STARTTLS (587)
            if ($port === 465 || $scheme === 'ssl' || $scheme === 'smtps') {
                $encryption = PHPMailer::ENCRYPTION_SMTPS;
                $port = 465;
            } else {
                $encryption = PHPMailer::ENCRYPTION_STARTTLS;
                $port = 587;
            }

            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->SMTPSecure = $encryption;
            $mail->Port       = $port;
            $mail->Timeout    = 10;

            // Prevent SSL verification failures in container environments
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $fromAddress = config('mail.from.address') ?: env('MAIL_FROM_ADDRESS') ?: $username ?: 'noreply@phishmind.edu';
            $fromName    = config('mail.from.name') ?: env('MAIL_FROM_NAME', 'PhishMind Security');

            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'PhishMind - Account Validation';

            $mail->Body = "
                <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;text-align:center;'>
                    <h2 style='color:#0f172a;margin-bottom:8px;'>PhishMind Verification</h2>
                    <p style='color:#64748b;font-size:14px;margin-bottom:20px;'>Your 6-digit verification code is:</p>
                    <div style='background:#f0fdf4;border:1px solid #bbf7d0;padding:16px;border-radius:8px;display:inline-block;margin-bottom:20px;'>
                        <span style='font-size:32px;font-weight:bold;letter-spacing:6px;color:#166534;font-family:monospace;'>"
                        . htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8') .
                        "</span>
                    </div>
                    <p style='color:#94a3b8;font-size:12px;margin:0;'>This code expires in 15 minutes. If you did not request this, please ignore this email.</p>
                </div>
            ";

            $mail->AltBody = "Your PhishMind verification code is: {$otpCode} (expires in 15 minutes)";

            $sent = $mail->send();
            if ($sent) {
                Log::info("OTP email successfully sent to {$toEmail} via {$host}:{$port}");
            }
            return $sent;

        } catch (Throwable $e) {
            Log::error('OTP email delivery failed', [
                'recipient' => $toEmail,
                'host'      => $mail->Host ?? 'unknown',
                'port'      => $mail->Port ?? 'unknown',
                'error'     => $e->getMessage(),
                'errorInfo' => $mail->ErrorInfo ?? '',
            ]);

            return false;
        }
    }
}
