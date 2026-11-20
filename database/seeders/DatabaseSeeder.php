<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        // Create a Student User
        User::create(['name' => 'Maya Santos', 'email' => 'maya@university.edu', 'password' => bcrypt('password'), 'is_verified' => true, 'is_admin' => false]);
        
        // Create an Admin User
        User::create(['name' => 'Admin Researcher', 'email' => 'admin@phishmind.edu', 'password' => bcrypt('password'), 'is_verified' => true, 'is_admin' => true]);

        $this->call([ScenarioSeeder::class]);
    }
}