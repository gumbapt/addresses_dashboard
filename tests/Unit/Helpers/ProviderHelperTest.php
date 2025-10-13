<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ProviderHelper;
use PHPUnit\Framework\TestCase;

class ProviderHelperTest extends TestCase
{
    public function test_normalize_name_handles_common_variations(): void
    {
        // AT&T variations
        $this->assertEquals('AT&T', ProviderHelper::normalizeName('AT & T'));
        $this->assertEquals('AT&T', ProviderHelper::normalizeName('ATT'));
        $this->assertEquals('AT&T', ProviderHelper::normalizeName('At&t'));
        $this->assertEquals('AT&T', ProviderHelper::normalizeName('AT&T'));
        
        // Comcast/Xfinity
        $this->assertEquals('Xfinity', ProviderHelper::normalizeName('Comcast'));
        
        // Charter/Spectrum
        $this->assertEquals('Spectrum', ProviderHelper::normalizeName('Charter'));
        $this->assertEquals('Spectrum', ProviderHelper::normalizeName('Charter Communications'));
        
        // T-Mobile variations
        $this->assertEquals('T-Mobile', ProviderHelper::normalizeName('T Mobile'));
        $this->assertEquals('T-Mobile', ProviderHelper::normalizeName('TMobile'));
        
        // Verizon
        $this->assertEquals('Verizon', ProviderHelper::normalizeName('Verizon Wireless'));
        
        // Unknown providers should not change
        $this->assertEquals('Unknown ISP', ProviderHelper::normalizeName('Unknown ISP'));
        $this->assertEquals('Local Provider', ProviderHelper::normalizeName('Local Provider'));
    }

    public function test_generate_slug(): void
    {
        $this->assertEquals('att', ProviderHelper::generateSlug('AT&T'));
        $this->assertEquals('cox-communications', ProviderHelper::generateSlug('Cox Communications'));
        $this->assertEquals('t-mobile', ProviderHelper::generateSlug('T-Mobile'));
        $this->assertEquals('spectrum', ProviderHelper::generateSlug('Spectrum'));
    }

    public function test_is_valid_technology(): void
    {
        // Valid technologies
        $this->assertTrue(ProviderHelper::isValidTechnology('Fiber'));
        $this->assertTrue(ProviderHelper::isValidTechnology('Cable'));
        $this->assertTrue(ProviderHelper::isValidTechnology('Mobile'));
        $this->assertTrue(ProviderHelper::isValidTechnology('DSL'));
        $this->assertTrue(ProviderHelper::isValidTechnology('Satellite'));
        $this->assertTrue(ProviderHelper::isValidTechnology('Wireless'));
        $this->assertTrue(ProviderHelper::isValidTechnology('Fixed Wireless'));
        
        // Invalid technologies
        $this->assertFalse(ProviderHelper::isValidTechnology('InvalidTech'));
        $this->assertFalse(ProviderHelper::isValidTechnology('Unknown'));
        $this->assertFalse(ProviderHelper::isValidTechnology(''));
    }

    public function test_normalize_technology(): void
    {
        // Standard normalizations
        $this->assertEquals('Fiber', ProviderHelper::normalizeTechnology('Fiber Optic'));
        $this->assertEquals('Cable', ProviderHelper::normalizeTechnology('Cable Internet'));
        $this->assertEquals('Mobile', ProviderHelper::normalizeTechnology('Mobile/Cellular'));
        $this->assertEquals('Mobile', ProviderHelper::normalizeTechnology('Cellular'));
        $this->assertEquals('DSL', ProviderHelper::normalizeTechnology('Digital Subscriber Line'));
        $this->assertEquals('Satellite', ProviderHelper::normalizeTechnology('Satellite Internet'));
        $this->assertEquals('Fixed Wireless', ProviderHelper::normalizeTechnology('Fixed Wireless Access'));
        $this->assertEquals('Fixed Wireless', ProviderHelper::normalizeTechnology('FWA'));
        
        // Unknown technologies should not change
        $this->assertEquals('Unknown Tech', ProviderHelper::normalizeTechnology('Unknown Tech'));
    }

    public function test_extract_technologies(): void
    {
        // From top_providers format
        $topProviderData = ['name' => 'AT&T', 'technology' => 'Fiber Optic'];
        $technologies = ProviderHelper::extractTechnologies($topProviderData);
        $this->assertContains('Fiber', $technologies);
        
        // From by_provider format
        $byProviderData = ['Cable Internet' => 100, 'Mobile/Cellular' => 200];
        $technologies = ProviderHelper::extractTechnologies($byProviderData);
        $this->assertContains('Cable', $technologies);
        $this->assertContains('Mobile', $technologies);
        $this->assertCount(2, $technologies);
        
        // Invalid technologies should be filtered out
        $invalidData = ['InvalidTech' => 100, 'Fiber' => 50];
        $technologies = ProviderHelper::extractTechnologies($invalidData);
        $this->assertContains('Fiber', $technologies);
        $this->assertEquals(1, count($technologies));
    }

    public function test_normalize_website(): void
    {
        // Add protocol if missing
        $this->assertEquals('https://att.com', ProviderHelper::normalizeWebsite('att.com'));
        $this->assertEquals('https://www.att.com', ProviderHelper::normalizeWebsite('www.att.com'));
        
        // Keep protocol if present
        $this->assertEquals('https://att.com', ProviderHelper::normalizeWebsite('https://att.com'));
        $this->assertEquals('http://att.com', ProviderHelper::normalizeWebsite('http://att.com'));
        
        // Return null for empty or null
        $this->assertNull(ProviderHelper::normalizeWebsite(''));
        $this->assertNull(ProviderHelper::normalizeWebsite(null));
        
        // For strings that can't be URLs, it might still try to validate
        // Let's test with obviously invalid URLs
        $this->assertNull(ProviderHelper::normalizeWebsite('not a url at all'));
        $this->assertNull(ProviderHelper::normalizeWebsite('just text'));
    }

    public function test_generate_description(): void
    {
        // With technologies
        $technologies = ['Fiber', 'Mobile'];
        $description = ProviderHelper::generateDescription($technologies);
        $this->assertEquals('Internet service provider offering Fiber, Mobile services', $description);
        
        // Without technologies
        $description = ProviderHelper::generateDescription([]);
        $this->assertEquals('Internet service provider', $description);
    }

    public function test_get_technology_display_name(): void
    {
        $this->assertEquals('Fiber Optic', ProviderHelper::getTechnologyDisplayName('Fiber'));
        $this->assertEquals('Cable Internet', ProviderHelper::getTechnologyDisplayName('Cable'));
        $this->assertEquals('Mobile/Cellular', ProviderHelper::getTechnologyDisplayName('Mobile'));
        $this->assertEquals('Digital Subscriber Line', ProviderHelper::getTechnologyDisplayName('DSL'));
        $this->assertEquals('Satellite Internet', ProviderHelper::getTechnologyDisplayName('Satellite'));
        $this->assertEquals('Fixed Wireless', ProviderHelper::getTechnologyDisplayName('Wireless'));
        $this->assertEquals('Fixed Wireless Access', ProviderHelper::getTechnologyDisplayName('Fixed Wireless'));
        
        // Unknown technology should return as-is
        $this->assertEquals('Unknown', ProviderHelper::getTechnologyDisplayName('Unknown'));
    }
}
