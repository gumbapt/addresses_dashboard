<?php

namespace Tests\Unit;

use App\Domain\Entities\DomainGroup;
use DateTime;
use Tests\TestCase;

class DomainGroupEntityTest extends TestCase
{
    public function test_can_create_domain_group_entity()
    {
        $group = new DomainGroup(
            id: 1,
            name: 'Test Group',
            slug: 'test-group',
            description: 'Test description',
            isActive: true,
            settings: ['key' => 'value'],
            maxDomains: 10,
            createdBy: 1,
            updatedBy: null,
            createdAt: new DateTime('2025-11-08 12:00:00'),
            updatedAt: new DateTime('2025-11-08 12:00:00')
        );

        $this->assertEquals(1, $group->id);
        $this->assertEquals('Test Group', $group->name);
        $this->assertEquals('test-group', $group->slug);
        $this->assertEquals('Test description', $group->description);
        $this->assertTrue($group->isActive);
        $this->assertEquals(['key' => 'value'], $group->settings);
        $this->assertEquals(10, $group->maxDomains);
        $this->assertEquals(1, $group->createdBy);
        $this->assertNull($group->updatedBy);
    }

    public function test_entity_to_array()
    {
        $group = new DomainGroup(
            id: 1,
            name: 'Test Group',
            slug: 'test-group',
            description: 'Test description',
            isActive: true,
            settings: ['key' => 'value'],
            maxDomains: 10,
            createdBy: 1,
            updatedBy: null,
            createdAt: new DateTime('2025-11-08 12:00:00'),
            updatedAt: new DateTime('2025-11-08 12:00:00')
        );

        $array = $group->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('Test Group', $array['name']);
        $this->assertEquals('test-group', $array['slug']);
        $this->assertEquals(10, $array['max_domains']);
        $this->assertEquals('2025-11-08 12:00:00', $array['created_at']);
    }

    public function test_entity_to_dto()
    {
        $group = new DomainGroup(
            id: 1,
            name: 'Test Group',
            slug: 'test-group',
            description: 'Test description',
            isActive: true,
            settings: ['key' => 'value'],
            maxDomains: 10,
            createdBy: 1
        );

        $dto = $group->toDto();

        $this->assertEquals(1, $dto->id);
        $this->assertEquals('Test Group', $dto->name);
        $this->assertEquals('test-group', $dto->slug);
        $this->assertEquals(10, $dto->max_domains);
    }

    public function test_has_max_domains_limit_returns_true_when_has_limit()
    {
        $group = new DomainGroup(
            id: 1,
            name: 'Test Group',
            slug: 'test-group',
            maxDomains: 10
        );

        $this->assertTrue($group->hasMaxDomainsLimit());
    }

    public function test_has_max_domains_limit_returns_false_when_unlimited()
    {
        $group = new DomainGroup(
            id: 1,
            name: 'Test Group',
            slug: 'test-group',
            maxDomains: null
        );

        $this->assertFalse($group->hasMaxDomainsLimit());
    }

    public function test_is_unlimited_returns_true_when_no_limit()
    {
        $group = new DomainGroup(
            id: 1,
            name: 'Test Group',
            slug: 'test-group',
            maxDomains: null
        );

        $this->assertTrue($group->isUnlimited());
    }

    public function test_is_unlimited_returns_false_when_has_limit()
    {
        $group = new DomainGroup(
            id: 1,
            name: 'Test Group',
            slug: 'test-group',
            maxDomains: 10
        );

        $this->assertFalse($group->isUnlimited());
    }

    public function test_can_create_with_minimal_data()
    {
        $group = new DomainGroup(
            id: 1,
            name: 'Minimal Group',
            slug: 'minimal-group'
        );

        $this->assertEquals(1, $group->id);
        $this->assertEquals('Minimal Group', $group->name);
        $this->assertEquals('minimal-group', $group->slug);
        $this->assertNull($group->description);
        $this->assertTrue($group->isActive);
        $this->assertNull($group->settings);
        $this->assertNull($group->maxDomains);
    }
}

