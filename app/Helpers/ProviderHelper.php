<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class ProviderHelper
{
    /**
     * Normalize provider name to handle common variations
     * 
     * @param string $name
     * @return string
     */
    public static function normalizeName(string $name): string
    {
        $name = trim($name);
        
        // Common provider name normalizations
        $replacements = [
            'AT & T' => 'AT&T',
            'ATT' => 'AT&T',
            'At&t' => 'AT&T',
            'Comcast' => 'Xfinity',
            'Charter' => 'Spectrum',
            'Charter Communications' => 'Spectrum',
            'Verizon Wireless' => 'Verizon',
            'T Mobile' => 'T-Mobile',
            'TMobile' => 'T-Mobile',
            'Cox Communications Inc' => 'Cox Communications',
            'Consolidated Communications Inc' => 'Consolidated Communications',
            'Frontier Communications' => 'Frontier',
        ];
        
        return $replacements[$name] ?? $name;
    }

    /**
     * Generate slug from provider name
     * 
     * @param string $name
     * @return string
     */
    public static function generateSlug(string $name): string
    {
        return Str::slug($name);
    }

    /**
     * Validate technology name
     * 
     * @param string $technology
     * @return bool
     */
    public static function isValidTechnology(string $technology): bool
    {
        $validTechnologies = [
            'Fiber',
            'Cable',
            'Mobile',
            'DSL',
            'Satellite',
            'Wireless',
            'Fixed Wireless',
        ];
        
        return in_array($technology, $validTechnologies);
    }

    /**
     * Normalize technology name
     * 
     * @param string $technology
     * @return string
     */
    public static function normalizeTechnology(string $technology): string
    {
        $technology = trim($technology);
        
        $replacements = [
            'Fiber Optic' => 'Fiber',
            'Cable Internet' => 'Cable',
            'Mobile/Cellular' => 'Mobile',
            'Cellular' => 'Mobile',
            'Digital Subscriber Line' => 'DSL',
            'Satellite Internet' => 'Satellite',
            'Fixed Wireless Access' => 'Fixed Wireless',
            'FWA' => 'Fixed Wireless',
        ];
        
        return $replacements[$technology] ?? $technology;
    }

    /**
     * Extract technologies from provider data
     * 
     * @param array $providerData
     * @return array
     */
    public static function extractTechnologies(array $providerData): array
    {
        $technologies = [];
        
        // From top_providers format
        if (isset($providerData['technology'])) {
            $technologies[] = self::normalizeTechnology($providerData['technology']);
        }
        
        // From by_provider format (multiple technologies)
        if (is_array($providerData) && !isset($providerData['technology'])) {
            foreach (array_keys($providerData) as $tech) {
                if (is_string($tech)) {
                    $technologies[] = self::normalizeTechnology($tech);
                }
            }
        }
        
        return array_unique(array_filter($technologies, [self::class, 'isValidTechnology']));
    }

    /**
     * Clean and validate website URL
     * 
     * @param string|null $website
     * @return string|null
     */
    public static function normalizeWebsite(?string $website): ?string
    {
        if (empty($website)) {
            return null;
        }
        
        $website = trim($website);
        
        // Add https:// if no protocol specified
        if (!preg_match('/^https?:\/\//', $website)) {
            $website = 'https://' . $website;
        }
        
        // Validate URL format
        if (filter_var($website, FILTER_VALIDATE_URL)) {
            return $website;
        }
        
        return null;
    }

    /**
     * Generate provider description from technologies
     * 
     * @param array $technologies
     * @return string
     */
    public static function generateDescription(array $technologies): string
    {
        if (empty($technologies)) {
            return 'Internet service provider';
        }
        
        $techString = implode(', ', $technologies);
        return "Internet service provider offering {$techString} services";
    }

    /**
     * Get display name for technology
     * 
     * @param string $technology
     * @return string
     */
    public static function getTechnologyDisplayName(string $technology): string
    {
        $displayNames = [
            'Fiber' => 'Fiber Optic',
            'Cable' => 'Cable Internet',
            'Mobile' => 'Mobile/Cellular',
            'DSL' => 'Digital Subscriber Line',
            'Satellite' => 'Satellite Internet',
            'Wireless' => 'Fixed Wireless',
            'Fixed Wireless' => 'Fixed Wireless Access',
        ];
        
        return $displayNames[$technology] ?? $technology;
    }
}
