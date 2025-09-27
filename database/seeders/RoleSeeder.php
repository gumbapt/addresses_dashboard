<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        

        Role::create([
            'slug' => 'admin',
            'name' => 'Administrador',
            'description' => 'Administrador do sistema',
        ]);

        Role::create([
            'slug' => 'user',
            'name' => 'Usuário',
            'description' => 'Usuário comum',
        ]);
    }
}
