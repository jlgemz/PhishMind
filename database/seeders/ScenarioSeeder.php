<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Scenario;
use Illuminate\Support\Facades\DB;

class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Scenario::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info("🗑️  Cleared existing scenarios.");

        $this->seedUrl();
        $this->seedEmail();
        $this->seedSms();

        // Core fallback scenarios
        Scenario::create([
            'type' => 'URL / Web Phishing',
            'content' => 'https://www.youtube.com',
            'is_phishing' => false,
            'indicator' => 'authority',
            'is_diagnostic' => false,
            'status' => 'approved',
            'explanation' => 'Official YouTube video sharing platform on verified Google infrastructure.',
        ]);

        Scenario::create([
            'type' => 'URL / Web Phishing',
            'content' => 'https://bit.ly/3x8kL',
            'is_phishing' => false,
            'indicator' => 'deceptive_url',
            'is_diagnostic' => false,
            'status' => 'approved',
            'explanation' => 'Legitimate shortened link via Bitly.',
        ]);

        Scenario::create([
            'type' => 'SMS Phishing',
            'content' => "Never share your OTP 385535 or enter it on suspicious forms/sites from SMS links. Only use it in the GoTyme Bank app. If you didn't request it, call #468888.",
            'is_phishing' => false,
            'indicator' => 'authority',
            'is_diagnostic' => false,
            'status' => 'approved',
            'explanation' => 'Official GoTyme Bank transactional security warning and OTP advisory.',
        ]);

        Scenario::create([
            'type' => 'SMS Phishing',
            'content' => 'ALERT: Your bank account has been suspended. Verify your identity now: https://secure-bank.xyz/verify',
            'is_phishing' => true,
            'indicator' => 'urgency',
            'is_diagnostic' => false,
            'status' => 'approved',
            'explanation' => 'Bank impersonation with artificial urgency is a common smishing attack.',
        ]);

        $this->command->info("🎉 Seeder completed!");
    }

    private function seedUrl(): void
    {
        $path = file_exists(base_path('ml_service/PhishMind_URL_dict.csv'))
            ? base_path('ml_service/PhishMind_URL_dict.csv')
            : base_path('ml_service/url_dataset.csv');

        $count = 0;
        if (!file_exists($path)) {
            $this->command->error("❌ File not found: $path");
            return;
        }

        $file = fopen($path, 'r');
        $firstLine = fgets($file);
        rewind($file);
        $delimiter = str_contains($firstLine, "\t") ? "\t" : ",";

        $headers = fgetcsv($file, 0, $delimiter, '"');
        $urlIdx = null;
        $labelIdx = null;

        if ($headers) {
            foreach ($headers as $i => $col) {
                $col = strtolower(trim($col, " \"'\xEF\xBB\xBF"));
                if (in_array($col, ['url', 'input_text', 'text'])) $urlIdx = $i;
                if (in_array($col, ['label', 'final_label', 'class'])) $labelIdx = $i;
            }
        }

        if ($urlIdx !== null && $labelIdx !== null) {
            while (($row = fgetcsv($file, 0, $delimiter, '"')) !== false && $count < 300) {
                if (!isset($row[$urlIdx]) || !isset($row[$labelIdx])) continue;

                $content = trim($row[$urlIdx]);
                $label = strtolower(trim($row[$labelIdx]));
                if (empty($content) || strlen($content) < 5) continue;

                if (str_contains($label, 'phishing') || $label === '1') {
                    $isPhishing = true;
                } elseif (str_contains($label, 'legitimate') || $label === '0' || str_contains($label, 'benign')) {
                    $isPhishing = false;
                } else {
                    continue;
                }

                Scenario::create([
                    'type' => 'URL / Web Phishing',
                    'content' => $content,
                    'is_phishing' => $isPhishing,
                    'indicator' => $isPhishing ? 'deceptive_url' : 'authority',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => $isPhishing ? 'Phishing URL detected by structural feature analysis.' : 'Legitimate web address on verified domain infrastructure.',
                ]);
                $count++;
            }
        }
        fclose($file);
        $this->command->info("✅ Imported $count URL scenarios from " . basename($path));
    }

    private function seedEmail(): void
    {
        $path = file_exists(base_path('ml_service/PhishMind_Email_dict.csv'))
            ? base_path('ml_service/PhishMind_Email_dict.csv')
            : base_path('ml_service/email_dataset.csv');

        $count = 0;
        if (!file_exists($path)) {
            $this->command->error("❌ File not found: $path");
            return;
        }

        $file = fopen($path, 'r');
        $firstLine = fgets($file);
        rewind($file);
        $delimiter = str_contains($firstLine, "\t") ? "\t" : ",";

        $headers = fgetcsv($file, 0, $delimiter, '"');
        $textIdx = null;
        $labelIdx = null;

        if ($headers) {
            foreach ($headers as $i => $col) {
                $col = strtolower(trim($col, " \"'\xEF\xBB\xBF"));
                if (in_array($col, ['input_text', 'cleaned_message', 'original_message', 'text', 'email text', 'body', 'content', 'message', 'subject'])) {
                    $textIdx = $i;
                }
                if (in_array($col, ['final_label', 'verified_label', 'original_label', 'label', 'email type', 'class', 'target'])) {
                    $labelIdx = $i;
                }
            }
        }

        if ($textIdx !== null && $labelIdx !== null) {
            while (($row = fgetcsv($file, 0, $delimiter, '"')) !== false && $count < 250) {
                if (!isset($row[$textIdx]) || !isset($row[$labelIdx])) continue;

                $content = trim($row[$textIdx]);
                $label = strtolower(trim($row[$labelIdx]));
                if (empty($content) || strlen($content) < 15) continue;

                if ($label === 'phishing' || $label === '1' || str_contains($label, 'malicious')) {
                    $isPhishing = true;
                } elseif ($label === 'legitimate' || $label === '0' || str_contains($label, 'benign') || str_contains($label, 'ham')) {
                    $isPhishing = false;
                } else {
                    continue;
                }

                Scenario::create([
                    'type' => 'Email Phishing',
                    'content' => $content,
                    'is_phishing' => $isPhishing,
                    'indicator' => $isPhishing ? 'urgency' : 'authority',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => $isPhishing ? 'Suspicious email displaying artificial urgency, credentials request, or domain mismatch.' : 'Authentic email correspondence without deceptive signatures.',
                ]);
                $count++;
            }
        }
        fclose($file);
        $this->command->info("✅ Imported $count Email scenarios from " . basename($path));
    }

    private function seedSms(): void
    {
        $count = 0;

        // 1. Seed from tagalog-sms.csv (Authentic Filipino messages)
        $tagalogPath = base_path('ml_service/tagalog-sms.csv');
        if (file_exists($tagalogPath)) {
            $file = fopen($tagalogPath, 'r');
            $header = fgetcsv($file);
            $textCol = 2;
            if ($header) {
                foreach ($header as $i => $col) {
                    if (in_array(strtolower(trim($col)), ['input_text', 'text'])) {
                        $textCol = $i;
                        break;
                    }
                }
            }

            $tagalogCount = 0;
            while (($row = fgetcsv($file)) !== false && $tagalogCount < 100) {
                $text = isset($row[$textCol]) ? trim($row[$textCol]) : '';
                if (strlen($text) < 15) continue;

                Scenario::create([
                    'type' => 'SMS Phishing',
                    'content' => $text,
                    'is_phishing' => false,
                    'indicator' => 'authority',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Authentic Filipino SMS notification, advisory, or official promo message.',
                ]);
                $tagalogCount++;
                $count++;
            }
            fclose($file);
            $this->command->info("✅ Imported $tagalogCount legitimate Filipino SMS scenarios from tagalog-sms.csv");
        }

        // 2. Seed from PhishMind_SMS_dict.csv
        $smsDictPath = base_path('ml_service/PhishMind_SMS_dict.csv');
        if (file_exists($smsDictPath)) {
            $file = fopen($smsDictPath, 'r');
            $header = fgetcsv($file);
            $textCol = 2; // input_text
            $labelCol = 3; // label
            $smsCount = 0;

            while (($row = fgetcsv($file)) !== false && $smsCount < 150) {
                $text = isset($row[$textCol]) ? trim($row[$textCol]) : '';
                $lbl = isset($row[$labelCol]) ? trim($row[$labelCol]) : '0';
                if (strlen($text) < 10) continue;

                $isPhishing = ($lbl === '1' || strtolower($lbl) === 'phishing');

                Scenario::create([
                    'type' => 'SMS Phishing',
                    'content' => $text,
                    'is_phishing' => $isPhishing,
                    'indicator' => $isPhishing ? 'urgency' : 'authority',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => $isPhishing ? 'Smishing lure using urgency or malicious links.' : 'Authentic SMS text message.',
                ]);
                $smsCount++;
                $count++;
            }
            fclose($file);
            $this->command->info("✅ Imported $smsCount SMS scenarios from PhishMind_SMS_dict.csv");
        }
    }
}
