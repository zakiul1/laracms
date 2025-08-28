<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Adjust email / password as you like
        $email = 'admin@gmail.com';

        $user = User::firstOrNew(['email' => $email]);
        $user->name = 'Administrator';
        $user->password = Hash::make('12345678'); // change this!
        $user->is_admin = true;                   // mark as admin
        $user->save();

        $this->command->info("Admin user ready: {$email} / password");
    }
}