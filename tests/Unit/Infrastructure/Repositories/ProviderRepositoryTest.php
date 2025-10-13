<?php

namespace Tests\Unit\Infrastructure\Repositories;

use App\Domain\Entities\Provider as ProviderEntity;
use App\Infrastructure\Repositories\ProviderRepository;
use App\Models\Provider as ProviderModel;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProviderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProviderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProviderRepository();
    }

    public function test_create_provider_with_normalized_name(): void
    {
        $provider = $this->repository->create(
            name: 'AT & T',
            website: 'att.com',
            technologies: ['Fiber', 'Mobile']
        );

        $this->assertInstanceOf(ProviderEntity::class, $provider);
        $this->assertEquals('AT&T', $provider->getName());
        $this->assertEquals('att', $provider->getSlug());
        $this->assertEquals('https://att.com', $provider->getWebsite());
        $this->assertEquals(['Fiber', 'Mobile'], $provider->getTechnologies());
        $this->assertTrue($provider->isActive());
    }

    public function test_find_or_create_creates_new_provider(): void
    {
        $provider = $this->repository->findOrCreate(
            name: 'AT&T',
            technologies: ['Fiber'],
            website: 'att.com'
        );

        $this->assertInstanceOf(ProviderEntity::class, $provider);
        $this->assertEquals('AT&T', $provider->getName());
        $this->assertEquals('att', $provider->getSlug());
        $this->assertEquals(['Fiber'], $provider->getTechnologies());
        
        // Verify it was saved to database
        $this->assertDatabaseHas('providers', [
            'name' => 'AT&T',
            'slug' => 'att',
        ]);
    }

    public function test_find_or_create_finds_existing_provider(): void
    {
        // Create initial provider
        $firstProvider = $this->repository->findOrCreate(
            name: 'AT&T',
            technologies: ['Fiber']
        );

        // Try to create again - should find existing
        $secondProvider = $this->repository->findOrCreate(
            name: 'AT&T',
            technologies: ['Mobile']
        );

        $this->assertEquals($firstProvider->getId(), $secondProvider->getId());
        $this->assertEquals('AT&T', $secondProvider->getName());
    }

    public function test_find_or_create_merges_technologies(): void
    {
        // Create provider with Fiber
        $firstProvider = $this->repository->findOrCreate(
            name: 'AT&T',
            technologies: ['Fiber']
        );

        // Add Mobile technology
        $updatedProvider = $this->repository->findOrCreate(
            name: 'AT&T',
            technologies: ['Mobile', 'DSL']
        );

        $this->assertEquals($firstProvider->getId(), $updatedProvider->getId());
        $this->assertContains('Fiber', $updatedProvider->getTechnologies());
        $this->assertContains('Mobile', $updatedProvider->getTechnologies());
        $this->assertContains('DSL', $updatedProvider->getTechnologies());
        $this->assertCount(3, $updatedProvider->getTechnologies());
    }

    public function test_find_or_create_with_name_normalization(): void
    {
        // Create with normalized name variation
        $firstProvider = $this->repository->findOrCreate(
            name: 'AT & T',
            technologies: ['Fiber']
        );

        // Use different variation - should find same provider
        $secondProvider = $this->repository->findOrCreate(
            name: 'ATT',
            technologies: ['Mobile']
        );

        $this->assertEquals($firstProvider->getId(), $secondProvider->getId());
        $this->assertEquals('AT&T', $secondProvider->getName());
        $this->assertContains('Fiber', $secondProvider->getTechnologies());
        $this->assertContains('Mobile', $secondProvider->getTechnologies());
    }

    public function test_find_by_id(): void
    {
        $createdProvider = $this->repository->create(
            name: 'Test Provider',
            technologies: ['Cable']
        );

        $foundProvider = $this->repository->findById($createdProvider->getId());

        $this->assertNotNull($foundProvider);
        $this->assertEquals($createdProvider->getId(), $foundProvider->getId());
        $this->assertEquals('Test Provider', $foundProvider->getName());
    }

    public function test_find_by_id_returns_null_for_nonexistent(): void
    {
        $provider = $this->repository->findById(99999);
        $this->assertNull($provider);
    }

    public function test_find_by_name(): void
    {
        $this->repository->create(
            name: 'Unique Provider',
            technologies: ['Satellite']
        );

        $foundProvider = $this->repository->findByName('Unique Provider');

        $this->assertNotNull($foundProvider);
        $this->assertEquals('Unique Provider', $foundProvider->getName());
    }

    public function test_find_by_slug(): void
    {
        $this->repository->create(
            name: 'Cox Communications',
            technologies: ['Cable']
        );

        $foundProvider = $this->repository->findBySlug('cox-communications');

        $this->assertNotNull($foundProvider);
        $this->assertEquals('Cox Communications', $foundProvider->getName());
        $this->assertEquals('cox-communications', $foundProvider->getSlug());
    }

    public function test_find_by_technology(): void
    {
        $this->repository->create(
            name: 'Fiber Provider',
            technologies: ['Fiber', 'Cable']
        );
        
        $this->repository->create(
            name: 'Mobile Provider',
            technologies: ['Mobile']
        );
        
        $this->repository->create(
            name: 'Multi Provider',
            technologies: ['Fiber', 'Mobile', 'DSL']
        );

        $fiberProviders = $this->repository->findByTechnology('Fiber');
        
        $this->assertCount(2, $fiberProviders);
        
        $names = array_map(fn($p) => $p->getName(), $fiberProviders);
        $this->assertContains('Fiber Provider', $names);
        $this->assertContains('Multi Provider', $names);
    }

    public function test_find_all_paginated_with_search(): void
    {
        $this->repository->create(
            name: 'AT&T',
            technologies: ['Fiber'],
        );
        
        $this->repository->create(
            name: 'Verizon',
            technologies: ['Mobile']
        );
        
        $this->repository->create(
            name: 'T-Mobile',
            technologies: ['Mobile']
        );

        // Search by name
        $result = $this->repository->findAllPaginated(
            page: 1,
            perPage: 10,
            search: 'AT&T'
        );

        $this->assertEquals(1, $result['total']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('AT&T', $result['data'][0]->getName());
    }

    public function test_find_all_paginated_with_technology_filter(): void
    {
        $this->repository->create(
            name: 'Provider A',
            technologies: ['Fiber', 'Cable']
        );
        
        $this->repository->create(
            name: 'Provider B',
            technologies: ['Mobile']
        );
        
        $this->repository->create(
            name: 'Provider C',
            technologies: ['Fiber', 'DSL']
        );

        $result = $this->repository->findAllPaginated(
            page: 1,
            perPage: 10,
            technology: 'Fiber'
        );

        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['data']);
        
        $names = array_map(fn($p) => $p->getName(), $result['data']);
        $this->assertContains('Provider A', $names);
        $this->assertContains('Provider C', $names);
    }

    public function test_update_provider(): void
    {
        $provider = $this->repository->create(
            name: 'Original Name',
            technologies: ['Cable']
        );

        $updatedProvider = $this->repository->update(
            id: $provider->getId(),
            name: 'Updated Name',
            website: 'https://updated.com',
            technologies: ['Fiber', 'Mobile']
        );

        $this->assertEquals('Updated Name', $updatedProvider->getName());
        $this->assertEquals('updated-name', $updatedProvider->getSlug());
        $this->assertEquals('https://updated.com', $updatedProvider->getWebsite());
        $this->assertEquals(['Fiber', 'Mobile'], $updatedProvider->getTechnologies());
    }

    public function test_add_technology(): void
    {
        $provider = $this->repository->create(
            name: 'Test Provider',
            technologies: ['Cable']
        );

        $this->repository->addTechnology($provider->getId(), 'Fiber');

        $updatedProvider = $this->repository->findById($provider->getId());
        $this->assertContains('Cable', $updatedProvider->getTechnologies());
        $this->assertContains('Fiber', $updatedProvider->getTechnologies());
        $this->assertCount(2, $updatedProvider->getTechnologies());
    }

    public function test_remove_technology(): void
    {
        $provider = $this->repository->create(
            name: 'Test Provider',
            technologies: ['Cable', 'Fiber', 'Mobile']
        );

        $this->repository->removeTechnology($provider->getId(), 'Fiber');

        $updatedProvider = $this->repository->findById($provider->getId());
        $this->assertContains('Cable', $updatedProvider->getTechnologies());
        $this->assertContains('Mobile', $updatedProvider->getTechnologies());
        $this->assertNotContains('Fiber', $updatedProvider->getTechnologies());
        $this->assertCount(2, $updatedProvider->getTechnologies());
    }

    public function test_delete_provider(): void
    {
        $provider = $this->repository->create(
            name: 'To Delete',
            technologies: ['DSL']
        );

        $providerId = $provider->getId();
        $this->repository->delete($providerId);

        $deletedProvider = $this->repository->findById($providerId);
        $this->assertNull($deletedProvider);
    }

    public function test_unique_slug_generation(): void
    {
        // Create first provider
        $first = $this->repository->create(
            name: 'Test Provider One',
            technologies: ['Cable']
        );

        // Create second with similar name but different enough to avoid name collision
        $second = $this->repository->create(
            name: 'Test Provider Two', 
            technologies: ['Fiber']
        );

        // Create third with same base but different name
        $third = $this->repository->create(
            name: 'Test Provider Three',
            technologies: ['Mobile']
        );

        $this->assertEquals('test-provider-one', $first->getSlug());
        $this->assertEquals('test-provider-two', $second->getSlug());
        $this->assertEquals('test-provider-three', $third->getSlug());
        
        // Test that each provider has a unique name and slug
        $this->assertNotEquals($first->getName(), $second->getName());
        $this->assertNotEquals($first->getSlug(), $second->getSlug());
        $this->assertNotEquals($second->getName(), $third->getName());
        $this->assertNotEquals($second->getSlug(), $third->getSlug());
    }
}
