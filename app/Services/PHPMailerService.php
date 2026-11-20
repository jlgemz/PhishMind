<?php
namespace App\Services;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PHPMailerService {
    public function sendOTP($toEmail, $otpCode) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST', 'smtp.mailtrap.io');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME');
            $mail->Password   = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);
            $mail->Port       = env('MAIL_PORT', 2525);
            $mail->setFrom(env('MAIL_FROM_ADDRESS', 'noreply@phishmind.edu'), 'PhishMind Security');
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'PhishMind - Account Validation';
            $mail->Body    = "<div style='font-family: Arial; text-align:center;'><h2>Welcome to PhishMind</h2><p>Your OTP is:</p><h1 style='color:#1a7f64;'>$otpCode</h1></div>";
            $mail->send();
        } catch (Exception $e) { \Log::error("PHPMailer Error: {$mail->ErrorInfo}"); }
    }
}