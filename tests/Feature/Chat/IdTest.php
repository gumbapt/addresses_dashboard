<?php

namespace Tests\Feature\Chat;

use App\Models\Admin;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_and_admin_have_different_ids()
    {
        // Cria usuários e admin
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $admin = Admin::factory()->create();
        
        $this->assertNotEquals($user1->id, $admin->id);
        $this->assertNotEquals($user2->id, $admin->id);
        $this->assertNotEquals($user1->id, $user2->id);
        
        \Illuminate\Support\Facades\Log::info('User1 ID: ' . $user1->id);
        \Illuminate\Support\Facades\Log::info('User2 ID: ' . $user2->id);
        \Illuminate\Support\Facades\Log::info('Admin ID: ' . $admin->id);
    }

    public function test_can_add_user_and_admin_to_same_chat()
    {
        // Cria usuários e admin
        $user = User::factory()->create();
        $admin = Admin::factory()->create();
        
        // Cria chat
        $chat = Chat::create([
            'name' => 'Chat Teste',
            'type' => 'private',
            'created_by' => $user->id
        ]);
        
        // Adiciona participantes ao chat (mesmo que tenham o mesmo ID)
        \Illuminate\Support\Facades\DB::table('chat_user')->insert([
            [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'user_type' => 'user',
                'joined_at' => now(),
                'is_active' => true
            ],
            [
                'chat_id' => $chat->id,
                'user_id' => $admin->id,
                'user_type' => 'admin',
                'joined_at' => now(),
                'is_active' => true
            ]
        ]);
        
        // Verifica se ambos foram adicionados
        $participants = \Illuminate\Support\Facades\DB::table('chat_user')
            ->where('chat_id', $chat->id)
            ->get();
            
        $this->assertCount(2, $participants);
        
        // Verifica se os tipos estão corretos
        $userParticipant = $participants->where('user_type', 'user')->first();
        $adminParticipant = $participants->where('user_type', 'admin')->first();
        
        $this->assertNotNull($userParticipant);
        $this->assertNotNull($adminParticipant);
    }
} 