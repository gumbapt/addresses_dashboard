<?php

namespace Tests\Unit;

use App\Application\UseCases\DomainGroup\AddDomainsToGroupUseCase;
use App\Application\UseCases\DomainGroup\RemoveDomainsFromGroupUseCase;
use App\Domain\Entities\DomainGroup;
use App\Domain\Exceptions\NotFoundException;
use App\Domain\Exceptions\ValidationException;
use App\Domain\Repositories\DomainGroupRepositoryInterface;
use App\Domain\Repositories\DomainRepositoryInterface;
use Tests\TestCase;
use Mockery;

class DomainGroupBatchOperationsTest extends TestCase
{
    private $domainGroupRepo;
    private $domainRepo;
    private $addUseCase;
    private $removeUseCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->domainGroupRepo = Mockery::mock(DomainGroupRepositoryInterface::class);
        $this->domainRepo = Mockery::mock(DomainRepositoryInterface::class);
        
        $this->addUseCase = new AddDomainsToGroupUseCase($this->domainGroupRepo, $this->domainRepo);
        $this->removeUseCase = new RemoveDomainsFromGroupUseCase($this->domainGroupRepo, $this->domainRepo);
    }

    /** @test */
    public function can_add_domains_to_group()
    {
        // Arrange
        $groupId = 1;
        $domainIds = [1, 2, 3];
        
        $group = new DomainGroup(
            id: $groupId,
            name: 'Test Group',
            slug: 'test-group',
            isActive: true,
            maxDomains: null
        );
        
        $this->domainGroupRepo->shouldReceive('findById')->with($groupId)->andReturn($group);
        $this->domainRepo->shouldReceive('findByIds')->with($domainIds)->andReturn([1, 2, 3]);
        $this->domainGroupRepo->shouldReceive('getDomainsInOtherGroups')->with($domainIds, $groupId)->andReturn([]);
        $this->domainGroupRepo->shouldReceive('getDomainsCount')->with($groupId)->andReturn(0);
        $this->domainGroupRepo->shouldReceive('addDomains')->with($groupId, $domainIds)->andReturn(3);
        
        // Act
        $result = $this->addUseCase->execute($groupId, $domainIds);
        
        // Assert
        $this->assertEquals(3, $result['domains_added']);
        $this->assertEquals(0, $result['domains_moved']);
        $this->assertEquals(3, $result['total_requested']);
        $this->assertEquals($groupId, $result['group_id']);
        $this->assertEquals('Test Group', $result['group_name']);
    }

    /** @test */
    public function cannot_add_domains_when_group_not_found()
    {
        // Arrange
        $this->domainGroupRepo->shouldReceive('findById')->with(999)->andReturn(null);
        
        // Act & Assert
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Domain group with ID 999 not found.');
        
        $this->addUseCase->execute(999, [1, 2]);
    }

    /** @test */
    public function cannot_add_empty_domains_array()
    {
        // Arrange
        $group = new DomainGroup(
            id: 1,
            name: 'Test Group',
            slug: 'test-group',
            isActive: true
        );
        
        $this->domainGroupRepo->shouldReceive('findById')->with(1)->andReturn($group);
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Domain IDs array cannot be empty.');
        
        $this->addUseCase->execute(1, []);
    }

    /** @test */
    public function cannot_add_invalid_domain_ids()
    {
        // Arrange
        $groupId = 1;
        $domainIds = [1, 2, 999];
        
        $group = new DomainGroup(
            id: $groupId,
            name: 'Test Group',
            slug: 'test-group',
            isActive: true
        );
        
        $this->domainGroupRepo->shouldReceive('findById')->with($groupId)->andReturn($group);
        $this->domainRepo->shouldReceive('findByIds')->with($domainIds)->andReturn([1, 2]); // Only 2 found
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more domain IDs are invalid.');
        
        $this->addUseCase->execute($groupId, $domainIds);
    }

    /** @test */
    public function cannot_add_domains_exceeding_group_limit()
    {
        // Arrange
        $groupId = 1;
        $domainIds = [1, 2, 3];
        
        $group = new DomainGroup(
            id: $groupId,
            name: 'Limited Group',
            slug: 'limited-group',
            isActive: true,
            maxDomains: 5
        );
        
        $this->domainGroupRepo->shouldReceive('findById')->with($groupId)->andReturn($group);
        $this->domainRepo->shouldReceive('findByIds')->with($domainIds)->andReturn([1, 2, 3]);
        $this->domainGroupRepo->shouldReceive('getDomainsInOtherGroups')->with($domainIds, $groupId)->andReturn([]);
        $this->domainGroupRepo->shouldReceive('getDomainsCount')->with($groupId)->andReturn(4); // Already has 4
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('only has 1 available slots');
        
        $this->addUseCase->execute($groupId, $domainIds);
    }

    /** @test */
    public function can_add_domains_to_group_with_limit_when_space_available()
    {
        // Arrange
        $groupId = 1;
        $domainIds = [1, 2];
        
        $group = new DomainGroup(
            id: $groupId,
            name: 'Limited Group',
            slug: 'limited-group',
            isActive: true,
            maxDomains: 10
        );
        
        $this->domainGroupRepo->shouldReceive('findById')->with($groupId)->andReturn($group);
        $this->domainRepo->shouldReceive('findByIds')->with($domainIds)->andReturn([1, 2]);
        $this->domainGroupRepo->shouldReceive('getDomainsInOtherGroups')->with($domainIds, $groupId)->andReturn([]);
        $this->domainGroupRepo->shouldReceive('getDomainsCount')->with($groupId)->andReturn(5);
        $this->domainGroupRepo->shouldReceive('addDomains')->with($groupId, $domainIds)->andReturn(2);
        
        // Act
        $result = $this->addUseCase->execute($groupId, $domainIds);
        
        // Assert
        $this->assertEquals(2, $result['domains_added']);
        $this->assertEquals(0, $result['domains_moved']);
        $this->assertEquals('Limited Group', $result['group_name']);
    }

    /** @test */
    public function can_remove_domains_from_group()
    {
        // Arrange
        $groupId = 1;
        $domainIds = [1, 2];
        
        $group = new DomainGroup(
            id: $groupId,
            name: 'Test Group',
            slug: 'test-group',
            isActive: true
        );
        
        $this->domainGroupRepo->shouldReceive('findById')->with($groupId)->andReturn($group);
        $this->domainRepo->shouldReceive('findByIds')->with($domainIds)->andReturn([1, 2]);
        $this->domainGroupRepo->shouldReceive('removeDomains')->with($groupId, $domainIds)->andReturn(2);
        
        // Act
        $result = $this->removeUseCase->execute($groupId, $domainIds);
        
        // Assert
        $this->assertEquals(2, $result['removed']);
        $this->assertEquals(2, $result['total_requested']);
        $this->assertEquals($groupId, $result['group_id']);
        $this->assertEquals('Test Group', $result['group_name']);
    }

    /** @test */
    public function cannot_remove_domains_when_group_not_found()
    {
        // Arrange
        $this->domainGroupRepo->shouldReceive('findById')->with(999)->andReturn(null);
        
        // Act & Assert
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Domain group with ID 999 not found.');
        
        $this->removeUseCase->execute(999, [1, 2]);
    }

    /** @test */
    public function cannot_remove_empty_domains_array()
    {
        // Arrange
        $group = new DomainGroup(
            id: 1,
            name: 'Test Group',
            slug: 'test-group',
            isActive: true
        );
        
        $this->domainGroupRepo->shouldReceive('findById')->with(1)->andReturn($group);
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Domain IDs array cannot be empty.');
        
        $this->removeUseCase->execute(1, []);
    }

    /** @test */
    public function cannot_remove_invalid_domain_ids()
    {
        // Arrange
        $groupId = 1;
        $domainIds = [1, 2, 999];
        
        $group = new DomainGroup(
            id: $groupId,
            name: 'Test Group',
            slug: 'test-group',
            isActive: true
        );
        
        $this->domainGroupRepo->shouldReceive('findById')->with($groupId)->andReturn($group);
        $this->domainRepo->shouldReceive('findByIds')->with($domainIds)->andReturn([1, 2]); // Only 2 found
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more domain IDs are invalid.');
        
        $this->removeUseCase->execute($groupId, $domainIds);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

