<?php

namespace Tests\Unit;

use App\Domain\Repositories\DomainGroupRepositoryInterface;
use App\Infrastructure\Repositories\DomainGroupRepository;
use App\Models\DomainGroup;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainGroupRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DomainGroupRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DomainGroupRepository();
    }

    public function test_can_create_domain_group()
    {
        $admin = Admin::factory()->create(['is_super_admin' => true]);
        
        $group = $this->repository->create(
            name: 'Test Group',
            slug: 'test-group',
            description: 'Test description',
            isActive: true,
            settings: ['key' => 'value'],
            maxDomains: 10,
            createdBy: $admin->id
        );

        $this->assertEquals('Test Group', $group->name);
        $this->assertEquals('test-group', $group->slug);
        $this->assertEquals(10, $group->maxDomains);
        
        $this->assertDatabaseHas('domain_groups', [
            'name' => 'Test Group',
            'slug' => 'test-group',
            'max_domains' => 10,
        ]);
    }

    public function test_can_find_by_id()
    {
        $model = DomainGroup::factory()->create(['name' => 'Test Group']);
        
        $group = $this->repository->findById($model->id);

        $this->assertNotNull($group);
        $this->assertEquals('Test Group', $group->name);
        $this->assertEquals($model->id, $group->id);
    }

    public function test_find_by_id_returns_null_when_not_found()
    {
        $group = $this->repository->findById(999);

        $this->assertNull($group);
    }

    public function test_can_find_by_slug()
    {
        $model = DomainGroup::factory()->create(['slug' => 'test-slug']);
        
        $group = $this->repository->findBySlug('test-slug');

        $this->assertNotNull($group);
        $this->assertEquals('test-slug', $group->slug);
    }

    public function test_can_find_all()
    {
        DomainGroup::factory()->count(3)->create();
        
        $groups = $this->repository->findAll();

        $this->assertCount(3, $groups);
        $this->assertContainsOnlyInstancesOf(\App\Domain\Entities\DomainGroup::class, $groups);
    }

    public function test_can_find_all_paginated()
    {
        DomainGroup::factory()->count(25)->create();
        
        $result = $this->repository->findAllPaginated(page: 1, perPage: 10);

        $this->assertCount(10, $result['data']);
        $this->assertEquals(25, $result['total']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(3, $result['last_page']);
    }

    public function test_can_search_domain_groups()
    {
        DomainGroup::factory()->create(['name' => 'Production Group']);
        DomainGroup::factory()->create(['name' => 'Staging Group']);
        DomainGroup::factory()->create(['name' => 'Development Group']);
        
        $result = $this->repository->findAllPaginated(
            page: 1,
            perPage: 10,
            search: 'Production'
        );

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Production Group', $result['data'][0]->name);
    }

    public function test_can_filter_by_active_status()
    {
        DomainGroup::factory()->count(3)->create(['is_active' => true]);
        DomainGroup::factory()->count(2)->create(['is_active' => false]);
        
        $result = $this->repository->findAllPaginated(
            page: 1,
            perPage: 10,
            isActive: true
        );

        $this->assertCount(3, $result['data']);
    }

    public function test_can_update_domain_group()
    {
        $model = DomainGroup::factory()->create(['name' => 'Old Name']);
        $admin = Admin::factory()->create();
        
        $group = $this->repository->update(
            id: $model->id,
            name: 'New Name',
            updatedBy: $admin->id
        );

        $this->assertEquals('New Name', $group->name);
        $this->assertEquals($admin->id, $group->updatedBy);
        
        $this->assertDatabaseHas('domain_groups', [
            'id' => $model->id,
            'name' => 'New Name',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_can_delete_domain_group()
    {
        $model = DomainGroup::factory()->create();
        
        $deleted = $this->repository->delete($model->id);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('domain_groups', ['id' => $model->id]);
    }

    public function test_can_find_active_groups()
    {
        DomainGroup::factory()->count(3)->create(['is_active' => true]);
        DomainGroup::factory()->count(2)->create(['is_active' => false]);
        
        $groups = $this->repository->findActive();

        $this->assertCount(3, $groups);
    }

    public function test_can_get_domains_count()
    {
        $group = DomainGroup::factory()->create();
        \App\Models\Domain::factory()->count(5)->create(['domain_group_id' => $group->id]);
        
        $count = $this->repository->getDomainsCount($group->id);

        $this->assertEquals(5, $count);
    }

    public function test_has_reached_max_domains_returns_true_when_limit_reached()
    {
        $group = DomainGroup::factory()->create(['max_domains' => 3]);
        \App\Models\Domain::factory()->count(3)->create(['domain_group_id' => $group->id]);
        
        $hasReached = $this->repository->hasReachedMaxDomains($group->id);

        $this->assertTrue($hasReached);
    }

    public function test_has_reached_max_domains_returns_false_when_below_limit()
    {
        $group = DomainGroup::factory()->create(['max_domains' => 5]);
        \App\Models\Domain::factory()->count(2)->create(['domain_group_id' => $group->id]);
        
        $hasReached = $this->repository->hasReachedMaxDomains($group->id);

        $this->assertFalse($hasReached);
    }

    public function test_has_reached_max_domains_returns_false_when_unlimited()
    {
        $group = DomainGroup::factory()->create(['max_domains' => null]);
        \App\Models\Domain::factory()->count(100)->create(['domain_group_id' => $group->id]);
        
        $hasReached = $this->repository->hasReachedMaxDomains($group->id);

        $this->assertFalse($hasReached);
    }

    public function test_slug_uniqueness_is_enforced()
    {
        $admin = Admin::factory()->create(['is_super_admin' => true]);
        
        $this->repository->create(
            name: 'Group 1',
            slug: 'test-group',
            createdBy: $admin->id
        );
        
        // Criar outro com mesmo slug base (deve adicionar sufixo)
        $group2 = $this->repository->create(
            name: 'Group 2',
            slug: 'test-group',
            createdBy: $admin->id
        );

        $this->assertEquals('test-group-1', $group2->slug);
    }
}

