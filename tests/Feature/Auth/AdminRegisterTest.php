<?php

namespace Tests\Feature\Auth;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_with_valid_data()
    {
        // Arrange
        $registerData = [
            'name' => 'New Admin',
            'email' => 'newadmin@lestjam.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'admin' => [
                    'id',
                    'name',
                    'email',
                    'is_active',
                    'created_at'
                ]
            ])
            ->assertJson([
                'admin' => [
                    'name' => 'New Admin',
                    'email' => 'newadmin@lestjam.com',
                    'is_active' => true
                ]
            ]);

        $this->assertDatabaseHas('admins', [
            'name' => 'New Admin',
            'email' => 'newadmin@lestjam.com',
            'is_active' => true
        ]);
    }

    public function test_admin_cannot_register_with_existing_email()
    {
        // Arrange
        Admin::factory()->create([
            'email' => 'existing@lestjam.com'
        ]);

        $registerData = [
            'name' => 'New Admin',
            'email' => 'existing@lestjam.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Email is already taken'
            ])
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_name()
    {
        // Arrange
        $registerData = [
            'email' => 'newadmin@lestjam.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_register_requires_email()
    {
        // Arrange
        $registerData = [
            'name' => 'New Admin',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password()
    {
        // Arrange
        $registerData = [
            'name' => 'New Admin',
            'email' => 'newadmin@lestjam.com',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_requires_password_confirmation()
    {
        // Arrange
        $registerData = [
            'name' => 'New Admin',
            'email' => 'newadmin@lestjam.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_confirmation_must_match()
    {
        // Arrange
        $registerData = [
            'name' => 'New Admin',
            'email' => 'newadmin@lestjam.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_requires_valid_email_format()
    {
        // Arrange
        $registerData = [
            'name' => 'New Admin',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_must_be_at_least_6_characters()
    {
        // Arrange
        $registerData = [
            'name' => 'New Admin',
            'email' => 'newadmin@lestjam.com',
            'password' => '12345',
            'password_confirmation' => '12345'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_name_cannot_exceed_255_characters()
    {
        // Arrange
        $registerData = [
            'name' => str_repeat('a', 256),
            'email' => 'newadmin@lestjam.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_new_admin_is_created_with_active_status()
    {
        // Arrange
        $registerData = [
            'name' => 'New Admin',
            'email' => 'newadmin@lestjam.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/admin/register', $registerData);

        // Assert
        $response->assertStatus(201);

        $admin = Admin::where('email', 'newadmin@lestjam.com')->first();
        $this->assertTrue($admin->is_active);
        $this->assertNull($admin->last_login_at);
    }
} 