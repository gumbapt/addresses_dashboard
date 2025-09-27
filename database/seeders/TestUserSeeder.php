<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuário de teste para frontend
        User::create([
            'name' => 'Usuário Teste',
            'email' => 'user@email.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Criar alguns usuários adicionais para testes
        User::create([
            'name' => 'João Silva',
            'email' => 'joao@email.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@email.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Usuários de teste criados com sucesso!');
        $this->command->info('📧 Email: user@email.com | Senha: password123');
        $this->command->info('📧 Email: joao@email.com | Senha: password123');
        $this->command->info('📧 Email: maria@email.com | Senha: password123');
    }
} 