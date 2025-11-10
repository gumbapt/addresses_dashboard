<?php

namespace Tests\Unit;

use App\Application\UseCases\DomainGroup\AddDomainsToGroupUseCase;
use App\Domain\Entities\DomainGroup;
use App\Domain\Repositories\DomainGroupRepositoryInterface;
use App\Domain\Repositories\DomainRepositoryInterface;
use Tests\TestCase;
use Mockery;

class DomainGroupMoveDomainsTest extends TestCase
{
    private $domainGroupRepo;
    private $domainRepo;
    private $addUseCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->domainGroupRepo = Mockery::mock(DomainGroupRepositoryInterface::class);
        $this->domainRepo = Mockery::mock(DomainRepositoryInterface::class);
        
        $this->addUseCase = new AddDomainsToGroupUseCase($this->domainGroupRepo, $this->domainRepo);
    }

    /** @test */
    public function detects_when_domains_are_already_in_other_groups()
    {
        // Arrange
        $groupId = 2;
        $domainIds = [1, 2, 3];
        
        $group = new DomainGroup(
            id: $groupId,
            name: 'Target Group',
            slug: 'target-group',
            isActive: true,
            maxDomains: null
        );
        
        // Simular que os domínios 1 e 2 já estão em outro grupo
        $domainsInOtherGroups = [
            [
                'domain_id' => 1,
                'domain_name' => 'domain1.com',
                'current_group_id' => 1,
                'current_group_name' => 'Source Group',
            ],
            [
                'domain_id' => 2,
                'domain_name' => 'domain2.com',
                'current_group_id' => 1,
                'current_group_name' => 'Source Group',
            ],
        ];
        
        $this->domainGroupRepo->shouldReceive('findById')->with($groupId)->andReturn($group);
        $this->domainRepo->shouldReceive('findByIds')->with($domainIds)->andReturn([1, 2, 3]);
        $this->domainGroupRepo->shouldReceive('getDomainsInOtherGroups')->with($domainIds, $groupId)->andReturn($domainsInOtherGroups);
        $this->domainGroupRepo->shouldReceive('getDomainsCount')->with($groupId)->andReturn(0);
        $this->domainGroupRepo->shouldReceive('addDomains')->with($groupId, $domainIds)->andReturn(3);
        
        // Act
        $result = $this->addUseCase->execute($groupId, $domainIds);
        
        // Assert
        $this->assertEquals(3, $result['total_updated']);
        $this->assertEquals(1, $result['domains_added']); // Apenas 1 novo (domínio 3)
        $this->assertEquals(2, $result['domains_moved']); // 2 movidos (domínios 1 e 2)
        $this->assertCount(2, $result['moved_from']);
        $this->assertEquals('Source Group', $result['moved_from'][0]['current_group_name']);
    }

    /** @test */
    public function all_domains_are_new_when_none_in_other_groups()
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
        $this->domainGroupRepo->shouldReceive('getDomainsInOtherGroups')->with($domainIds, $groupId)->andReturn([]); // Nenhum em outro grupo
        $this->domainGroupRepo->shouldReceive('getDomainsCount')->with($groupId)->andReturn(0);
        $this->domainGroupRepo->shouldReceive('addDomains')->with($groupId, $domainIds)->andReturn(3);
        
        // Act
        $result = $this->addUseCase->execute($groupId, $domainIds);
        
        // Assert
        $this->assertEquals(3, $result['total_updated']);
        $this->assertEquals(3, $result['domains_added']); // Todos são novos
        $this->assertEquals(0, $result['domains_moved']); // Nenhum movido
        $this->assertEmpty($result['moved_from']);
    }

    /** @test */
    public function all_domains_are_moved_when_all_in_other_groups()
    {
        // Arrange
        $groupId = 2;
        $domainIds = [1, 2, 3];
        
        $group = new DomainGroup(
            id: $groupId,
            name: 'Target Group',
            slug: 'target-group',
            isActive: true,
            maxDomains: null
        );
        
        // Todos os domínios estão em outro grupo
        $domainsInOtherGroups = [
            ['domain_id' => 1, 'domain_name' => 'd1.com', 'current_group_id' => 1, 'current_group_name' => 'Group 1'],
            ['domain_id' => 2, 'domain_name' => 'd2.com', 'current_group_id' => 1, 'current_group_name' => 'Group 1'],
            ['domain_id' => 3, 'domain_name' => 'd3.com', 'current_group_id' => 1, 'current_group_name' => 'Group 1'],
        ];
        
        $this->domainGroupRepo->shouldReceive('findById')->with($groupId)->andReturn($group);
        $this->domainRepo->shouldReceive('findByIds')->with($domainIds)->andReturn([1, 2, 3]);
        $this->domainGroupRepo->shouldReceive('getDomainsInOtherGroups')->with($domainIds, $groupId)->andReturn($domainsInOtherGroups);
        $this->domainGroupRepo->shouldReceive('getDomainsCount')->with($groupId)->andReturn(5);
        $this->domainGroupRepo->shouldReceive('addDomains')->with($groupId, $domainIds)->andReturn(3);
        
        // Act
        $result = $this->addUseCase->execute($groupId, $domainIds);
        
        // Assert
        $this->assertEquals(3, $result['total_updated']);
        $this->assertEquals(0, $result['domains_added']); // Nenhum novo
        $this->assertEquals(3, $result['domains_moved']); // Todos movidos
        $this->assertCount(3, $result['moved_from']);
    }

    /** @test */
    public function limit_only_considers_new_domains_not_moved_ones()
    {
        // Arrange
        $groupId = 1;
        $domainIds = [1, 2, 3]; // 2 movidos + 1 novo
        
        $group = new DomainGroup(
            id: $groupId,
            name: 'Limited Group',
            slug: 'limited-group',
            isActive: true,
            maxDomains: 10
        );
        
        // 2 domínios já estão em outro grupo (serão movidos)
        $domainsInOtherGroups = [
            ['domain_id' => 1, 'domain_name' => 'd1.com', 'current_group_id' => 2, 'current_group_name' => 'Group 2'],
            ['domain_id' => 2, 'domain_name' => 'd2.com', 'current_group_id' => 2, 'current_group_name' => 'Group 2'],
        ];
        
        $this->domainGroupRepo->shouldReceive('findById')->with($groupId)->andReturn($group);
        $this->domainRepo->shouldReceive('findByIds')->with($domainIds)->andReturn([1, 2, 3]);
        $this->domainGroupRepo->shouldReceive('getDomainsInOtherGroups')->with($domainIds, $groupId)->andReturn($domainsInOtherGroups);
        $this->domainGroupRepo->shouldReceive('getDomainsCount')->with($groupId)->andReturn(9); // Já tem 9
        $this->domainGroupRepo->shouldReceive('addDomains')->with($groupId, $domainIds)->andReturn(3);
        
        // Act - Deve passar porque apenas 1 domínio é novo (9 + 1 = 10, dentro do limite)
        $result = $this->addUseCase->execute($groupId, $domainIds);
        
        // Assert
        $this->assertEquals(1, $result['domains_added']); // 1 novo
        $this->assertEquals(2, $result['domains_moved']); // 2 movidos
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

