<?php

namespace Tests\Unit\Infrastructure\Repositories;

use Tests\TestCase;
use App\Models\State;
use App\Infrastructure\Repositories\StateRepository;
use App\Domain\Entities\State as StateEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StateRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private StateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new StateRepository();
    }

    public function test_find_by_id_returns_state_entity(): void
    {
        // Arrange
        $state = State::factory()->create(['code' => 'CA', 'name' => 'California']);

        // Act
        $result = $this->repository->findById($state->id);

        // Assert
        $this->assertInstanceOf(StateEntity::class, $result);
        $this->assertEquals($state->id, $result->getId());
        $this->assertEquals('CA', $result->getCode());
        $this->assertEquals('California', $result->getName());
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        // Act
        $result = $this->repository->findById(999);

        // Assert
        $this->assertNull($result);
    }

    public function test_find_by_code_returns_state_entity(): void
    {
        // Arrange
        State::factory()->create(['code' => 'CA', 'name' => 'California']);

        // Act
        $result = $this->repository->findByCode('CA');

        // Assert
        $this->assertInstanceOf(StateEntity::class, $result);
        $this->assertEquals('CA', $result->getCode());
        $this->assertEquals('California', $result->getName());
    }

    public function test_find_by_code_returns_null_when_not_found(): void
    {
        // Act
        $result = $this->repository->findByCode('XX');

        // Assert
        $this->assertNull($result);
    }

    public function test_find_all_returns_array_of_state_entities(): void
    {
        // Arrange
        State::factory()->count(3)->create();

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(StateEntity::class, $result);
    }

    public function test_find_all_returns_empty_array_when_no_states(): void
    {
        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_find_all_returns_states_ordered_by_name(): void
    {
        // Arrange
        State::factory()->create(['name' => 'Texas', 'code' => 'TX']);
        State::factory()->create(['name' => 'Alabama', 'code' => 'AL']);
        State::factory()->create(['name' => 'Montana', 'code' => 'MT']);

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertEquals('Alabama', $result[0]->getName());
        $this->assertEquals('Montana', $result[1]->getName());
        $this->assertEquals('Texas', $result[2]->getName());
    }

    public function test_find_all_active_returns_only_active_states(): void
    {
        // Arrange
        State::factory()->count(3)->create(['is_active' => true]);
        State::factory()->count(2)->create(['is_active' => false]);

        // Act
        $result = $this->repository->findAllActive();

        // Assert
        $this->assertCount(3, $result);
        
        foreach ($result as $state) {
            $this->assertTrue($state->isActive());
        }
    }

    public function test_find_all_paginated_returns_correct_structure(): void
    {
        // Arrange
        State::factory()->count(10)->create();

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 5);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertArrayHasKey('last_page', $result);
        $this->assertArrayHasKey('from', $result);
        $this->assertArrayHasKey('to', $result);
    }

    public function test_find_all_paginated_returns_correct_data(): void
    {
        // Arrange
        State::factory()->count(10)->create();

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 5);

        // Assert
        $this->assertCount(5, $result['data']);
        $this->assertEquals(10, $result['total']);
        $this->assertEquals(5, $result['per_page']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(2, $result['last_page']);
        $this->assertContainsOnlyInstancesOf(StateEntity::class, $result['data']);
    }

    public function test_find_all_paginated_can_filter_by_search(): void
    {
        // Arrange
        State::factory()->create(['name' => 'California', 'code' => 'CA']);
        State::factory()->create(['name' => 'Texas', 'code' => 'TX']);
        State::factory()->create(['name' => 'Carolina', 'code' => 'NC']);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, search: 'Calif');

        // Assert
        $this->assertGreaterThanOrEqual(1, $result['total']);
        $this->assertStringContainsString('Calif', $result['data'][0]->getName());
    }

    public function test_find_all_paginated_can_filter_by_active_status(): void
    {
        // Arrange
        State::factory()->count(3)->create(['is_active' => true]);
        State::factory()->count(2)->create(['is_active' => false]);

        // Act - Filter active
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, isActive: true);

        // Assert
        $this->assertEquals(3, $result['total']);

        // Act - Filter inactive
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10, isActive: false);

        // Assert
        $this->assertEquals(2, $result['total']);
    }

    public function test_find_all_paginated_returns_states_ordered_by_name(): void
    {
        // Arrange
        State::factory()->create(['name' => 'Texas', 'code' => 'TX']);
        State::factory()->create(['name' => 'Alabama', 'code' => 'AL']);
        State::factory()->create(['name' => 'Montana', 'code' => 'MT']);

        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10);

        // Assert
        $this->assertEquals('Alabama', $result['data'][0]->getName());
        $this->assertEquals('Montana', $result['data'][1]->getName());
        $this->assertEquals('Texas', $result['data'][2]->getName());
    }

    public function test_find_all_paginated_handles_second_page(): void
    {
        // Arrange
        State::factory()->count(10)->create();

        // Act
        $result = $this->repository->findAllPaginated(page: 2, perPage: 3);

        // Assert
        $this->assertEquals(2, $result['current_page']);
        $this->assertCount(3, $result['data']);
        $this->assertEquals(4, $result['from']);
        $this->assertEquals(6, $result['to']);
    }

    public function test_find_all_paginated_handles_empty_results(): void
    {
        // Act
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10);

        // Assert
        $this->assertEmpty($result['data']);
        $this->assertEquals(0, $result['total']);
    }

    public function test_state_entity_conversion_preserves_all_data(): void
    {
        // Arrange
        $state = State::factory()->create([
            'code' => 'CA',
            'name' => 'California',
            'timezone' => 'America/Los_Angeles',
            'latitude' => 36.7783,
            'longitude' => -119.4179,
            'is_active' => true,
        ]);

        // Act
        $entity = $this->repository->findById($state->id);

        // Assert
        $this->assertEquals($state->id, $entity->getId());
        $this->assertEquals('CA', $entity->getCode());
        $this->assertEquals('California', $entity->getName());
        $this->assertEquals('America/Los_Angeles', $entity->getTimezone());
        $this->assertEquals(36.7783, $entity->getLatitude());
        $this->assertEquals(-119.4179, $entity->getLongitude());
        $this->assertTrue($entity->isActive());
    }

    public function test_find_by_code_handles_different_cases(): void
    {
        // Arrange
        State::factory()->create(['code' => 'CA', 'name' => 'California']);

        // Act
        $result = $this->repository->findByCode('ca');

        // Assert - SQLite is case-insensitive by default, so this will find the state
        // In production with case-sensitive collation, this would return null
        $this->assertNotNull($result);
        $this->assertEquals('CA', $result->getCode()); // Original code is preserved
    }
}

