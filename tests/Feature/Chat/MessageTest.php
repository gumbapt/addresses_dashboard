<?php

namespace Tests\Feature\Chat;

use App\Models\Admin;
use App\Models\Chat;
use App\Models\ChatUser;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected $user1;
    protected $user2;
    protected $admin;
    protected $chat;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->admin = Admin::factory()->create();
        
        Log::info('User1 ID: ' . $this->user1->id);
        Log::info('User2 ID: ' . $this->user2->id);
        Log::info('Admin ID: ' . $this->admin->id);
        
        // Cria chat privado
        $this->chat = Chat::create([
            'name' => 'Chat Teste',
            'type' => 'private',
            'description' => 'Chat de teste',
            'created_by' => $this->user1->id
        ]);
        
        ChatUser::insert([ 
            [   
                'chat_id' => $this->chat->id,
                'user_id' => $this->user1->id,
                'user_type' => 'user',
                'joined_at' => now(),
                'is_active' => true
            ],
            [
                'chat_id' => $this->chat->id,
                'user_id' => $this->admin->id,
                'user_type' => 'admin',
                'joined_at' => now(),
                'is_active' => true
            ]
        ]);
  
    }

    public function test_user_and_admin_have_different_ids()
    {
        // Cria usuários e admin em um teste isolado
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $admin = Admin::factory()->create();
        
        $this->assertNotEquals($user1->id, $admin->id);
        $this->assertNotEquals($user2->id, $admin->id);
        $this->assertNotEquals($user1->id, $user2->id);
        
        Log::info('User1 ID: ' . $user1->id);
        Log::info('User2 ID: ' . $user2->id);
        Log::info('Admin ID: ' . $admin->id);
    }

    public function test_admin_is_participant_of_chat()
    {
        // Verifica se o admin está na tabela chat_user
        $adminParticipant = DB::table('chat_user')
            ->where('chat_id', $this->chat->id)
            ->where('user_id', $this->admin->id)
            ->first();

        $this->assertNotNull($adminParticipant);
        $this->assertEquals('admin', $adminParticipant->user_type);
        $this->assertTrue($adminParticipant->is_active);
    }

    public function test_user_can_send_message_to_chat()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/chat/{$this->chat->id}/send", [
                'content' => 'Olá, esta é uma mensagem de teste!',
                'message_type' => 'text'
            ]);
        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'chat_id',
                    'status',
                    'message_type'
                ]
            ]);
        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'content' => 'Olá, esta é uma mensagem de teste!',
            'sender_id' => $this->user1->id,
            'message_type' => 'text',
            'is_read' => false
        ]);
    }

    public function test_admin_can_send_message_to_chat()
    {
        $token = $this->admin->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/chat/{$this->chat->id}/send", [
                'content' => 'Resposta do admin!',
                'message_type' => 'text'
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'content' => 'Resposta do admin!',
            'sender_id' => $this->admin->id,
            'message_type' => 'text',
            'is_read' => false
        ]);
    }

    public function test_user_cannot_send_message_to_chat_they_are_not_part_of()
    {
        $token = $this->user2->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/chat/{$this->chat->id}/send", [
                'content' => 'Mensagem de usuário não participante',
                'message_type' => 'text'
            ]);

        $response->assertStatus(403);
    }

    public function test_can_get_chat_messages()
    {
        // Cria algumas mensagens
        Message::create([
            'chat_id' => $this->chat->id,
            'content' => 'Primeira mensagem',
            'sender_id' => $this->user1->id,
            'message_type' => 'text',
            'is_read' => false
        ]);

        Message::create([
            'chat_id' => $this->chat->id,
            'content' => 'Segunda mensagem',
            'sender_id' => $this->admin->id,
            'message_type' => 'text',
            'is_read' => false
        ]);

        $token = $this->user1->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/chat/{$this->chat->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'messages' => [
                        '*' => [
                            'id',
                            'chat_id',
                            'content',
                            'sender_id',
                            'message_type',
                            'metadata',
                            'is_read',
                            'created_at'
                        ]
                    ],
                    'from_cache',
                    'pagination'
                ]
            ]);

        $this->assertCount(2, $response->json('data.messages'));
    }

    public function test_can_mark_messages_as_read()
    {
        // Cria mensagens não lidas
        Message::create([
            'chat_id' => $this->chat->id,
            'content' => 'Mensagem não lida 1',
            'sender_id' => $this->admin->id,
            'message_type' => 'text',
            'is_read' => false
        ]);

        Message::create([
            'chat_id' => $this->chat->id,
            'content' => 'Mensagem não lida 2',
            'sender_id' => $this->admin->id,
            'message_type' => 'text',
            'is_read' => false
        ]);

        $token = $this->user1->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/chat/{$this->chat->id}/read");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['updated_count']
            ]);

        $this->assertEquals(2, $response->json('data.updated_count'));

        // Verifica se as mensagens foram marcadas como lidas
        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'content' => 'Mensagem não lida 1',
            'is_read' => true
        ]);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'content' => 'Mensagem não lida 2',
            'is_read' => true
        ]);
    }

    public function test_can_get_unread_count()
    {
        // Cria mensagens não lidas
        Message::create([
            'chat_id' => $this->chat->id,
            'content' => 'Mensagem não lida 1',
            'sender_id' => $this->admin->id,
            'message_type' => 'text',
            'is_read' => false
        ]);

        Message::create([
            'chat_id' => $this->chat->id,
            'content' => 'Mensagem não lida 2',
            'sender_id' => $this->admin->id,
            'message_type' => 'text',
            'is_read' => false
        ]);

        $token = $this->user1->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/chat/{$this->chat->id}/unread-count");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['unread_count']
            ]);

        $this->assertEquals(2, $response->json('data.unread_count'));
    }

    public function test_can_send_message_with_metadata()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        
        $metadata = [
            'file_url' => 'https://example.com/image.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg'
        ];
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/chat/{$this->chat->id}/send", [
                'content' => 'Mensagem com metadados',
                'message_type' => 'image',
                'metadata' => $metadata
            ]);

        $response->assertStatus(201);

        // Verifica se a mensagem foi criada (sem verificar o formato exato do JSON)
        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'content' => 'Mensagem com metadados',
            'sender_id' => $this->user1->id,
            'message_type' => 'image'
        ]);

        // Verifica se o metadata foi salvo (verificando apenas se existe)
        $message = Message::where('chat_id', $this->chat->id)
            ->where('content', 'Mensagem com metadados')
            ->first();
        
        $this->assertNotNull($message);
        $this->assertNotNull($message->metadata);
        $this->assertEquals($metadata, $message->metadata);
    }

    public function test_sender_type_is_derived_from_chat_user_table()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/chat/{$this->chat->id}/send", [
                'content' => 'Teste de derivação do tipo',
                'message_type' => 'text'
            ]);

        $response->assertStatus(201);

        $messageData = $response->json('data.message');
        
        // Verifica que sender_type está presente na resposta (derivado)
        $this->assertArrayHasKey('sender_type', $messageData);
        
        // Verifica que o tipo está correto baseado na tabela chat_user
        $userType = \Illuminate\Support\Facades\DB::table('chat_user')
            ->where('chat_id', $this->chat->id)
            ->where('user_id', $this->user1->id)
            ->value('user_type');
            
        $this->assertEquals($userType, $messageData['sender_type']);
    }

    public function test_validation_requires_content()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/chat/{$this->chat->id}/send", [
                'message_type' => 'text'
            ]);

        $response->assertStatus(422);
    }

    public function test_validation_accepts_valid_message_types()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        
        $validTypes = ['text', 'image', 'file'];
        
        foreach ($validTypes as $type) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson("/api/chat/{$this->chat->id}/send", [
                    'content' => "Mensagem do tipo {$type}",
                    'message_type' => $type
                ]);

            $response->assertStatus(201);
        }
    }

    public function test_validation_rejects_invalid_message_types()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/chat/{$this->chat->id}/send", [
                'content' => 'Mensagem com tipo inválido',
                'message_type' => 'invalid_type'
            ]);

        $response->assertStatus(422);
    }
} 