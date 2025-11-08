<?php

namespace Tests\Unit;

use App\Application\UseCases\DomainGroup\CreateDomainGroupUseCase;
use App\Application\UseCases\DomainGroup\UpdateDomainGroupUseCase;
use App\Application\UseCases\DomainGroup\DeleteDomainGroupUseCase;
use App\Application\UseCases\DomainGroup\GetAllDomainGroupsUseCase;
use App\Application\UseCases\DomainGroup\GetDomainGroupByIdUseCase;
use App\Domain\Exceptions\NotFoundException;
use App\Domain\Exceptions\ValidationException;
use App\Infrastructure\Repositories\DomainGroupRepository;
use App\Models\DomainGroup;
use App\Models\Domain;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainGroupUseCasesTest extends TestCase
{
    use RefreshDatabase;

    private DomainGroupRepository $repository;
    private CreateDomainGroupUseCase $createUseCase;
    private UpdateDomainGroupUseCase $updateUseCase;
    private DeleteDomainGroupUseCase $deleteUseCase;
    private GetAllDomainGroupsUseCase $getAllUseCase;
    private GetDomainGroupByIdUseCase $getByIdUseCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new DomainGroupRepository();
        $this->createUseCase = new CreateDomainGroupUseCase($this->repository);
        $this->updateUseCase = new UpdateDomainGroupUseCase($this->repository);
        $this->deleteUseCase = new DeleteDomainGroupUseCase($this->repository);
        $this->getAllUseCase = new GetAllDomainGroupsUseCase($this->repository);
        $this->getByIdUseCase = new GetDomainGroupByIdUseCase($this->repository);
    }

    public function test_create_use_case_creates_domain_group()
    {
        $admin = Admin::factory()->create(['is_super_admin' => true]);
        
        $group = $this->createUseCase->execute(
            name: 'Test Group',
            slug: 'test-group',
            description: 'Test description',
            isActive: true,
            settings: ['test' => true],
            maxDomains: 10,
            createdBy: $admin->id
        );

        $this->assertEquals('Test Group', $group->name);
        $this->assertDatabaseHas('domain_groups', ['name' => 'Test Group']);
    }

    public function test_get_by_id_use_case_finds_group()
    {
        $model = DomainGroup::factory()->create(['name' => 'Find Me']);
        
        $group = $this->getByIdUseCase->execute($model->id);

        $this->assertEquals('Find Me', $group->name);
        $this->assertEquals($model->id, $group->id);
    }

    public function test_get_by_id_use_case_throws_exception_when_not_found()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Domain group with ID 999 not found');

        $this->getByIdUseCase->execute(999);
    }

    public function test_get_all_use_case_returns_all_groups()
    {
        DomainGroup::factory()->count(5)->create();
        
        $groups = $this->getAllUseCase->execute();

        $this->assertCount(5, $groups);
    }

    public function test_get_all_paginated_use_case()
    {
        DomainGroup::factory()->count(25)->create();
        
        $result = $this->getAllUseCase->executePaginated(page: 1, perPage: 10);

        $this->assertCount(10, $result['data']);
        $this->assertEquals(25, $result['total']);
    }

    public function test_get_active_use_case_returns_only_active_groups()
    {
        DomainGroup::factory()->count(3)->create(['is_active' => true]);
        DomainGroup::factory()->count(2)->create(['is_active' => false]);
        
        $groups = $this->getAllUseCase->executeActive();

        $this->assertCount(3, $groups);
    }

    public function test_update_use_case_updates_domain_group()
    {
        $model = DomainGroup::factory()->create(['name' => 'Old Name']);
        $admin = Admin::factory()->create();
        
        $group = $this->updateUseCase->execute(
            id: $model->id,
            name: 'Updated Name',
            maxDomains: 20,
            updatedBy: $admin->id
        );

        $this->assertEquals('Updated Name', $group->name);
        $this->assertEquals(20, $group->maxDomains);
        $this->assertDatabaseHas('domain_groups', [
            'id' => $model->id,
            'name' => 'Updated Name',
            'max_domains' => 20,
        ]);
    }

    public function test_delete_use_case_deletes_empty_group()
    {
        $model = DomainGroup::factory()->create();
        
        $deleted = $this->deleteUseCase->execute($model->id);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('domain_groups', ['id' => $model->id]);
    }

    public function test_delete_use_case_throws_exception_when_group_has_domains()
    {
        $group = DomainGroup::factory()->create();
        Domain::factory()->count(2)->create(['domain_group_id' => $group->id]);
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot delete domain group with 2 associated domains');

        $this->deleteUseCase->execute($group->id);
    }

    public function test_can_create_unlimited_group()
    {
        $admin = Admin::factory()->create(['is_super_admin' => true]);
        
        $group = $this->createUseCase->execute(
            name: 'Unlimited Group',
            slug: 'unlimited-group',
            maxDomains: null,
            createdBy: $admin->id
        );

        $this->assertNull($group->maxDomains);
        $this->assertTrue($group->isUnlimited());
    }

    public function test_repository_enforces_unique_slugs()
    {
        $admin = Admin::factory()->create(['is_super_admin' => true]);
        
        $group1 = $this->createUseCase->execute(
            name: 'Group 1',
            slug: 'same-slug',
            createdBy: $admin->id
        );
        
        $group2 = $this->createUseCase->execute(
            name: 'Group 2',
            slug: 'same-slug',
            createdBy: $admin->id
        );

        $this->assertEquals('same-slug', $group1->slug);
        $this->assertEquals('same-slug-1', $group2->slug);
    }
}

