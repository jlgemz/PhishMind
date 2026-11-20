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
        }
    }
}