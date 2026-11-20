<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Scenario;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existingCount = Scenario::where('type', 'Email Phishing')->count();

        // If there are fewer than 5 approved email scenarios, seed high-quality curated email scenarios
        if ($existingCount < 5) {
            $scenarios = [
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
                [
                    'type' => 'Email Phishing',
                    'content' => "Subject: Prof. Daniel Vance shared a document with you: 'Final_Exam_Study_Guide.docx'\n\nProf. Daniel Vance has invited you to view and collaborate on the following file in Microsoft OneDrive:\n\n[ Final_Exam_Study_Guide.docx - 4.2 MB ]\n\nClick the link below to access your institutional document repository:\nhttps://onedrive-secure-viewer.org/login?doc=studyguide\n\nThis link will expire in 48 hours.",
                    'is_phishing' => true,
                    'indicator' => 'authority',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Leverages professor authority and exam anxiety to lure students into typing credentials on an external spoofed domain (onedrive-secure-viewer.org).',
                ],
                [
                    'type' => 'Email Phishing',
                    'content' => "Subject: Congratulations: Spring Term Financial Aid & Grant Adjustment\n\nDear Student,\n\nYour application for the 2026 Emergency Student Relief Grant has been approved for the amount of $2,400.00.\n\nTo accept these funds and arrange direct wire delivery to your bank account, please complete the grant confirmation form by 5:00 PM today:\nhttps://university-grants-relief.com/claim-aid\n\nStudent Financial Services",
                    'is_phishing' => true,
                    'indicator' => 'urgency',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Appeals to financial reward with a tight deadline, routing students to an external domain designed to harvest banking information.',
                ],
                [
                    'type' => 'Email Phishing',
                    'content' => "Subject: DHL Express: Parcel Delivery Failed - Action Needed\n\nTracking Number: DHL-8921-99214-US\n\nYour incoming international package cannot be delivered due to an incorrect campus building address and unpaid customs fees ($2.75).\n\nPlease update your shipping address and pay the fee online within 48 hours to avoid package return:\nhttp://dhl-express-tracking-delivery.info/pay\n\nDHL Customer Support",
                    'is_phishing' => true,
                    'indicator' => 'deceptive_url',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Phishing delivery lure with fake tracking number directing victims to an unofficial, insecure domain.',
                ],
                [
                    'type' => 'Email Phishing',
                    'content' => "Subject: Password Expiring Notification: Action Required\n\nDear User,\n\nYour campus network and email account password will expire today in 2 hours. If you wish to keep your current password, please confirm your active credentials below:\n\nhttp://account-keep-password.com/sso\n\nIf no action is taken, your mailbox will be locked and all incoming emails will be returned to sender.\n\nIT System Administrator",
                    'is_phishing' => true,
                    'indicator' => 'urgency',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Classic password expiration phishing lure creating extreme time anxiety (2 hours) and linking to a fake SSO domain.',
                ],
                [
                    'type' => 'Email Phishing',
                    'content' => "Subject: University Library Extended Hours for Finals Week\n\nDear Campus Community,\n\nThe University Main Library will be extending study hours starting next Monday to assist students during final examinations. The library will remain open 24 hours a day through Friday.\n\nQuiet study rooms and computer labs will be available on floors 2 through 4. For full facility guidelines and room booking, please visit the official university portal at https://library.university.edu.\n\nBest regards,\nUniversity Library Services",
                    'is_phishing' => false,
                    'indicator' => 'authority',
                    'is_diagnostic' => true,
                    'status' => 'approved',
                    'explanation' => 'Legitimate informational notice from university library with official .edu institutional domain and no credential harvesting or coercive pressure.',
                ],
                [
                    'type' => 'Email Phishing',
                    'content' => "Subject: Notice: Spring Term Course Registration Dates & Advising\n\nDear Student,\n\nPriority course enrollment for the upcoming spring term begins on November 28. Your specific enrollment appointment window is visible in your student center dashboard.\n\nPlease schedule an appointment with your academic advisor before registering. To view course schedules and degree requirements, log in to your student portal at https://portal.university.edu.\n\nOffice of the University Registrar",
                    'is_phishing' => false,
                    'indicator' => 'authority',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Standard administrative notification pointing students directly to the official university portal without suspicious links or panic tactics.',
                ],
                [
                    'type' => 'Email Phishing',
                    'content' => "Subject: Scheduled Campus Network Maintenance this Saturday\n\nHello Campus Community,\n\nThe Information Technology department will perform routine scheduled maintenance on the campus core network this Saturday, November 28, from 2:00 AM to 6:00 AM.\n\nDuring this maintenance window, access to on-campus Wi-Fi and the learning management system may experience brief intermittent outages. No action is required on your part.\n\nQuestions? Contact the IT Service Desk at helpdesk@university.edu.\n\nUniversity IT Services",
                    'is_phishing' => false,
                    'indicator' => 'urgency',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Routine maintenance announcement. It asks for zero action or credentials and provides official contact information.',
                ],
                [
                    'type' => 'Email Phishing',
                    'content' => "Subject: Join the Annual Campus Sustainability Clean-up This Weekend!\n\nHi everyone!\n\nThe Student Government Association is hosting our annual Campus Green Initiative clean-up this Saturday at 10:00 AM outside the Student Union.\n\nFree t-shirts and lunch will be provided to all student volunteers. Come make a positive impact on our university grounds!\n\nSign up on our student organization page at https://studentlife.university.edu/cleanup.\n\nStudent Government Association",
                    'is_phishing' => false,
                    'indicator' => 'authority',
                    'is_diagnostic' => false,
                    'status' => 'approved',
                    'explanation' => 'Authentic student organization volunteer invitation linking to official institutional subdomains with no threats or deceptive requests.',
                ],
            ];

            foreach ($scenarios as $scenarioData) {
                Scenario::create($scenarioData);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive rollback needed for seeded scenarios
    }
};
