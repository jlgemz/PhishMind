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

        // Fallback SMS scenarios (in case CSV is missing)
        Scenario::create([
            'type' => 'SMS Phishing',
            'content' => 'You have won a $500 gift card! Click here to claim: bit.ly/abc123',
            'is_phishing' => true,
            'indicator' => 'urgency',
            'is_diagnostic' => false,
            'status' => 'approved',
            'explanation' => 'Unsolicited prize with shortened URL is a classic smishing lure.',
        ]);

        Scenario::create([
            'type' => 'SMS Phishing',
            'content' => 'Your package delivery is pending. Provide your details: https://delivery-track.xyz/confirm',
            'is_phishing' => true,
            'indicator' => 'urgency',
            'is_diagnostic' => false,
            'status' => 'approved',
            'explanation' => 'Fake delivery notices use urgency and lookalike domains.',
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

        $this->command->info("✅ Added 3 fallback SMS scenarios.");
        $this->command->info("🎉 Seeder completed!");
    }

    private function seedUrl(): void
    {
        $path = base_path('ml_service/url_dataset.csv');
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
                if ($col === 'url') $urlIdx = $i;
                if ($col === 'label') $labelIdx = $i;
            }
        }

        if ($urlIdx !== null && $labelIdx !== null) {
            while (($row = fgetcsv($file, 0, $delimiter, '"')) !== false) {
                if (!isset($row[$urlIdx]) || !isset($row[$labelIdx])) continue;

                $content = trim($row[$urlIdx]);
                $label = strtolower(trim($row[$labelIdx]));
                if (empty($content)) continue;

                if (str_contains($label, 'phishing') || $label === '1') {
                    $isPhishing = true;
                } elseif (str_contains($label, 'legitimate') || $label === '0') {
                    $isPhishing = false;
                } else {
                    continue;
                }

                Scenario::create([
                    'type' => 'URL / Web Phishing',
                    'content' => $content,
                    'is_phishing' => $isPhishing,
                    'indicator' => 'deceptive_url',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Analyzed by the Random Forest model based on structural URL features.',
                ]);
                $count++;
            }
        }
        fclose($file);
        $this->command->info("✅ Imported $count URL scenarios (indicator: deceptive_url).");
    }

    private function seedEmail(): void
    {
        $path = base_path('ml_service/email_dataset.csv');
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
                if (in_array($col, ['text', 'email text', 'body', 'content', 'message'])) {
                    $textIdx = $i;
                }
                if (in_array($col, ['label', 'email type', 'class', 'target'])) {
                    $labelIdx = $i;
                }
            }
        }

        if ($textIdx !== null && $labelIdx !== null) {
            while (($row = fgetcsv($file, 0, $delimiter, '"')) !== false) {
                if (!isset($row[$textIdx]) || !isset($row[$labelIdx])) continue;

                $content = trim($row[$textIdx]);
                $label = strtolower(trim($row[$labelIdx]));
                if (empty($content)) continue;

                if ($label === 'phishing' || $label === '1' || str_contains($label, 'malicious')) {
                    $isPhishing = true;
                } elseif ($label === 'legitimate' || $label === '0' || str_contains($label, 'benign')) {
                    $isPhishing = false;
                } else {
                    continue;
                }

                Scenario::create([
                    'type' => 'Email Phishing',
                    'content' => $content,
                    'is_phishing' => $isPhishing,
                    'indicator' => 'urgency',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Analyzed for urgency keywords, sender domain, and embedded links.',
                ]);
                $count++;
            }
        }
        fclose($file);
        $this->command->info("✅ Imported $count Email scenarios (indicator: urgency).");
    }

    private function seedSms(): void
    {
        $path = base_path('ml_service/sms_dataset.csv');
        $count = 0;

        if (!file_exists($path)) {
            $this->command->warning("⚠️  File not found: $path (using fallback SMS scenarios).");
            return;
        }

        $file = fopen($path, 'r');
        $firstLine = fgets($file);
        rewind($file);
        $delimiter = str_contains($firstLine, "\t") ? "\t" : ",";

        $headers = fgetcsv($file, 0, $delimiter, '"');
        $labelIdx = null;
        $textIdx = null;

        if ($headers) {
            foreach ($headers as $i => $col) {
                $col = strtolower(trim($col, " \"'\xEF\xBB\xBF"));
                if (in_array($col, ['v1', 'label', 'class', 'target'])) $labelIdx = $i;
                if (in_array($col, ['v2', 'text', 'message', 'sms'])) $textIdx = $i;
            }
        }

        if ($labelIdx !== null && $textIdx !== null) {
            while (($row = fgetcsv($file, 0, $delimiter, '"')) !== false) {
                if (!isset($row[$textIdx]) || !isset($row[$labelIdx])) continue;

                $content = trim($row[$textIdx]);
                $label = strtolower(trim($row[$labelIdx]));
                if (empty($content)) continue;

                if (str_contains($label, 'phishing') || str_contains($label, 'spam')
                    || str_contains($label, 'smish') || $label === '1') {
                    $isPhishing = true;
                } elseif (str_contains($label, 'legitimate') || str_contains($label, 'ham')
                    || str_contains($label, 'benign') || $label === '0') {
                    $isPhishing = false;
                } else {
                    continue;
                }

                Scenario::create([
                    'type' => 'SMS Phishing',
                    'content' => $content,
                    'is_phishing' => $isPhishing,
                    'indicator' => 'urgency',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Analyzed for urgency cues, embedded links, and phone-number lures.',
                ]);
                $count++;
            }
        }
        fclose($file);
        $this->command->info("✅ Imported $count SMS scenarios (indicator: urgency).");
    }
}