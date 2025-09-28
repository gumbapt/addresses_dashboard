<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First create the Super Admin
        $this->call(SudoAdminSeeder::class);
        
        // Secondary admin
        Admin::create([
            'name' => 'Secondary Admin',
            'email' => 'admin2@dashboard.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'is_super_admin' => false,
        ]);
    }
}
