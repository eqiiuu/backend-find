<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'alul',
                'username' => 'alul',
                'email' => 'alul@mail.com',
                'nomor_telepon' => '081234567890',
                'password' => Hash::make('qwerty123'),
            ],
            [
                'name' => 'ulil',
                'username' => 'ulil',
                'email' => 'ulil@mail.com',
                'nomor_telepon' => '081234567891',
                'password' => Hash::make('qwerty123'),
            ],
            [
                'name' => 'qadafi',
                'username' => 'qadafi',
                'email' => 'qadafi@mail.com',
                'nomor_telepon' => '081234567892',
                'password' => Hash::make('qwerty123'),
            ],
            [
                'name' => 'eqi',
                'username' => 'eqi',
                'email' => 'eqi@mail.com',
                'nomor_telepon' => '081234567893',
                'password' => Hash::make('qwerty123'),
            ],
            [
                'name' => 'rifat',
                'username' => 'rifat',
                'email' => 'rifat@mail.com',
                'nomor_telepon' => '081234567894',
                'password' => Hash::make('qwerty123'),
            ],
        ];

        foreach ($users as $userData) {
            try {
                $user = User::create($userData);
                Log::info('User created successfully', ['user' => $user->toArray()]);
            } catch (\Exception $e) {
                Log::error('Failed to create user', [
                    'user_data' => $userData,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }
} 