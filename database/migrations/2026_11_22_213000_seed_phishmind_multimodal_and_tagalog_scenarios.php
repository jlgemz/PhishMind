<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Scenario;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Curated core multimodal scenarios including Filipino communications & popular platforms
        $coreScenarios = [
            // Popular Websites & Shortened URLs
            [
                'type' => 'URL / Web Phishing',
                'content' => 'https://www.youtube.com',
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Official YouTube video sharing platform hosted on the verified YouTube / Google infrastructure.',
            ],
            [
                'type' => 'URL / Web Phishing',
                'content' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Legitimate YouTube video playback link using standard query parameters (?v=).',
            ],
            [
                'type' => 'URL / Web Phishing',
                'content' => 'https://bit.ly/3x8kL',
                'is_phishing' => false,
                'indicator' => 'deceptive_url',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Shortened URL via Bitly. While URL shorteners obscure destination addresses, they are frequently used for legitimate link sharing.',
            ],
            [
                'type' => 'URL / Web Phishing',
                'content' => 'https://www.facebook.com/login',
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Authentic login gateway for Facebook hosted on the official facebook.com domain with valid HTTPS.',
            ],
            [
                'type' => 'URL / Web Phishing',
                'content' => 'http://youtube-security-verify.net/account/login',
                'is_phishing' => true,
                'indicator' => 'deceptive_url',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Brand spoofing attempt using an unauthorized third-party domain (youtube-security-verify.net) to impersonate YouTube.',
            ],
            [
                'type' => 'URL / Web Phishing',
                'content' => 'https://bit.ly/bdo-login-verify-now',
                'is_phishing' => true,
                'indicator' => 'deceptive_url',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Shortened URL hiding a spoofed BDO online banking login page.',
            ],

            // Authentic Academic & Institutional Emails
            [
                'type' => 'Email Phishing',
                'content' => "Dear Students,\n\nPlease be informed that the midterm examinations will begin on November 25, 2026.\n\nStudents are advised to review their respective subject schedules and prepare the necessary examination materials.\n\nFor questions or concerns, kindly contact your department office during regular working hours.\n\nThank you.\n\nOffice of Academic Affairs",
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Authentic academic notice from the Office of Academic Affairs providing examination schedules without suspicious links or credential requests.',
            ],
            [
                'type' => 'Email Phishing',
                'content' => "Dear Students,\n\nPlease be advised that final examinations for the current semester will take place from December 15 to December 20, 2026. Ensure all clearance requirements are submitted to the registrar before the examination week.\n\nBest regards,\nOffice of the University Registrar",
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Legitimate official registrar advisory regarding semester final examination dates and clearance.',
            ],

            // Authentic Filipino SMS Communications (Tagalog & English)
            [
                'type' => 'SMS Phishing',
                'content' => "Never share your OTP 385535 or enter it on suspicious forms/sites from SMS links. Only use it in the GoTyme Bank app. If you didn't request it, call #468888.",
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Legitimate bank OTP advisory from GoTyme Bank explicitly warning users never to enter credentials on external websites.',
            ],
            [
                'type' => 'SMS Phishing',
                'content' => "Did you request to do BANK TRANSFER to BDO Unibank, Inc. account no. ending in 6177 with amount of PHP 1600.00? If not, DON'T ENTER YOUR OTP ON ANY SITE OR SEND IT TO ANYONE because IT'S A SCAM! If you requested, your OTP is 672482.",
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Authentic transactional advisory and anti-scam warning sent by GCash for a bank transfer.',
            ],
            [
                'type' => 'SMS Phishing',
                'content' => "May promo pa rin? Sulitin mo na 'yan bago pa mag-expire! Expired na ang promo? Load your fave TIKTOK SAYA 50 with just a tap sa Smart App o sa suking tindahan!",
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Authentic telco promotional reminder in conversational Filipino directing users to the official Smart App or local store.',
            ],
            [
                'type' => 'SMS Phishing',
                'content' => "Gusto naming ready ka kausapin ang Tropa! You get total of 17 GB and Unli Allnet Calls & Texts! Load via Smart App o *123#",
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Legitimate telecom load promotion from TNT using the official USSD shortcode (*123#) and verified mobile app.',
            ],
            [
                'type' => 'SMS Phishing',
                'content' => "ALERT: Your BDO Online account has been deactivated due to suspicious transactions. Reactivate immediately to prevent penalty: https://bdo-online-reactivate.xyz/login",
                'is_phishing' => true,
                'indicator' => 'urgency',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Smishing lure using threatening language and a suspicious .xyz domain targeting Filipino banking customers.',
            ],
            [
                'type' => 'SMS Phishing',
                'content' => "GCASH ADVISORY: Your account was flagged for unauthorized cash out of PHP 5,000. Verify your identity now: http://gcash-security-recovery.top",
                'is_phishing' => true,
                'indicator' => 'urgency',
                'is_diagnostic' => false,
                'status' => 'approved',
                'explanation' => 'Spoofed GCash SMS creating artificial panic and linking to an unofficial .top domain.',
            ],
        ];

        foreach ($coreScenarios as $scen) {
            $existing = Scenario::where('content', $scen['content'])->first();
            if (!$existing) {
                Scenario::create($scen);
            }
        }

        // 2. Batch import samples from tagalog-sms.csv
        $tagalogCsv = base_path('ml_service/tagalog-sms.csv');
        if (file_exists($tagalogCsv)) {
            $imported = 0;
            if (($handle = fopen($tagalogCsv, 'r')) !== false) {
                $header = fgetcsv($handle);
                $textCol = false;
                if ($header) {
                    foreach ($header as $idx => $colName) {
                        $c = strtolower(trim($colName));
                        if (in_array($c, ['input_text', 'text'])) {
                            $textCol = $idx;
                            break;
                        }
                    }
                }

                while (($row = fgetcsv($handle)) !== false && $imported < 40) {
                    $text = $textCol !== false && isset($row[$textCol]) ? trim($row[$textCol]) : '';
                    if (strlen($text) < 15) continue;

                    $exists = Scenario::where('content', $text)->first();
                    if (!$exists) {
                        Scenario::create([
                            'type' => 'SMS Phishing',
                            'content' => $text,
                            'is_phishing' => false,
                            'indicator' => 'authority',
                            'is_diagnostic' => false,
                            'status' => 'approved',
                            'explanation' => 'Authentic Filipino SMS communication (transactional notification, advisory, or official promo).',
                        ]);
                        $imported++;
                    }
                }
                fclose($handle);
            }
        }

        // 3. Batch import samples from PhishMind_URL_dict.csv
        $urlCsv = base_path('ml_service/PhishMind_URL_dict.csv');
        if (file_exists($urlCsv)) {
            $imported = 0;
            if (($handle = fopen($urlCsv, 'r')) !== false) {
                $header = fgetcsv($handle);
                $textCol = 2; // input_text
                $labelCol = 3; // label

                while (($row = fgetcsv($handle)) !== false && $imported < 40) {
                    $u = isset($row[$textCol]) ? trim($row[$textCol]) : '';
                    $lbl = isset($row[$labelCol]) ? trim($row[$labelCol]) : '0';
                    if (strlen($u) < 8) continue;

                    $isPhish = ($lbl === '1' || strtolower($lbl) === 'phishing');
                    $exists = Scenario::where('content', $u)->first();
                    if (!$exists) {
                        Scenario::create([
                            'type' => 'URL / Web Phishing',
                            'content' => $u,
                            'is_phishing' => $isPhish,
                            'indicator' => $isPhish ? 'deceptive_url' : 'authority',
                            'is_diagnostic' => false,
                            'status' => 'approved',
                            'explanation' => $isPhish ? 'Phishing URL from the curated PhishMind dictionary.' : 'Verified authentic web address.',
                        ]);
                        $imported++;
                    }
                }
                fclose($handle);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe to keep scenarios
    }
};
