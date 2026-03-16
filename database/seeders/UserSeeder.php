<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function up(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'chingiz@admin.com'],
            [
                'name' => 'Chingiz',
                'password' => Hash::make('!d3hD\_714<s'),
            ]
        );
    }

    public function run(): void
    {
        $this->up();
    }
}
