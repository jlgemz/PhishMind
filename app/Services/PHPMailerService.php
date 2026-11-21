<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PHPMailerService
{
    /**
     * Send 6-digit OTP code directly to the registered user via Resend REST API (HTTPS port 443).
     */
    public function sendOTP(string $toEmail, string $otpCode): bool
    {
        $resendKey = config('services.resend.key') ?: env('RESEND_API_KEY');

        if (empty($resendKey)) {
            Log::error("Resend API key is missing. Please set RESEND_API_KEY in your environment.");
            return false;
        }

        return $this->sendViaResend($toEmail, $otpCode, $resendKey);
    }

    /**
     * Send OTP via Resend REST API.
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

            Log::error("Resend API rejected delivery to {$toEmail} (HTTP {$response->status()}): " . $response->body());
            return false;

        } catch (Throwable $e) {
            Log::error("Resend API request exception for {$toEmail}: " . $e->getMessage());
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


