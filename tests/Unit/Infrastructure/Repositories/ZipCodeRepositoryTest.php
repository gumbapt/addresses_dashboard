<?php

namespace Tests\Unit\Infrastructure\Repositories;

use Tests\TestCase;
use App\Models\State;
use App\Models\City;
use App\Models\ZipCode;
use App\Infrastructure\Repositories\ZipCodeRepository;
use App\Domain\Entities\ZipCode as ZipCodeEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ZipCodeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ZipCodeRepository $repository;
    private State $state;
    private City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ZipCodeRepository();
        $this->state = State::factory()->create(['code' => 'CA', 'name' => 'California']);
        $this->city = City::factory()->create(['name' => 'Los Angeles', 'state_id' => $this->state->id]);
    }

    public function test_find_by_id_returns_zip_code_entity(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
        ]);

        // Act
        $result = $this->repository->findById($zipCode->id);

        // Assert
        $this->assertInstanceOf(ZipCodeEntity::class, $result);
        $this->assertEquals($zipCode->id, $result->getId());
        $this->assertEquals('90210', $result->getCode());
        $this->assertEquals($this->state->id, $result->getStateId());
        $this->assertEquals($this->city->id, $result->getCityId());
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        // Act
        $result = $this->repository->findById(999);

        // Assert
        $this->assertNull($result);
    }

    public function test_find_by_code_returns_zip_code_entity(): void
    {
        // Arrange
        ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
        ]);

        // Act
        $result = $this->repository->findByCode('90210');

        // Assert
        $this->assertInstanceOf(ZipCodeEntity::class, $result);
        $this->assertEquals('90210', $result->getCode());
    }

    public function test_find_by_code_normalizes_input(): void
    {
        // Arrange
        ZipCode::factory()->create([
            'code' => '07018',
            'state_id' => $this->state->id,
        ]);

        // Act - Search with int (should normalize to string with leading zero)
        $result = $this->repository->findByCode(7018);

        // Assert
        $this->assertInstanceOf(ZipCodeEntity::class, $result);
        $this->assertEquals('07018', $result->getCode());
    }

    public function test_find_by_code_returns_null_when_not_found(): void
    {
        // Act
        $result = $this->repository->findByCode('99999');

        // Assert
        $this->assertNull($result);
    }

    public function test_find_by_state_returns_array_of_zip_code_entities(): void
    {
        // Arrange
        ZipCode::factory()->count(3)->create(['state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findByState($this->state->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(ZipCodeEntity::class, $result);
    }

    public function test_find_by_state_returns_zip_codes_ordered_by_code(): void
    {
        // Arrange
        ZipCode::factory()->create(['code' => '90211', 'state_id' => $this->state->id]);
        ZipCode::factory()->create(['code' => '10001', 'state_id' => $this->state->id]);
        ZipCode::factory()->create(['code' => '90210', 'state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findByState($this->state->id);

        // Assert
        $this->assertEquals('10001', $result[0]->getCode());
        $this->assertEquals('90210', $result[1]->getCode());
        $this->assertEquals('90211', $result[2]->getCode());
    }

    public function test_find_by_city_returns_array_of_zip_code_entities(): void
    {
        // Arrange
        ZipCode::factory()->count(3)->create([
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
        ]);

        // Act
        $result = $this->repository->findByCity($this->city->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(ZipCodeEntity::class, $result);
    }

    public function test_find_all_returns_array_of_zip_code_entities(): void
    {
        // Arrange
        ZipCode::factory()->count(3)->create(['state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(ZipCodeEntity::class, $result);
    }

    public function test_find_all_paginated_returns_correct_structure(): void
    {
        // Arrange
        ZipCode::factory()->count(10)->create(['state_id' => $this->state->id]);

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
        ZipCode::factory()->count(10)->create(['state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 5);

        // Assert
        $this->assertCount(5, $result['data']);
        $this->assertEquals(10, $result['total']);
        $this->assertEquals(5, $result['per_page']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertContainsOnlyInstancesOf(ZipCodeEntity::class, $result['data']);
    }

    public function test_find_all_paginated_can_filter_by_search(): void
    {
        // Arrange
        ZipCode::factory()->create(['code' => '90210', 'state_id' => $this->state->id]);
        ZipCode::factory()->create(['code' => '90211', 'state_id' => $this->state->id]);
        ZipCode::factory()->create(['code' => '10001', 'state_id' => $this->state->id]);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, search: '902');

        // Assert
        $this->assertEquals(2, $result['total']);
        
        foreach ($result['data'] as $zipCode) {
            $this->assertStringContainsString('902', $zipCode->getCode());
        }
    }

    public function test_find_all_paginated_can_filter_by_state(): void
    {
        // Arrange
        $texasState = State::factory()->create(['code' => 'TX']);
        
        ZipCode::factory()->count(3)->create(['state_id' => $this->state->id]);
        ZipCode::factory()->count(2)->create(['state_id' => $texasState->id]);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, stateId: $this->state->id);

        // Assert
        $this->assertEquals(3, $result['total']);
    }

    public function test_find_all_paginated_can_filter_by_city(): void
    {
        // Arrange
        $sanDiegoCity = City::factory()->create(['state_id' => $this->state->id]);
        
        ZipCode::factory()->count(3)->create(['state_id' => $this->state->id, 'city_id' => $this->city->id]);
        ZipCode::factory()->count(2)->create(['state_id' => $this->state->id, 'city_id' => $sanDiegoCity->id]);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, cityId: $this->city->id);

        // Assert
        $this->assertEquals(3, $result['total']);
    }

    public function test_find_all_paginated_can_filter_by_active_status(): void
    {
        // Arrange
        ZipCode::factory()->count(3)->create(['state_id' => $this->state->id, 'is_active' => true]);
        ZipCode::factory()->count(2)->create(['state_id' => $this->state->id, 'is_active' => false]);

        // Act - Filter active
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, isActive: true);

        // Assert
        $this->assertEquals(3, $result['total']);

        // Act - Filter inactive
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, isActive: false);

        // Assert
        $this->assertEquals(2, $result['total']);
    }

    public function test_create_creates_new_zip_code(): void
    {
        // Act
        $result = $this->repository->create(
            code: '90210',
            stateId: $this->state->id,
            cityId: $this->city->id,
            latitude: 34.0901,
            longitude: -118.4065,
            type: 'Standard',
            population: 21733
        );

        // Assert
        $this->assertInstanceOf(ZipCodeEntity::class, $result);
        $this->assertEquals('90210', $result->getCode());
        $this->assertEquals($this->state->id, $result->getStateId());
        $this->assertEquals($this->city->id, $result->getCityId());
        $this->assertTrue($result->isActive());
        
        // Verify database
        $this->assertDatabaseHas('zip_codes', [
            'code' => '90210',
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
        ]);
    }

    public function test_create_normalizes_zip_code(): void
    {
        // Act - Pass int that needs leading zero
        $result = $this->repository->create(
            code: 7018,
            stateId: $this->state->id
        );

        // Assert
        $this->assertEquals('07018', $result->getCode());
        $this->assertDatabaseHas('zip_codes', ['code' => '07018']);
    }

    public function test_find_or_create_returns_existing_zip_code(): void
    {
        // Arrange
        $existingZipCode = ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
        ]);

        // Act
        $result = $this->repository->findOrCreate('90210', $this->state->id);

        // Assert
        $this->assertInstanceOf(ZipCodeEntity::class, $result);
        $this->assertEquals($existingZipCode->id, $result->getId());
        $this->assertEquals('90210', $result->getCode());
        
        // Verify no duplicate was created
        $this->assertEquals(1, ZipCode::where('code', '90210')->count());
    }

    public function test_find_or_create_creates_new_zip_code_when_not_found(): void
    {
        // Act
        $result = $this->repository->findOrCreate(
            code: '90211',
            stateId: $this->state->id,
            cityId: $this->city->id,
            latitude: 34.0,
            longitude: -118.0
        );

        // Assert
        $this->assertInstanceOf(ZipCodeEntity::class, $result);
        $this->assertEquals('90211', $result->getCode());
        $this->assertEquals($this->state->id, $result->getStateId());
        $this->assertEquals($this->city->id, $result->getCityId());
        
        // Verify database
        $this->assertDatabaseHas('zip_codes', [
            'code' => '90211',
            'state_id' => $this->state->id,
        ]);
    }

    public function test_find_or_create_normalizes_zip_code(): void
    {
        // Act - Pass int that needs leading zero
        $result = $this->repository->findOrCreate(7018, $this->state->id);

        // Assert
        $this->assertEquals('07018', $result->getCode());
        
        // Act again - Should find existing
        $result2 = $this->repository->findOrCreate('07018', $this->state->id);
        
        // Assert - Same entity
        $this->assertEquals($result->getId(), $result2->getId());
        $this->assertEquals(1, ZipCode::where('code', '07018')->count());
    }

    public function test_update_modifies_zip_code_data(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
            'latitude' => 10.0,
            'longitude' => 20.0,
            'type' => 'Standard',
            'population' => 1000,
            'is_active' => true,
        ]);

        // Act
        $result = $this->repository->update(
            id: $zipCode->id,
            latitude: 34.0901,
            longitude: -118.4065,
            type: 'PO Box',
            population: 21733,
            isActive: false
        );

        // Assert
        $this->assertInstanceOf(ZipCodeEntity::class, $result);
        $this->assertEquals(34.0901, $result->getLatitude());
        $this->assertEquals(-118.4065, $result->getLongitude());
        $this->assertEquals('PO Box', $result->getType());
        $this->assertEquals(21733, $result->getPopulation());
        $this->assertFalse($result->isActive());
        
        // Verify database
        $this->assertDatabaseHas('zip_codes', [
            'id' => $zipCode->id,
            'latitude' => 34.0901,
            'type' => 'PO Box',
            'is_active' => false,
        ]);
    }

    public function test_update_only_modifies_provided_fields(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
            'latitude' => 10.0,
            'longitude' => 20.0,
            'type' => 'Standard',
        ]);

        // Act - Only update type
        $result = $this->repository->update($zipCode->id, type: 'PO Box');

        // Assert
        $this->assertEquals('PO Box', $result->getType());
        $this->assertEquals(10.0, $result->getLatitude()); // Should remain unchanged
        $this->assertEquals(20.0, $result->getLongitude()); // Should remain unchanged
    }

    public function test_delete_removes_zip_code(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create(['state_id' => $this->state->id]);

        // Act
        $this->repository->delete($zipCode->id);

        // Assert
        $this->assertDatabaseMissing('zip_codes', ['id' => $zipCode->id]);
    }

    public function test_zip_code_entity_conversion_preserves_all_data(): void
    {
        // Arrange
        $zipCode = ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
            'latitude' => 34.0901,
            'longitude' => -118.4065,
            'type' => 'Standard',
            'population' => 21733,
            'is_active' => true,
        ]);

        // Act
        $entity = $this->repository->findById($zipCode->id);

        // Assert
        $this->assertEquals($zipCode->id, $entity->getId());
        $this->assertEquals('90210', $entity->getCode());
        $this->assertEquals($this->state->id, $entity->getStateId());
        $this->assertEquals($this->city->id, $entity->getCityId());
        $this->assertEquals(34.0901, $entity->getLatitude());
        $this->assertEquals(-118.4065, $entity->getLongitude());
        $this->assertEquals('Standard', $entity->getType());
        $this->assertEquals(21733, $entity->getPopulation());
        $this->assertTrue($entity->isActive());
    }

    public function test_zip_code_can_exist_without_city(): void
    {
        // Act
        $result = $this->repository->create(
            code: '90210',
            stateId: $this->state->id,
            cityId: null
        );

        // Assert
        $this->assertNull($result->getCityId());
        $this->assertDatabaseHas('zip_codes', [
            'code' => '90210',
            'city_id' => null,
        ]);
    }

    public function test_find_all_paginated_with_multiple_filters(): void
    {
        // Arrange
        ZipCode::factory()->create([
            'code' => '90210',
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
            'is_active' => true
        ]);
        ZipCode::factory()->create([
            'code' => '90211',
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
            'is_active' => false
        ]);
        ZipCode::factory()->create([
            'code' => '10001',
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
            'is_active' => true
        ]);

        // Act - Search "902" + state + city + active
        $result = $this->repository->findAllPaginated(
            page: 1,
            perPage: 10,
            search: '902',
            stateId: $this->state->id,
            cityId: $this->city->id,
            isActive: true
        );

        // Assert
        $this->assertEquals(1, $result['total']); // Only 90210 matches all criteria
        $this->assertEquals('90210', $result['data'][0]->getCode());
    }
}

