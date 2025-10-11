<?php

namespace Tests\Unit\Infrastructure\Repositories;

use Tests\TestCase;
use App\Models\State;
use App\Models\City;
use App\Infrastructure\Repositories\CityRepository;
use App\Domain\Entities\City as CityEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CityRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CityRepository $repository;
    private State $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CityRepository();
        $this->state = State::factory()->create(['code' => 'CA', 'name' => 'California']);
    }

    public function test_find_by_id_returns_city_entity(): void
    {
        // Arrange
        $city = City::factory()->create([
            'name' => 'Los Angeles',
            'state_id' => $this->state->id,
        ]);

        // Act
        $result = $this->repository->findById($city->id);

        // Assert
        $this->assertInstanceOf(CityEntity::class, $result);
        $this->assertEquals($city->id, $result->getId());
        $this->assertEquals('Los Angeles', $result->getName());
        $this->assertEquals($this->state->id, $result->getStateId());
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        // Act
        $result = $this->repository->findById(999);

        // Assert
        $this->assertNull($result);
    }

    public function test_find_by_name_and_state_returns_city_entity(): void
    {
        // Arrange
        City::factory()->create([
            'name' => 'Los Angeles',
            'state_id' => $this->state->id,
        ]);

        // Act
        $result = $this->repository->findByNameAndState('Los Angeles', $this->state->id);

        // Assert
        $this->assertInstanceOf(CityEntity::class, $result);
        $this->assertEquals('Los Angeles', $result->getName());
        $this->assertEquals($this->state->id, $result->getStateId());
    }

    public function test_find_by_name_and_state_returns_null_when_not_found(): void
    {
        // Act
        $result = $this->repository->findByNameAndState('Nonexistent City', $this->state->id);

        // Assert
        $this->assertNull($result);
    }

    public function test_find_by_state_returns_array_of_city_entities(): void
    {
        // Arrange
        City::factory()->count(3)->create(['state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findByState($this->state->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(CityEntity::class, $result);
    }

    public function test_find_by_state_returns_cities_ordered_by_name(): void
    {
        // Arrange
        City::factory()->create(['name' => 'San Francisco', 'state_id' => $this->state->id]);
        City::factory()->create(['name' => 'Los Angeles', 'state_id' => $this->state->id]);
        City::factory()->create(['name' => 'San Diego', 'state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findByState($this->state->id);

        // Assert
        $this->assertEquals('Los Angeles', $result[0]->getName());
        $this->assertEquals('San Diego', $result[1]->getName());
        $this->assertEquals('San Francisco', $result[2]->getName());
    }

    public function test_find_by_state_returns_empty_array_when_no_cities(): void
    {
        // Act
        $result = $this->repository->findByState($this->state->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_find_all_returns_array_of_city_entities(): void
    {
        // Arrange
        City::factory()->count(3)->create(['state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(CityEntity::class, $result);
    }

    public function test_find_all_paginated_returns_correct_structure(): void
    {
        // Arrange
        City::factory()->count(10)->create(['state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 5);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertArrayHasKey('last_page', $result);
    }

    public function test_find_all_paginated_returns_correct_data(): void
    {
        // Arrange
        City::factory()->count(10)->create(['state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 5);

        // Assert
        $this->assertCount(5, $result['data']);
        $this->assertEquals(10, $result['total']);
        $this->assertEquals(5, $result['per_page']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertContainsOnlyInstancesOf(CityEntity::class, $result['data']);
    }

    public function test_find_all_paginated_can_filter_by_search(): void
    {
        // Arrange
        City::factory()->create(['name' => 'Los Angeles', 'state_id' => $this->state->id]);
        City::factory()->create(['name' => 'San Francisco', 'state_id' => $this->state->id]);
        City::factory()->create(['name' => 'San Diego', 'state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, search: 'San');

        // Assert
        $this->assertEquals(2, $result['total']);
        
        foreach ($result['data'] as $city) {
            $this->assertStringContainsString('San', $city->getName());
        }
    }

    public function test_find_all_paginated_can_filter_by_state(): void
    {
        // Arrange
        $texasState = State::factory()->create(['code' => 'TX']);
        
        City::factory()->count(3)->create(['state_id' => $this->state->id]);
        City::factory()->count(2)->create(['state_id' => $texasState->id]);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, stateId: $this->state->id);

        // Assert
        $this->assertEquals(3, $result['total']);
        
        foreach ($result['data'] as $city) {
            $this->assertEquals($this->state->id, $city->getStateId());
        }
    }

    public function test_find_or_create_returns_existing_city(): void
    {
        // Arrange
        $existingCity = City::factory()->create([
            'name' => 'Los Angeles',
            'state_id' => $this->state->id,
        ]);

        // Act
        $result = $this->repository->findOrCreate('Los Angeles', $this->state->id);

        // Assert
        $this->assertInstanceOf(CityEntity::class, $result);
        $this->assertEquals($existingCity->id, $result->getId());
        $this->assertEquals('Los Angeles', $result->getName());
        
        // Verify no duplicate was created
        $this->assertEquals(1, City::where('name', 'Los Angeles')->count());
    }

    public function test_find_or_create_creates_new_city_when_not_found(): void
    {
        // Act
        $result = $this->repository->findOrCreate('New City', $this->state->id, 34.0522, -118.2437);

        // Assert
        $this->assertInstanceOf(CityEntity::class, $result);
        $this->assertEquals('New City', $result->getName());
        $this->assertEquals($this->state->id, $result->getStateId());
        $this->assertEquals(34.0522, $result->getLatitude());
        $this->assertEquals(-118.2437, $result->getLongitude());
        
        // Verify city was created in database
        $this->assertDatabaseHas('cities', [
            'name' => 'New City',
            'state_id' => $this->state->id,
        ]);
    }

    public function test_find_or_create_is_case_sensitive(): void
    {
        // Arrange
        City::factory()->create([
            'name' => 'Los Angeles',
            'state_id' => $this->state->id,
        ]);

        // Act - Different case should create new city
        $result = $this->repository->findOrCreate('los angeles', $this->state->id);

        // Assert
        $this->assertEquals('los angeles', $result->getName());
        $this->assertEquals(2, City::count()); // Two cities should exist
    }

    public function test_update_modifies_city_data(): void
    {
        // Arrange
        $city = City::factory()->create([
            'name' => 'Original Name',
            'state_id' => $this->state->id,
            'latitude' => 10.0,
            'longitude' => 20.0,
        ]);

        // Act
        $result = $this->repository->update(
            $city->id,
            name: 'Updated Name',
            latitude: 30.0,
            longitude: 40.0
        );

        // Assert
        $this->assertInstanceOf(CityEntity::class, $result);
        $this->assertEquals('Updated Name', $result->getName());
        $this->assertEquals(30.0, $result->getLatitude());
        $this->assertEquals(40.0, $result->getLongitude());
        
        // Verify database was updated
        $this->assertDatabaseHas('cities', [
            'id' => $city->id,
            'name' => 'Updated Name',
            'latitude' => 30.0,
            'longitude' => 40.0,
        ]);
    }

    public function test_update_only_modifies_provided_fields(): void
    {
        // Arrange
        $city = City::factory()->create([
            'name' => 'Original Name',
            'state_id' => $this->state->id,
            'latitude' => 10.0,
            'longitude' => 20.0,
        ]);

        // Act - Only update name
        $result = $this->repository->update($city->id, name: 'New Name');

        // Assert
        $this->assertEquals('New Name', $result->getName());
        $this->assertEquals(10.0, $result->getLatitude()); // Should remain unchanged
        $this->assertEquals(20.0, $result->getLongitude()); // Should remain unchanged
    }

    public function test_delete_removes_city(): void
    {
        // Arrange
        $city = City::factory()->create(['state_id' => $this->state->id]);

        // Act
        $this->repository->delete($city->id);

        // Assert
        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    }

    public function test_city_entity_conversion_preserves_all_data(): void
    {
        // Arrange
        $city = City::factory()->create([
            'name' => 'Los Angeles',
            'state_id' => $this->state->id,
            'latitude' => 34.0522,
            'longitude' => -118.2437,
            'population' => 3979576,
        ]);

        // Act
        $entity = $this->repository->findById($city->id);

        // Assert
        $this->assertEquals($city->id, $entity->getId());
        $this->assertEquals('Los Angeles', $entity->getName());
        $this->assertEquals($this->state->id, $entity->getStateId());
        $this->assertEquals(34.0522, $entity->getLatitude());
        $this->assertEquals(-118.2437, $entity->getLongitude());
        $this->assertEquals(3979576, $entity->getPopulation());
    }

    public function test_find_by_name_and_state_distinguishes_between_states(): void
    {
        // Arrange
        $texasState = State::factory()->create(['code' => 'TX']);
        
        City::factory()->create(['name' => 'Austin', 'state_id' => $this->state->id]); // Austin, CA
        City::factory()->create(['name' => 'Austin', 'state_id' => $texasState->id]); // Austin, TX

        // Act
        $californiaAustin = $this->repository->findByNameAndState('Austin', $this->state->id);
        $texasAustin = $this->repository->findByNameAndState('Austin', $texasState->id);

        // Assert
        $this->assertNotEquals($californiaAustin->getId(), $texasAustin->getId());
        $this->assertEquals($this->state->id, $californiaAustin->getStateId());
        $this->assertEquals($texasState->id, $texasAustin->getStateId());
    }
}

