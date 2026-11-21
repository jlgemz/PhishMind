<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PHPMailerService
{
    /**
     * Send 6-digit OTP code to the recipient.
     * Attempts Resend HTTP API first (port 443, cloud-friendly, zero firewall blocks),
     * and automatically falls back to SMTP (PHPMailer) if Resend is unavailable.
     */
    public function sendOTP(string $toEmail, string $otpCode): bool
    {
        $resendKey = config('services.resend.key') ?: env('RESEND_API_KEY');

        // 1. Try Resend API first if key is configured
        if (!empty($resendKey)) {
            $sent = $this->sendViaResend($toEmail, $otpCode, $resendKey);
            if ($sent) {
                return true;
            }
            Log::info("Resend delivery unfulfilled for {$toEmail}, attempting SMTP fallback...");
        }

        // 2. Fall back to SMTP via PHPMailer
        return $this->sendViaSmtp($toEmail, $otpCode);
    }

    /**
     * Send OTP via Resend REST API (HTTPS port 443).
     */
    protected function sendViaResend(string $toEmail, string $otpCode, string $apiKey): bool
    {
        try {
            $from = config('services.resend.from') 
                ?: env('RESEND_FROM_EMAIL', 'PhishMind Security <onboarding@resend.dev>');

            $html = $this->getOtpHtml($otpCode);
            $text = "Your PhishMind verification code is: {$otpCode} (expires in 15 minutes)";

            $response = Http::withoutVerifying()
                ->timeout(12)
                ->withToken($apiKey)
                ->post('https://api.resend.com/emails', [
                    'from'    => $from,
                    'to'      => [$toEmail],
                    'subject' => 'PhishMind - Your Verification Code: ' . $otpCode,
                    'html'    => $html,
                    'text'    => $text,
                ]);

            if ($response->successful()) {
                $emailId = $response->json('id') ?? 'unknown';
                Log::info("OTP successfully sent via Resend API to {$toEmail} (ID: {$emailId})");
                return true;
            }

            Log::warning("Resend API rejected delivery to {$toEmail} (HTTP {$response->status()}): " . $response->body());
            return false;

        } catch (Throwable $e) {
            Log::warning("Resend API request exception for {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send OTP via SMTP using PHPMailer.
     */
    protected function sendViaSmtp(string $toEmail, string $otpCode): bool
    {
        $mail = new PHPMailer(true);

        try {
            $host = config('mail.mailers.smtp.host') ?: env('MAIL_HOST', 'smtp.gmail.com');
            $username = config('mail.mailers.smtp.username') ?: env('MAIL_USERNAME');
            $password = config('mail.mailers.smtp.password') ?: env('MAIL_PASSWORD');
            $port = (int) (config('mail.mailers.smtp.port') ?: env('MAIL_PORT', 465));
            $scheme = strtolower((string) (config('mail.mailers.smtp.scheme') ?: env('MAIL_ENCRYPTION', '')));

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

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            $fromAddress = $username ?: (config('mail.from.address') ?: env('MAIL_FROM_ADDRESS', 'noreply@phishmind.edu'));
            $fromName    = config('mail.from.name') ?: env('MAIL_FROM_NAME', 'PhishMind Security');

            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'PhishMind - Your Verification Code: ' . $otpCode;
            $mail->Body    = $this->getOtpHtml($otpCode);
            $mail->AltBody = "Your PhishMind verification code is: {$otpCode} (expires in 15 minutes)";

            $sent = $mail->send();
            if ($sent) {
                Log::info("OTP email successfully sent via SMTP to {$toEmail} via {$host}:{$port}");
            }
            return $sent;

        } catch (Throwable $e) {
            Log::error('SMTP email delivery failed', [
                'recipient' => $toEmail,
                'host'      => $mail->Host ?? 'unknown',
                'port'      => $mail->Port ?? 'unknown',
                'error'     => $e->getMessage(),
                'errorInfo' => $mail->ErrorInfo ?? '',
            ]);

            return false;
        }
    }

    /**
     * Build responsive HTML template for OTP email.
     */
    protected function getOtpHtml(string $otpCode): string
    {
        $safeOtp = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');

        return "
            <div style='font-family:Arial,-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;max-width:480px;margin:24px auto;padding:32px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;text-align:center;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);'>
                <div style='display:inline-block;width:48px;height:48px;background:#1a7f64;border-radius:12px;margin-bottom:16px;line-height:48px;color:#ffffff;font-size:24px;font-weight:bold;'>
                    &#128737;
                </div>
                <h2 style='color:#0f172a;margin:0 0 8px 0;font-size:22px;font-weight:bold;'>PhishMind Verification</h2>
                <p style='color:#64748b;font-size:14px;margin:0 0 24px 0;'>Please use the following 6-digit one-time passcode (OTP) to complete your verification:</p>
                <div style='background:#f0fdf4;border:2px dashed #86efac;padding:18px 24px;border-radius:12px;display:inline-block;margin-bottom:24px;'>
                    <span style='font-size:36px;font-weight:800;letter-spacing:8px;color:#166534;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;'>{$safeOtp}</span>
                </div>
                <p style='color:#64748b;font-size:13px;line-height:1.5;margin:0 0 20px 0;'>
                    This code is valid for <strong>15 minutes</strong>.<br>
                    If you did not request this code, no action is required and you can safely ignore this email.
                </p>
                <div style='border-top:1px solid #f1f5f9;padding-top:16px;color:#94a3b8;font-size:11px;'>
                    PhishMind AI Adaptive Phishing Detection & Education Platform
                </div>
            </div>
        ";
    }
}

