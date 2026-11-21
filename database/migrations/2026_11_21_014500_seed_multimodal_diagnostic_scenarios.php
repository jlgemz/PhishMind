<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Scenario;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $diagnosticScenarios = [
            // URL Phishing
            [
                'type' => 'URL / Web Phishing',
                'content' => 'https://portal-university-auth.security-update.com/login',
                'is_phishing' => true,
                'indicator' => 'deceptive_url',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Uses a deceptive domain suffix (security-update.com) designed to impersonate the official campus portal.',
            ],
            [
                'type' => 'URL / Web Phishing',
                'content' => 'http://bank-of-america-verify.com/account/login?token=9281',
                'is_phishing' => true,
                'indicator' => 'deceptive_url',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Unencrypted HTTP banking website using an unofficial spoofed domain to harvest account credentials.',
            ],

            // URL Legitimate
            [
                'type' => 'URL / Web Phishing',
                'content' => 'https://portal.university.edu/students/academics/grades',
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Authentic student academic services portal hosted securely on the official university institutional .edu domain.',
            ],
            [
                'type' => 'URL / Web Phishing',
                'content' => 'https://library.university.edu/borrowing-and-study-spaces',
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Official campus library website providing student resources on the verified university domain.',
            ],

            // SMS Phishing
            [
                'type' => 'SMS Phishing',
                'content' => 'ALERT: Your student debit card has been suspended due to unusual activity. Verify your identity immediately: https://student-bank-verify.xyz/login',
                'is_phishing' => true,
                'indicator' => 'urgency',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Artificial panic and urgency claiming card suspension, directing recipient to a suspicious .xyz domain.',
            ],
            [
                'type' => 'SMS Phishing',
                'content' => 'FedEx Alert: Parcel tracking #FX-83921 is held at distribution hub. Update your delivery address now: http://fedx-parcel-track.top',
                'is_phishing' => true,
                'indicator' => 'urgency',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Smishing delivery lure impersonating FedEx with a lookalike domain (.top) and fake package claim.',
            ],

            // SMS Legitimate
            [
                'type' => 'SMS Phishing',
                'content' => 'CAMPUS ALERT: Severe weather advisory in effect for this afternoon. All campus activities will transition to remote operations after 3:00 PM.',
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Official campus safety broadcast. It contains no links, requests no credentials, and provides purely informational guidance.',
            ],
            [
                'type' => 'SMS Phishing',
                'content' => 'Your campus login verification code is 482910. Valid for 10 minutes. For your security, do not share this code with anyone.',
                'is_phishing' => false,
                'indicator' => 'urgency',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Authentic two-factor authentication security code with standard non-sharing security advisory.',
            ],

            // Email Phishing
            [
                'type' => 'Email Phishing',
                'content' => "Subject: URGENT: Campus IT Security Notice - Re-verify Your Account\n\nDear Student,\n\nOur system detected multiple unauthorized login attempts to your student email account. To prevent immediate account suspension, you must re-verify your identity and security settings within 24 hours at the following university portal:\n\nhttps://portal-university-auth.com/verify-session\n\nFailure to verify will result in permanent revocation of your campus single sign-on access.\n\nCampus IT Services & Information Security",
                'is_phishing' => true,
                'indicator' => 'urgency',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Uses severe artificial urgency (suspension within 24 hours) and directs users to an external unauthorized domain (portal-university-auth.com) instead of the official institution portal.',
            ],
            [
                'type' => 'Email Phishing',
                'content' => "Subject: ACTION REQUIRED: Direct Deposit Payroll Information Updated\n\nDear Campus Employee / Student Worker,\n\nWe have received a request to update your direct deposit bank account information. Your upcoming bi-weekly stipend of $1,250.00 will be deposited into the new account ending in *4821.\n\nIf you did not authorize this change, review your account details immediately and cancel the transfer:\nhttp://payroll-portal-verification.net/dispute?id=89214\n\nOffice of Human Resources and Payroll Services",
                'is_phishing' => true,
                'indicator' => 'urgency',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Classic panic-inducing financial lure prompting the recipient to click a fraudulent dispute link on a non-institutional domain.',
            ],

            // Email Legitimate
            [
                'type' => 'Email Phishing',
                'content' => "Subject: University Library Extended Hours for Finals Week\n\nDear Campus Community,\n\nThe University Main Library will be extending study hours starting next Monday to assist students during final examinations. The library will remain open 24 hours a day through Friday.\n\nQuiet study rooms and computer labs will be available on floors 2 through 4. For full facility guidelines and room booking, please visit the official university portal at https://library.university.edu.\n\nBest regards,\nUniversity Library Services",
                'is_phishing' => false,
                'indicator' => 'authority',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Legitimate informational notice from university library with official .edu institutional domain and no credential harvesting or coercive pressure.',
            ],
        ];

        foreach ($diagnosticScenarios as $scenarioData) {
            // Check if scenario content already exists
            $existing = Scenario::where('content', $scenarioData['content'])->first();
            if ($existing) {
                $existing->update([
                    'type' => $scenarioData['type'],
                    'is_phishing' => $scenarioData['is_phishing'],
                    'indicator' => $scenarioData['indicator'],
                    'is_diagnostic' => true,
                    'status' => 'approved',
                    'explanation' => $scenarioData['explanation'],
                ]);
            } else {
                Scenario::create($scenarioData);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive rollback needed
    }
};
