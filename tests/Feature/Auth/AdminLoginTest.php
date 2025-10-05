<?php

namespace Tests\Feature\Auth;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_valid_credentials()
    {
        // Arrange
        $admin = Admin::factory()->create([
            'email' => 'admin3@dashboard.com',
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => 'admin3@dashboard.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'admin' => [
                    'id',
                    'name',
                    'email',
                    'is_active',
                    'last_login_at'
                ],
                'token',
                'permissions' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description'
                    ]
                ]
            ])
            ->assertJson([
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'is_active' => true
                ]
            ]);

        // Verificar que as permissões são retornadas como array
        $this->assertIsArray($response->json('permissions'));

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_admin_cannot_login_with_invalid_email()
    {
        // Arrange
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);
    }

    public function test_admin_cannot_login_with_invalid_password()
    {
        // Arrange
        Admin::factory()->create([
            'email' => 'admin3@dashboard.com',
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => 'admin3@dashboard.com',
            'password' => 'wrongpassword'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);
    }

    public function test_inactive_admin_cannot_login()
    {
        // Arrange
        $admin = Admin::factory()->inactive()->create([
            'email' => 'admin3@dashboard.com',
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => 'admin3@dashboard.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Admin account is not active'
            ]);
    }

    public function test_login_requires_email()
    {
        // Arrange
        $loginData = [
            'password' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_password()
    {
        // Arrange
        $loginData = [
            'email' => 'admin3@dashboard.com'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_requires_valid_email_format()
    {
        // Arrange
        $loginData = [
            'email' => 'invalid-email',
            'password' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_must_be_at_least_6_characters()
    {
        // Arrange
        $loginData = [
            'email' => 'admin3@dashboard.com',
            'password' => '12345'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_admin_last_login_is_updated_after_successful_login()
    {
        // Arrange
        $admin = Admin::factory()->create([
            'email' => 'admin3@dashboard.com',
            'password' => bcrypt('password123'),
            'last_login_at' => null
        ]);

        $loginData = [
            'email' => 'admin3@dashboard.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(200);
        
        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);
        $this->assertTrue($admin->last_login_at->isToday());
    }

    public function test_login_returns_admin_permissions()
    {
        // Arrange - criar admin com roles e permissões
        $admin = Admin::factory()->create([
            'email' => 'admin_with_permissions@dashboard.com',
            'password' => bcrypt('password123'),
            'is_super_admin' => false
        ]);

        // Criar role e permissões (assumindo que existem seeders)
        $this->seed();

        $loginData = [
            'email' => 'admin_with_permissions@dashboard.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/login', $loginData);

        // Assert
        $response->assertStatus(200);
        
        $permissions = $response->json('permissions');
        
        // Verificar que permissões são retornadas
        $this->assertIsArray($permissions);
        
        // Se o admin tem roles, deve ter permissões
        if (!empty($permissions)) {
            $firstPermission = $permissions[0];
            $this->assertArrayHasKey('id', $firstPermission);
            $this->assertArrayHasKey('name', $firstPermission);
            $this->assertArrayHasKey('slug', $firstPermission);
            $this->assertArrayHasKey('description', $firstPermission);
        }
    }
} 