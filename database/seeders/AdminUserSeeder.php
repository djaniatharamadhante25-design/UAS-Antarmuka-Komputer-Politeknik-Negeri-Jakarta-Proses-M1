<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Akun admin awal Otaku Track (sama seperti repo asli):
     * username `admin` / password `Admin123!`.
     * Tidak dibuat bila username tersebut sudah ada.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@otakutrack.local',
                'password_hash' => Hash::make('Admin123!'),
                'role' => 'admin',
            ]
        );
    }
}
