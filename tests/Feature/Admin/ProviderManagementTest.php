<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Provider;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProviderManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user for authentication
        $this->admin = Admin::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        
        // Create token for API authentication
        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    public function test_can_list_providers_with_pagination(): void
    {

        // Create test providers
        Provider::factory()->create([
            'name' => 'AT&T',
            'slug' => 'att',
            'technologies' => ['Fiber', 'Mobile'],
            'is_active' => true,
        ]);
        
        Provider::factory()->create([
            'name' => 'Spectrum',
            'slug' => 'spectrum',
            'technologies' => ['Cable', 'Fiber'],
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'website',
                    'logo_url',
                    'description',
                    'technologies',
                    'is_active'
                ]
            ],
            'pagination' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
                'from',
                'to'
            ]
        ]);

        $response->assertJson([
            'success' => true,
        ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_search_providers_by_name(): void
    {

        Provider::factory()->create([
            'name' => 'Search Test Provider AT&T',
            'slug' => 'search-test-provider-att',
            'technologies' => ['Fiber'],
        ]);
        
        Provider::factory()->create([
            'name' => 'Verizon Search Test',
            'slug' => 'verizon-search-test', 
            'technologies' => ['Mobile'],
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers?search=Search%20Test%20Provider%20AT%26T');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Search Test Provider AT&T', $response->json('data.0.name'));
    }

    public function test_can_filter_providers_by_technology(): void
    {

        Provider::factory()->create([
            'name' => 'Fiber Provider',
            'slug' => 'fiber-provider',
            'technologies' => ['Fiber', 'Cable'],
        ]);
        
        Provider::factory()->create([
            'name' => 'Mobile Only Provider',
            'slug' => 'mobile-only',
            'technologies' => ['Mobile'],
        ]);
        
        Provider::factory()->create([
            'name' => 'Multi Provider',
            'slug' => 'multi-provider',
            'technologies' => ['Fiber', 'Mobile', 'DSL'],
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers?technology=Fiber');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        // Check that all returned providers have Fiber technology
        foreach ($data as $provider) {
            $this->assertContains('Fiber', $provider['technologies']);
        }
    }

    public function test_can_filter_providers_by_active_status(): void
    {

        Provider::factory()->create([
            'name' => 'Active Provider',
            'is_active' => true,
        ]);
        
        Provider::factory()->create([
            'name' => 'Inactive Provider',
            'is_active' => false,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers?is_active=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.is_active'));
    }

    public function test_can_get_provider_by_slug(): void
    {

        $provider = Provider::factory()->create([
            'name' => 'Test Provider',
            'slug' => 'test-provider',
            'website' => 'https://test.com',
            'description' => 'Test description',
            'technologies' => ['Fiber', 'Cable'],
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers/test-provider');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $provider->id,
                'name' => 'Test Provider',
                'slug' => 'test-provider',
                'website' => 'https://test.com',
                'description' => 'Test description',
                'technologies' => ['Fiber', 'Cable'],
                'is_active' => true,
            ]
        ]);
    }

    public function test_get_provider_by_slug_returns_404_for_nonexistent(): void
    {

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers/nonexistent-slug');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Provider with slug nonexistent-slug not found'
        ]);
    }

    public function test_can_get_providers_by_technology(): void
    {

        Provider::factory()->create([
            'name' => 'Fiber Provider 1',
            'technologies' => ['Fiber', 'Cable'],
        ]);
        
        Provider::factory()->create([
            'name' => 'Mobile Provider',
            'technologies' => ['Mobile'],
        ]);
        
        Provider::factory()->create([
            'name' => 'Fiber Provider 2', 
            'technologies' => ['Fiber', 'DSL'],
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers/by-technology/Fiber');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        $names = array_column($data, 'name');
        $this->assertContains('Fiber Provider 1', $names);
        $this->assertContains('Fiber Provider 2', $names);
    }

    public function test_can_get_available_technologies(): void
    {

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers/technologies');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $technologies = $response->json('data');
        
        $this->assertGreaterThan(0, count($technologies));
        
        // Check structure of technology entries
        foreach ($technologies as $tech) {
            $this->assertArrayHasKey('name', $tech);
            $this->assertArrayHasKey('display_name', $tech);
        }
        
        // Check for some expected technologies
        $techNames = array_column($technologies, 'name');
        $this->assertContains('Fiber', $techNames);
        $this->assertContains('Cable', $techNames);
        $this->assertContains('Mobile', $techNames);
        $this->assertContains('DSL', $techNames);
    }

    public function test_pagination_parameters_work_correctly(): void
    {

        // Create 25 providers with unique names to avoid slug conflicts
        for ($i = 1; $i <= 25; $i++) {
            Provider::factory()->create([
                'name' => "Test Provider $i",
                'slug' => "test-provider-$i",
            ]);
        }

        // Test first page with 10 per page
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers?page=1&per_page=10');

        $response->assertStatus(200);
        $pagination = $response->json('pagination');
        
        $this->assertEquals(25, $pagination['total']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(3, $pagination['last_page']); // ceil(25/10) = 3
        $this->assertCount(10, $response->json('data'));

        // Test second page
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers?page=2&per_page=10');
        
        $response->assertStatus(200);
        $pagination = $response->json('pagination');
        
        $this->assertEquals(2, $pagination['current_page']);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_per_page_limit_is_enforced(): void
    {

        // Create 150 providers with unique names to avoid slug conflicts  
        for ($i = 1; $i <= 150; $i++) {
            Provider::factory()->create([
                'name' => "Limit Test Provider $i",
                'slug' => "limit-test-provider-$i",
            ]);
        }

        // Try to request more than max (100)
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers?per_page=200');

        $response->assertStatus(200);
        $pagination = $response->json('pagination');
        
        // Should be limited to 100
        $this->assertEquals(100, $pagination['per_page']);
        $this->assertCount(100, $response->json('data'));
    }

    public function test_requires_admin_authentication(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers');
        
        $response->assertStatus(401);
    }

    public function test_combined_filters_work(): void
    {

        Provider::factory()->create([
            'name' => 'CombinedTest Fiber Service',
            'slug' => 'combinedtest-fiber-service',
            'technologies' => ['Fiber', 'Mobile'],
            'is_active' => true,
        ]);
        
        Provider::factory()->create([
            'name' => 'CombinedTest Mobile Only',
            'slug' => 'combinedtest-mobile-only',
            'technologies' => ['Mobile'],
            'is_active' => false,
        ]);
        
        Provider::factory()->create([
            'name' => 'Verizon Fiber Test',
            'slug' => 'verizon-fiber-test',
            'technologies' => ['Fiber'],
            'is_active' => true,
        ]);

        // Search for CombinedTest providers with Fiber technology that are active
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->getJson('/api/admin/providers?search=CombinedTest&technology=Fiber&is_active=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('CombinedTest Fiber Service', $data[0]['name']);
        $this->assertContains('Fiber', $data[0]['technologies']);
        $this->assertTrue($data[0]['is_active']);
    }
}
