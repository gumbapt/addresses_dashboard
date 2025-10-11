<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StateManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->seed(\Database\Seeders\AdminRolePermissionSeeder::class);
        $this->admin = Admin::where('email', 'admin@dashboard.com')->first();
    }

    public function test_admin_can_list_states_paginated(): void
    {
        // Arrange
        State::factory()->count(10)->create();
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states?per_page=5');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'timezone',
                        'latitude',
                        'longitude',
                        'is_active'
                    ]
                ],
                'pagination' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                    'from',
                    'to'
                ]
            ]);

        $this->assertEquals(5, count($response->json('data')));
        $this->assertEquals(10, $response->json('pagination.total'));
    }

    public function test_admin_can_get_all_active_states_without_pagination(): void
    {
        // Arrange
        State::factory()->count(5)->create(['is_active' => true]);
        State::factory()->count(2)->create(['is_active' => false]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states/all');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'code', 'name', 'timezone', 'latitude', 'longitude', 'is_active']
                ]
            ]);

        // Only active states should be returned
        $this->assertEquals(5, count($response->json('data')));
        
        // Verify all returned states are active
        foreach ($response->json('data') as $state) {
            $this->assertTrue($state['is_active']);
        }
    }

    public function test_admin_can_get_state_by_code(): void
    {
        // Arrange
        $state = State::factory()->create([
            'code' => 'CA',
            'name' => 'California',
        ]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states/CA');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $state->id,
                    'code' => 'CA',
                    'name' => 'California',
                ]
            ]);
    }

    public function test_get_state_by_code_returns_404_when_not_found(): void
    {
        // Arrange
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states/XX');

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'State with code XX not found'
            ]);
    }

    public function test_can_filter_states_by_active_status(): void
    {
        // Arrange
        State::factory()->count(3)->create(['is_active' => true]);
        State::factory()->count(2)->create(['is_active' => false]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act - Filter active states
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states?is_active=true');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('pagination.total'));

        // Act - Filter inactive states
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states?is_active=false');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
    }

    public function test_can_search_states_by_name(): void
    {
        // Arrange
        State::factory()->create(['name' => 'California', 'code' => 'CA']);
        State::factory()->create(['name' => 'Texas', 'code' => 'TX']);
        State::factory()->create(['name' => 'New York', 'code' => 'NY']);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states?search=calif');

        // Assert
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('pagination.total'));
        $this->assertStringContainsString('Calif', $response->json('data.0.name'));
    }

    public function test_states_are_returned_ordered_by_name(): void
    {
        // Arrange
        State::factory()->create(['name' => 'Texas', 'code' => 'TX']);
        State::factory()->create(['name' => 'Alabama', 'code' => 'AL']);
        State::factory()->create(['name' => 'Montana', 'code' => 'MT']);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states?per_page=10');

        // Assert
        $response->assertStatus(200);
        $states = $response->json('data');
        
        // Check alphabetical order
        $names = array_column($states, 'name');
        $sortedNames = $names;
        sort($sortedNames);
        $this->assertEquals($sortedNames, $names);
    }

    public function test_pagination_respects_per_page_limits(): void
    {
        // Arrange
        State::factory()->count(50)->create();
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act - Request 200 items (should be capped at 100)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states?per_page=200');

        // Assert
        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, $response->json('pagination.per_page'));

        // Act - Request 0 items (should default to minimum 1)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states?per_page=0');

        // Assert
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('pagination.per_page'));
    }

    public function test_unauthorized_user_cannot_access_states(): void
    {
        // Act - No token
        $response = $this->getJson('/api/admin/states');

        // Assert
        $response->assertStatus(401);
    }

    public function test_state_data_includes_coordinates(): void
    {
        // Arrange
        $state = State::factory()->create([
            'code' => 'CA',
            'latitude' => 36.7783,
            'longitude' => -119.4179,
        ]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states/CA');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'latitude' => 36.7783,
                    'longitude' => -119.4179,
                ]
            ]);
    }

    public function test_state_data_includes_timezone(): void
    {
        // Arrange
        $state = State::factory()->create([
            'code' => 'CA',
            'timezone' => 'America/Los_Angeles',
        ]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/states/CA');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'timezone' => 'America/Los_Angeles',
                ]
            ]);
    }
}

