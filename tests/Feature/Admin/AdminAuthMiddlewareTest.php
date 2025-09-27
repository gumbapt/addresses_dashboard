<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_protected_route()
    {
        // Arrange
        $admin = Admin::factory()->create([
            'email' => 'admin@dashboard_addresses.com',
            'password' => bcrypt('password123')
        ]);

        $loginResponse = $this->postJson('/api/admin/login', [
            'email' => 'admin@dashboard_addresses.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('token');

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/dashboard');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Welcome to Admin Dashboard'
            ]);
    }

    public function test_regular_user_cannot_access_admin_route()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123')
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('token');

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/dashboard');

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Access denied. Admin privileges required.'
            ]);
    }

    public function test_inactive_admin_cannot_access_protected_route()
    {
        $admin = Admin::factory()->inactive()->create([
            'email' => 'admin@dashboard_addresses.com',
            'password' => bcrypt('password123')
        ]);
        $token = $admin->createToken('test-token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/dashboard');
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Access denied. Admin privileges required.'
            ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_route()
    {
        // Act
        $response = $this->getJson('/api/admin/dashboard');

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_invalid_token_cannot_access_admin_route()
    {
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token'
        ])->getJson('/api/admin/dashboard');

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
} 