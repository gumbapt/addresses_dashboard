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
            'email' => 'admin@lestjam.com',
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => 'admin@lestjam.com',
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
                'token'
            ])
            ->assertJson([
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'is_active' => true
                ]
            ]);

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
            'email' => 'admin@lestjam.com',
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => 'admin@lestjam.com',
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
            'email' => 'admin@lestjam.com',
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => 'admin@lestjam.com',
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
            'email' => 'admin@lestjam.com'
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
            'email' => 'admin@lestjam.com',
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
            'email' => 'admin@lestjam.com',
            'password' => bcrypt('password123'),
            'last_login_at' => null
        ]);

        $loginData = [
            'email' => 'admin@lestjam.com',
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
} 