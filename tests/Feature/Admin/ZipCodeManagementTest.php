<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\State;
use App\Models\City;
use App\Models\ZipCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ZipCodeManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private State $state;
    private City $city;

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

        $this->city = City::factory()->create([
            'name' => 'Los Angeles',
            'state_id' => $this->state->id,
        ]);
    }

    public function test_admin_can_list_zip_codes_paginated(): void
    {
        // Arrange
        ZipCode::factory()->count(10)->create(['state_id' => $this->state->id]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes?per_page=5');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'state_id',
                        'city_id',
                        'latitude',
                        'longitude',
                        'type',
                        'population',
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

    public function test_admin_can_get_zip_code_by_code(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
        ]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes/90210');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $zipCode->id,
                    'code' => '90210',
                    'state_id' => $this->state->id,
                    'city_id' => $this->city->id,
                ]
            ]);
    }

    public function test_get_zip_code_by_code_returns_404_when_not_found(): void
    {
        // Arrange
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes/99999');

        // Assert
        $response->assertStatus(404)
            ->assertJsonPath('error', 'ZIP code 99999 not found');
    }

    public function test_admin_can_get_zip_codes_by_state(): void
    {
        // Arrange
        $californiaState = State::factory()->create(['code' => 'CA']);
        $texasState = State::factory()->create(['code' => 'TX']);
        
        ZipCode::factory()->count(3)->create(['state_id' => $californiaState->id]);
        ZipCode::factory()->count(2)->create(['state_id' => $texasState->id]);
        
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/zip-codes/by-state/{$californiaState->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'code', 'state_id', 'city_id']
                ]
            ]);

        $this->assertEquals(3, count($response->json('data')));
        
        // Verify all ZIP codes belong to California
        foreach ($response->json('data') as $zipCode) {
            $this->assertEquals($californiaState->id, $zipCode['state_id']);
        }
    }

    public function test_admin_can_get_zip_codes_by_city(): void
    {
        // Arrange
        $losAngelesCity = City::factory()->create(['state_id' => $this->state->id, 'name' => 'Los Angeles']);
        $sanFranciscoCity = City::factory()->create(['state_id' => $this->state->id, 'name' => 'San Francisco']);
        
        ZipCode::factory()->count(3)->create(['state_id' => $this->state->id, 'city_id' => $losAngelesCity->id]);
        ZipCode::factory()->count(2)->create(['state_id' => $this->state->id, 'city_id' => $sanFranciscoCity->id]);
        
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/zip-codes/by-city/{$losAngelesCity->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
        
        // Verify all ZIP codes belong to Los Angeles
        foreach ($response->json('data') as $zipCode) {
            $this->assertEquals($losAngelesCity->id, $zipCode['city_id']);
        }
    }

    public function test_can_search_zip_codes_by_code(): void
    {
        // Arrange
        ZipCode::factory()->create(['code' => '90210', 'state_id' => $this->state->id]);
        ZipCode::factory()->create(['code' => '90211', 'state_id' => $this->state->id]);
        ZipCode::factory()->create(['code' => '10001', 'state_id' => $this->state->id]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes?search=902');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
        
        foreach ($response->json('data') as $zipCode) {
            $this->assertStringContainsString('902', $zipCode['code']);
        }
    }

    public function test_can_filter_zip_codes_by_state(): void
    {
        // Arrange
        $californiaState = State::factory()->create(['code' => 'CA']);
        $texasState = State::factory()->create(['code' => 'TX']);
        
        ZipCode::factory()->count(3)->create(['state_id' => $californiaState->id]);
        ZipCode::factory()->count(2)->create(['state_id' => $texasState->id]);
        
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/zip-codes?state_id={$californiaState->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('pagination.total'));
    }

    public function test_can_filter_zip_codes_by_city(): void
    {
        // Arrange
        $losAngelesCity = City::factory()->create(['state_id' => $this->state->id]);
        $sanDiegoCity = City::factory()->create(['state_id' => $this->state->id]);
        
        ZipCode::factory()->count(3)->create(['state_id' => $this->state->id, 'city_id' => $losAngelesCity->id]);
        ZipCode::factory()->count(2)->create(['state_id' => $this->state->id, 'city_id' => $sanDiegoCity->id]);
        
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/zip-codes?city_id={$losAngelesCity->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('pagination.total'));
    }

    public function test_can_filter_zip_codes_by_active_status(): void
    {
        // Arrange
        ZipCode::factory()->count(3)->create(['state_id' => $this->state->id, 'is_active' => true]);
        ZipCode::factory()->count(2)->create(['state_id' => $this->state->id, 'is_active' => false]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act - Filter active ZIP codes
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes?is_active=true');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('pagination.total'));

        // Act - Filter inactive ZIP codes
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes?is_active=false');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
    }

    public function test_zip_codes_are_returned_ordered_by_code(): void
    {
        // Arrange
        ZipCode::factory()->create(['code' => '90211', 'state_id' => $this->state->id]);
        ZipCode::factory()->create(['code' => '10001', 'state_id' => $this->state->id]);
        ZipCode::factory()->create(['code' => '90210', 'state_id' => $this->state->id]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes?per_page=10');

        // Assert
        $response->assertStatus(200);
        $zipCodes = $response->json('data');
        
        // Check numerical order
        $codes = array_column($zipCodes, 'code');
        $sortedCodes = $codes;
        sort($sortedCodes);
        $this->assertEquals($sortedCodes, $codes);
    }

    public function test_pagination_respects_per_page_limits(): void
    {
        // Arrange
        ZipCode::factory()->count(50)->create(['state_id' => $this->state->id]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act - Request 200 items (should be capped at 100)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes?per_page=200');

        // Assert
        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, $response->json('pagination.per_page'));

        // Act - Request 0 items (should default to minimum 1)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes?per_page=0');

        // Assert
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('pagination.per_page'));
    }

    public function test_unauthorized_user_cannot_access_zip_codes(): void
    {
        // Act - No token
        $response = $this->getJson('/api/admin/zip-codes');

        // Assert
        $response->assertStatus(401);
    }

    public function test_zip_code_data_includes_coordinates(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
            'latitude' => 34.0901,
            'longitude' => -118.4065,
        ]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes/90210');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'latitude' => 34.0901,
                    'longitude' => -118.4065,
                ]
            ]);
    }

    public function test_zip_code_data_includes_type_and_population(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
            'type' => 'Standard',
            'population' => 21733,
        ]);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/zip-codes/90210');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'Standard',
                    'population' => 21733,
                ]
            ]);
    }

    public function test_zip_code_factory_creates_valid_zip_codes(): void
    {
        // Arrange & Act
        $zipCode = ZipCode::factory()->create(['state_id' => $this->state->id]);

        // Assert
        $this->assertDatabaseHas('zip_codes', [
            'id' => $zipCode->id,
            'code' => $zipCode->code,
            'state_id' => $this->state->id,
        ]);
        
        $this->assertNotNull($zipCode->code);
        $this->assertEquals(5, strlen($zipCode->code)); // ZIP codes should be 5 digits
    }

    public function test_zip_code_belongs_to_state_relationship(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create(['state_id' => $this->state->id]);

        // Act
        $relatedState = $zipCode->state;

        // Assert
        $this->assertNotNull($relatedState);
        $this->assertEquals($this->state->id, $relatedState->id);
        $this->assertEquals($this->state->code, $relatedState->code);
    }

    public function test_zip_code_belongs_to_city_relationship(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create([
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
        ]);

        // Act
        $relatedCity = $zipCode->city;

        // Assert
        $this->assertNotNull($relatedCity);
        $this->assertEquals($this->city->id, $relatedCity->id);
        $this->assertEquals($this->city->name, $relatedCity->name);
    }

    public function test_city_has_many_zip_codes_relationship(): void
    {
        // Arrange
        ZipCode::factory()->count(3)->create([
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
        ]);

        // Act
        $zipCodes = $this->city->zipCodes;

        // Assert
        $this->assertCount(3, $zipCodes);
        
        foreach ($zipCodes as $zipCode) {
            $this->assertEquals($this->city->id, $zipCode->city_id);
        }
    }

    public function test_state_has_many_zip_codes_relationship(): void
    {
        // Arrange
        ZipCode::factory()->count(3)->create(['state_id' => $this->state->id]);

        // Act
        $zipCodes = $this->state->zipCodes;

        // Assert
        $this->assertCount(3, $zipCodes);
        
        foreach ($zipCodes as $zipCode) {
            $this->assertEquals($this->state->id, $zipCode->state_id);
        }
    }

    public function test_zip_code_can_exist_without_city(): void
    {
        // Arrange & Act
        $zipCode = ZipCode::factory()->create([
            'state_id' => $this->state->id,
            'city_id' => null, // ZIP without city
        ]);

        // Assert
        $this->assertDatabaseHas('zip_codes', [
            'id' => $zipCode->id,
            'city_id' => null,
        ]);
        
        $this->assertNull($zipCode->city_id);
        $this->assertNull($zipCode->city);
    }

    public function test_can_combine_multiple_filters(): void
    {
        // Arrange
        $californiaState = State::factory()->create(['code' => 'CA']);
        $losAngelesCity = City::factory()->create(['state_id' => $californiaState->id]);
        
        ZipCode::factory()->create(['code' => '90210', 'state_id' => $californiaState->id, 'city_id' => $losAngelesCity->id, 'is_active' => true]);
        ZipCode::factory()->create(['code' => '90211', 'state_id' => $californiaState->id, 'city_id' => $losAngelesCity->id, 'is_active' => true]);
        ZipCode::factory()->create(['code' => '90212', 'state_id' => $californiaState->id, 'city_id' => $losAngelesCity->id, 'is_active' => false]);
        
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Act - Search "902" in California, Los Angeles, active only
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/zip-codes?search=902&state_id={$californiaState->id}&city_id={$losAngelesCity->id}&is_active=true");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
    }
}

