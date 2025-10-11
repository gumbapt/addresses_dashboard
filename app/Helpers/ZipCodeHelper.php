<?php

namespace App\Helpers;

class ZipCodeHelper
{
    /**
     * Normalize ZIP code to 5-digit string with leading zeros
     * 
     * @param int|string $zipCode
     * @return string
     */
    public static function normalize($zipCode): string
    {
        // Convert to string and remove any non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', (string) $zipCode);
        
        // Take only first 5 digits if longer (handles ZIP+4 format)
        if (strlen($cleaned) > 5) {
            $cleaned = substr($cleaned, 0, 5);
        }
        
        // Pad with leading zeros to 5 digits
        return str_pad($cleaned, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Validate if ZIP code is in valid format
     * 
     * @param string $zipCode
     * @return bool
     */
    public static function isValid(string $zipCode): bool
    {
        // US ZIP codes: 5 digits or 5+4 format (12345-6789)
        return preg_match('/^\d{5}(-\d{4})?$/', $zipCode) === 1;
    }

    /**
     * Extract 5-digit base ZIP from ZIP+4 format
     * 
     * @param string $zipCode
     * @return string
     */
    public static function getBase(string $zipCode): string
    {
        $normalized = self::normalize($zipCode);
        return substr($normalized, 0, 5);
    }

    /**
     * Infer state code from ZIP code (first digit rules)
     * Returns null if cannot be determined
     * 
     * @param string $zipCode
     * @return string|null
     */
    public static function inferStateFromFirstDigit(string $zipCode): ?string
    {
        // Return null for empty or non-numeric input
        if (empty($zipCode) || !preg_match('/\d/', $zipCode)) {
            return null;
        }
        
        $firstDigit = substr(self::normalize($zipCode), 0, 1);
        
        // Rough approximation based on ZIP code ranges
        // Note: This is not 100% accurate, better to use lookup table
        return match($firstDigit) {
            '0' => 'CT', // Northeast (00-09)
            '1' => 'NY', // NY, PA (10-19)
            '2' => 'VA', // DC, MD, VA (20-29)
            '3' => 'FL', // Southeast (30-39)
            '4' => 'KY', // Kentucky area (40-49)
            '5' => 'MN', // North Central (50-59)
            '6' => 'MO', // Missouri area (60-69)
            '7' => 'TX', // South Central (70-79)
            '8' => 'CO', // Mountain (80-89)
            '9' => 'CA', // West Coast (90-99)
            default => null,
        };
    }
}

