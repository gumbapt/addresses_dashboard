<?php

namespace Tests\Unit;

use App\Models\DomainGroup;
use App\Models\Domain;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainGroupModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_domain_group()
    {
        $group = DomainGroup::factory()->create([
            'name' => 'Test Group',
            'max_domains' => 10,
        ]);

        $this->assertDatabaseHas('domain_groups', [
            'name' => 'Test Group',
            'max_domains' => 10,
        ]);
    }

    public function test_slug_is_generated_automatically()
    {
        $group = DomainGroup::create([
            'name' => 'My Test Group',
            'is_active' => true,
        ]);

        $this->assertEquals('my-test-group', $group->slug);
    }

    public function test_has_many_domains_relationship()
    {
        $group = DomainGroup::factory()->create();
        $domains = Domain::factory()->count(3)->create(['domain_group_id' => $group->id]);

        $this->assertCount(3, $group->domains);
        $this->assertEquals($domains->first()->id, $group->domains->first()->id);
    }

    public function test_belongs_to_creator_relationship()
    {
        $admin = Admin::factory()->create(['name' => 'Creator Admin']);
        $group = DomainGroup::factory()->create(['created_by' => $admin->id]);

        $this->assertEquals('Creator Admin', $group->creator->name);
        $this->assertEquals($admin->id, $group->creator->id);
    }

    public function test_belongs_to_updater_relationship()
    {
        $admin = Admin::factory()->create(['name' => 'Updater Admin']);
        $group = DomainGroup::factory()->create([
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertEquals('Updater Admin', $group->updater->name);
    }

    public function test_active_scope()
    {
        DomainGroup::factory()->count(3)->create(['is_active' => true]);
        DomainGroup::factory()->count(2)->create(['is_active' => false]);

        $activeGroups = DomainGroup::active()->get();

        $this->assertCount(3, $activeGroups);
    }

    public function test_with_domains_scope()
    {
        $groupWithDomains = DomainGroup::factory()->create();
        Domain::factory()->count(2)->create(['domain_group_id' => $groupWithDomains->id]);
        
        $emptyGroup = DomainGroup::factory()->create();

        $groupsWithDomains = DomainGroup::withDomains()->get();

        $this->assertCount(1, $groupsWithDomains);
        $this->assertEquals($groupWithDomains->id, $groupsWithDomains->first()->id);
    }

    public function test_has_reached_max_domains_returns_true_when_limit_reached()
    {
        $group = DomainGroup::factory()->create(['max_domains' => 3]);
        Domain::factory()->count(3)->create(['domain_group_id' => $group->id]);

        $this->assertTrue($group->hasReachedMaxDomains());
    }

    public function test_has_reached_max_domains_returns_false_when_below_limit()
    {
        $group = DomainGroup::factory()->create(['max_domains' => 5]);
        Domain::factory()->count(2)->create(['domain_group_id' => $group->id]);

        $this->assertFalse($group->hasReachedMaxDomains());
    }

    public function test_has_reached_max_domains_returns_false_when_unlimited()
    {
        $group = DomainGroup::factory()->create(['max_domains' => null]);
        Domain::factory()->count(100)->create(['domain_group_id' => $group->id]);

        $this->assertFalse($group->hasReachedMaxDomains());
    }

    public function test_get_available_domains_count_returns_correct_value()
    {
        $group = DomainGroup::factory()->create(['max_domains' => 10]);
        Domain::factory()->count(3)->create(['domain_group_id' => $group->id]);

        $available = $group->getAvailableDomainsCount();

        $this->assertEquals(7, $available);
    }

    public function test_get_available_domains_count_returns_null_when_unlimited()
    {
        $group = DomainGroup::factory()->create(['max_domains' => null]);

        $available = $group->getAvailableDomainsCount();

        $this->assertNull($available);
    }

    public function test_settings_is_cast_to_array()
    {
        $group = DomainGroup::factory()->create([
            'settings' => ['key' => 'value', 'nested' => ['data' => 123]],
        ]);

        $this->assertIsArray($group->settings);
        $this->assertEquals('value', $group->settings['key']);
        $this->assertEquals(123, $group->settings['nested']['data']);
    }

    public function test_soft_delete_works()
    {
        $group = DomainGroup::factory()->create();
        $groupId = $group->id;

        $group->delete();

        $this->assertSoftDeleted('domain_groups', ['id' => $groupId]);
        $this->assertNull(DomainGroup::find($groupId));
        $this->assertNotNull(DomainGroup::withTrashed()->find($groupId));
    }
}

