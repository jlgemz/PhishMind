<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        // Create a Student User
        User::firstOrCreate(
            ['email' => 'maya@university.edu'],
            ['name' => 'Maya Santos', 'password' => bcrypt('password'), 'is_verified' => true, 'is_admin' => false]
        );
        
        // Create an Admin User
        User::firstOrCreate(
            ['email' => 'admin@phishmind.edu'],
            ['name' => 'Admin Researcher', 'password' => bcrypt('password'), 'is_verified' => true, 'is_admin' => true]
        );

        if (\App\Models\Scenario::count() === 0) {
            $this->call([ScenarioSeeder::class]);
        } else {
            // Ensure every modality has active scenarios in the database
            if (\App\Models\Scenario::where('type', 'Email Phishing')->count() === 0) {
                \App\Models\Scenario::create([
                    'type' => 'Email Phishing',
                    'content' => "Subject: URGENT: Campus IT Security Notice - Re-verify Your Account\n\nDear Student,\n\nOur system detected multiple unauthorized login attempts to your student email account. To prevent immediate account suspension, you must re-verify your identity and security settings within 24 hours at the following university portal:\n\nhttps://portal-university-auth.com/verify-session\n\nFailure to verify will result in permanent revocation of your campus single sign-on access.\n\nCampus IT Services & Information Security",
                    'is_phishing' => true,
                    'indicator' => 'urgency',
                    'is_diagnostic' => true,
                    'status' => 'approved',
                    'explanation' => 'Uses severe artificial urgency (suspension within 24 hours) and directs users to an external unauthorized domain (portal-university-auth.com) instead of the official institution portal.',
                ]);
                \App\Models\Scenario::create([
                    'type' => 'Email Phishing',
                    'content' => "Subject: University Library Extended Hours for Finals Week\n\nDear Campus Community,\n\nThe University Main Library will be extending study hours starting next Monday to assist students during final examinations. The library will remain open 24 hours a day through Friday.\n\nQuiet study rooms and computer labs will be available on floors 2 through 4. For full facility guidelines and room booking, please visit the official university portal at https://library.university.edu.\n\nBest regards,\nUniversity Library Services",
                    'is_phishing' => false,
                    'indicator' => 'authority',
                    'is_diagnostic' => true,
                    'status' => 'approved',
                    'explanation' => 'Legitimate informational notice from university library with official .edu institutional domain and no credential harvesting or coercive pressure.',
                ]);
            }
        }
    }
}