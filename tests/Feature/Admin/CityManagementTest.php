<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\State;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CityManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private State $state;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = Admin::factory()->create([
            'email' => 'admin@test.com',
        ]);

        $this->state = State::factory()->create([
            'code' => 'CA',
            'name' => 'California',
        ]);
    }

    public function test_admin_can_list_cities_paginated(): void
    {
        // Arrange
        City::factory()->count(10)->create(['state_id' => $this->state->id]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/cities?per_page=5');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'state_id',
                        'latitude',
                        'longitude'
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

    public function test_admin_can_get_cities_by_state(): void
    {
        // Arrange
        $californiaState = State::factory()->create(['code' => 'CA', 'name' => 'California']);
        $texasState = State::factory()->create(['code' => 'TX', 'name' => 'Texas']);
        
        City::factory()->count(3)->create(['state_id' => $californiaState->id]);
        City::factory()->count(2)->create(['state_id' => $texasState->id]);
        
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/cities/by-state/{$californiaState->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'state_id', 'latitude', 'longitude']
                ]
            ]);

        $this->assertEquals(3, count($response->json('data')));
        
        // Verify all cities belong to California
        foreach ($response->json('data') as $city) {
            $this->assertEquals($californiaState->id, $city['state_id']);
        }
    }

    public function test_can_search_cities_by_name(): void
    {
        // Arrange
        City::factory()->create(['name' => 'Los Angeles', 'state_id' => $this->state->id]);
        City::factory()->create(['name' => 'San Francisco', 'state_id' => $this->state->id]);
        City::factory()->create(['name' => 'San Diego', 'state_id' => $this->state->id]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/cities?search=San');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
        
        foreach ($response->json('data') as $city) {
            $this->assertStringContainsString('San', $city['name']);
        }
    }

    public function test_can_filter_cities_by_state(): void
    {
        // Arrange
        $californiaState = State::factory()->create(['code' => 'CA']);
        $texasState = State::factory()->create(['code' => 'TX']);
        
        City::factory()->count(3)->create(['state_id' => $californiaState->id]);
        City::factory()->count(2)->create(['state_id' => $texasState->id]);
        
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/cities?state_id={$californiaState->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('pagination.total'));
    }

    public function test_cities_are_returned_ordered_by_name(): void
    {
        // Arrange
        City::factory()->create(['name' => 'San Francisco', 'state_id' => $this->state->id]);
        City::factory()->create(['name' => 'Los Angeles', 'state_id' => $this->state->id]);
        City::factory()->create(['name' => 'San Diego', 'state_id' => $this->state->id]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/cities?per_page=10');

        // Assert
        $response->assertStatus(200);
        $cities = $response->json('data');
        
        // Check alphabetical order
        $names = array_column($cities, 'name');
        $sortedNames = $names;
        sort($sortedNames);
        $this->assertEquals($sortedNames, $names);
    }

    public function test_pagination_respects_per_page_limits(): void
    {
        // Arrange
        City::factory()->count(50)->create(['state_id' => $this->state->id]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act - Request 200 items (should be capped at 100)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/cities?per_page=200');

        // Assert
        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, $response->json('pagination.per_page'));

        // Act - Request 0 items (should default to minimum 1)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/cities?per_page=0');

        // Assert
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('pagination.per_page'));
    }

    public function test_unauthorized_user_cannot_access_cities(): void
    {
        // Act - No token
        $response = $this->getJson('/api/admin/cities');

        // Assert
        $response->assertStatus(401);
    }

    public function test_city_data_includes_coordinates(): void
    {
        // Arrange
        $city = City::factory()->create([
            'name' => 'Los Angeles',
            'state_id' => $this->state->id,
            'latitude' => 34.0522,
            'longitude' => -118.2437,
        ]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/cities');

        // Assert
        $response->assertStatus(200);
        $cityData = collect($response->json('data'))->firstWhere('id', $city->id);
        
        $this->assertNotNull($cityData);
        $this->assertEquals(34.0522, $cityData['latitude']);
        $this->assertEquals(-118.2437, $cityData['longitude']);
    }

    public function test_can_combine_search_and_state_filter(): void
    {
        // Arrange
        $californiaState = State::factory()->create(['code' => 'CA']);
        $texasState = State::factory()->create(['code' => 'TX']);
        
        City::factory()->create(['name' => 'San Francisco', 'state_id' => $californiaState->id]);
        City::factory()->create(['name' => 'San Diego', 'state_id' => $californiaState->id]);
        City::factory()->create(['name' => 'San Antonio', 'state_id' => $texasState->id]);
        
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act - Search "San" in California only
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/cities?search=San&state_id={$californiaState->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
        
        foreach ($response->json('data') as $city) {
            $this->assertStringContainsString('San', $city['name']);
            $this->assertEquals($californiaState->id, $city['state_id']);
        }
    }

    public function test_by_state_endpoint_returns_empty_array_for_state_without_cities(): void
    {
        // Arrange
        $emptyState = State::factory()->create(['code' => 'WY', 'name' => 'Wyoming']);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/cities/by-state/{$emptyState->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => []
            ]);
    }

    public function test_city_factory_creates_valid_cities(): void
    {
        // Arrange & Act
        $city = City::factory()->create(['state_id' => $this->state->id]);

        // Assert
        $this->assertDatabaseHas('cities', [
            'id' => $city->id,
            'name' => $city->name,
            'state_id' => $this->state->id,
        ]);
        
        $this->assertNotNull($city->name);
        $this->assertNotNull($city->state_id);
    }

    public function test_city_belongs_to_state_relationship(): void
    {
        // Arrange
        $city = City::factory()->create(['state_id' => $this->state->id]);

        // Act
        $relatedState = $city->state;

        // Assert
        $this->assertNotNull($relatedState);
        $this->assertEquals($this->state->id, $relatedState->id);
        $this->assertEquals($this->state->code, $relatedState->code);
    }

    public function test_state_has_many_cities_relationship(): void
    {
        // Arrange
        City::factory()->count(3)->create(['state_id' => $this->state->id]);

        // Act
        $cities = $this->state->cities;

        // Assert
        $this->assertCount(3, $cities);
        
        foreach ($cities as $city) {
            $this->assertEquals($this->state->id, $city->state_id);
        }
    }
}

