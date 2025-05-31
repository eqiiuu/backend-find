<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admins;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Create default admin user if it doesn't exist
        if (!Admins::where('username', 'Admin')->exists()) {
            Admins::create([
                'username' => 'Admin',
                'email' => 'admin@example.com',
                'name' => 'Super Admin',
                'password' => Hash::make('rifat123'),
                'user_id' => 'usr_' . Str::random(10),
                'is_super_admin' => true,
                'is_active' => true
            ]);
        }

        // Call the UserSeeder
        $this->call([
            UserSeeder::class,
        ]);
    }
}
