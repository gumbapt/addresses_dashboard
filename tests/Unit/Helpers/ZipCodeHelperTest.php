<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\ZipCodeHelper;

class ZipCodeHelperTest extends TestCase
{
    public function test_normalize_handles_integer_input(): void
    {
        // Act & Assert
        $this->assertEquals('10038', ZipCodeHelper::normalize(10038));
        $this->assertEquals('07018', ZipCodeHelper::normalize(7018));
        $this->assertEquals('00501', ZipCodeHelper::normalize(501));
        $this->assertEquals('00001', ZipCodeHelper::normalize(1));
    }

    public function test_normalize_handles_string_input(): void
    {
        // Act & Assert
        $this->assertEquals('90210', ZipCodeHelper::normalize('90210'));
        $this->assertEquals('07018', ZipCodeHelper::normalize('07018'));
        $this->assertEquals('00501', ZipCodeHelper::normalize('00501'));
        $this->assertEquals('12345', ZipCodeHelper::normalize('12345'));
    }

    public function test_normalize_removes_non_digit_characters(): void
    {
        // Act & Assert
        $this->assertEquals('90210', ZipCodeHelper::normalize('902-10'));
        $this->assertEquals('90210', ZipCodeHelper::normalize('902 10'));
        $this->assertEquals('90210', ZipCodeHelper::normalize('902.10'));
        $this->assertEquals('90210', ZipCodeHelper::normalize('902#10'));
    }

    public function test_normalize_pads_with_leading_zeros(): void
    {
        // Act & Assert
        $this->assertEquals('00123', ZipCodeHelper::normalize('123'));
        $this->assertEquals('00012', ZipCodeHelper::normalize('12'));
        $this->assertEquals('00001', ZipCodeHelper::normalize('1'));
        $this->assertEquals('01234', ZipCodeHelper::normalize(1234));
    }

    public function test_normalize_handles_zip_plus_4_format(): void
    {
        // Act & Assert - Should extract first 5 digits
        $this->assertEquals('90210', ZipCodeHelper::normalize('90210-1234'));
        $this->assertEquals('10001', ZipCodeHelper::normalize('10001-5555'));
    }

    public function test_normalize_handles_empty_string(): void
    {
        // Act & Assert
        $this->assertEquals('00000', ZipCodeHelper::normalize(''));
    }

    public function test_is_valid_accepts_5_digit_format(): void
    {
        // Act & Assert
        $this->assertTrue(ZipCodeHelper::isValid('90210'));
        $this->assertTrue(ZipCodeHelper::isValid('10001'));
        $this->assertTrue(ZipCodeHelper::isValid('00501'));
    }

    public function test_is_valid_accepts_zip_plus_4_format(): void
    {
        // Act & Assert
        $this->assertTrue(ZipCodeHelper::isValid('90210-1234'));
        $this->assertTrue(ZipCodeHelper::isValid('10001-5555'));
        $this->assertTrue(ZipCodeHelper::isValid('07018-0000'));
    }

    public function test_is_valid_rejects_invalid_formats(): void
    {
        // Act & Assert
        $this->assertFalse(ZipCodeHelper::isValid('902'));
        $this->assertFalse(ZipCodeHelper::isValid('90'));
        $this->assertFalse(ZipCodeHelper::isValid('9'));
        $this->assertFalse(ZipCodeHelper::isValid('902101')); // 6 digits
        $this->assertFalse(ZipCodeHelper::isValid('90210-12')); // Invalid ZIP+4
        $this->assertFalse(ZipCodeHelper::isValid('9021a')); // Contains letter
        $this->assertFalse(ZipCodeHelper::isValid('902 10')); // Contains space
    }

    public function test_get_base_extracts_5_digit_base_from_zip_plus_4(): void
    {
        // Act & Assert
        $this->assertEquals('90210', ZipCodeHelper::getBase('90210-1234'));
        $this->assertEquals('10001', ZipCodeHelper::getBase('10001-5555'));
        $this->assertEquals('07018', ZipCodeHelper::getBase('07018-0000'));
    }

    public function test_get_base_handles_5_digit_format(): void
    {
        // Act & Assert
        $this->assertEquals('90210', ZipCodeHelper::getBase('90210'));
        $this->assertEquals('10001', ZipCodeHelper::getBase('10001'));
    }

    public function test_get_base_normalizes_before_extraction(): void
    {
        // Act & Assert
        $this->assertEquals('07018', ZipCodeHelper::getBase(7018));
        $this->assertEquals('00501', ZipCodeHelper::getBase('501'));
    }

    public function test_infer_state_from_first_digit_returns_correct_approximations(): void
    {
        // Act & Assert
        $this->assertEquals('CT', ZipCodeHelper::inferStateFromFirstDigit('06830')); // 0x = Northeast
        $this->assertEquals('NY', ZipCodeHelper::inferStateFromFirstDigit('10001')); // 1x = NY/PA
        $this->assertEquals('VA', ZipCodeHelper::inferStateFromFirstDigit('20001')); // 2x = DC/MD/VA
        $this->assertEquals('FL', ZipCodeHelper::inferStateFromFirstDigit('33101')); // 3x = Southeast
        $this->assertEquals('KY', ZipCodeHelper::inferStateFromFirstDigit('40202')); // 4x = Kentucky area
        $this->assertEquals('MN', ZipCodeHelper::inferStateFromFirstDigit('55401')); // 5x = North Central
        $this->assertEquals('MO', ZipCodeHelper::inferStateFromFirstDigit('63101')); // 6x = Missouri area
        $this->assertEquals('TX', ZipCodeHelper::inferStateFromFirstDigit('75201')); // 7x = South Central
        $this->assertEquals('CO', ZipCodeHelper::inferStateFromFirstDigit('80202')); // 8x = Mountain
        $this->assertEquals('CA', ZipCodeHelper::inferStateFromFirstDigit('90210')); // 9x = West Coast
    }

    public function test_infer_state_from_first_digit_handles_leading_zeros(): void
    {
        // Act & Assert
        $this->assertEquals('CT', ZipCodeHelper::inferStateFromFirstDigit('00501')); // 0x
        $this->assertEquals('CT', ZipCodeHelper::inferStateFromFirstDigit('06830')); // 0x
    }

    public function test_infer_state_from_first_digit_normalizes_input(): void
    {
        // Act & Assert
        $this->assertEquals('CA', ZipCodeHelper::inferStateFromFirstDigit(90210)); // int
        $this->assertEquals('CA', ZipCodeHelper::inferStateFromFirstDigit('90210')); // string
        $this->assertEquals('NY', ZipCodeHelper::inferStateFromFirstDigit(10001)); // int
    }

    public function test_normalize_is_idempotent(): void
    {
        // Arrange
        $zip = '07018';
        
        // Act
        $normalized1 = ZipCodeHelper::normalize($zip);
        $normalized2 = ZipCodeHelper::normalize($normalized1);
        $normalized3 = ZipCodeHelper::normalize($normalized2);
        
        // Assert - Multiple normalizations should produce the same result
        $this->assertEquals($normalized1, $normalized2);
        $this->assertEquals($normalized2, $normalized3);
        $this->assertEquals('07018', $normalized3);
    }

    public function test_normalize_handles_real_world_json_cases(): void
    {
        // These are actual cases from newdata.json
        
        // Act & Assert
        $this->assertEquals('10038', ZipCodeHelper::normalize(10038)); // int from JSON
        $this->assertEquals('07018', ZipCodeHelper::normalize('07018')); // string from JSON
        $this->assertEquals('07018', ZipCodeHelper::normalize(7018)); // int that lost leading zero
    }

    public function test_normalize_handles_extreme_cases(): void
    {
        // Act & Assert
        $this->assertEquals('99999', ZipCodeHelper::normalize(99999)); // Max valid ZIP
        $this->assertEquals('00001', ZipCodeHelper::normalize(1)); // Min valid ZIP
        $this->assertEquals('00000', ZipCodeHelper::normalize(0)); // Edge case
    }

    public function test_is_valid_handles_edge_cases(): void
    {
        // Act & Assert
        $this->assertTrue(ZipCodeHelper::isValid('99999')); // Max valid ZIP
        $this->assertTrue(ZipCodeHelper::isValid('00001')); // Min valid ZIP
        $this->assertFalse(ZipCodeHelper::isValid('')); // Empty string
        $this->assertFalse(ZipCodeHelper::isValid('0')); // Too short
    }

    public function test_get_base_handles_integers(): void
    {
        // Act & Assert
        $this->assertEquals('90210', ZipCodeHelper::getBase(90210));
        $this->assertEquals('07018', ZipCodeHelper::getBase(7018));
        $this->assertEquals('00501', ZipCodeHelper::getBase(501));
    }

    public function test_normalize_consistency_across_different_input_types(): void
    {
        // Arrange - Different representations of the same ZIP
        $intInput = 7018;
        $stringInput = '7018';
        $stringWithZero = '07018';
        $stringWithDash = '07018-1234';
        
        // Act
        $normalized1 = ZipCodeHelper::normalize($intInput);
        $normalized2 = ZipCodeHelper::normalize($stringInput);
        $normalized3 = ZipCodeHelper::normalize($stringWithZero);
        $normalized4 = ZipCodeHelper::normalize($stringWithDash);
        
        // Assert - All should normalize to the same value
        $this->assertEquals('07018', $normalized1);
        $this->assertEquals('07018', $normalized2);
        $this->assertEquals('07018', $normalized3);
        $this->assertEquals('07018', $normalized4);
    }

    public function test_infer_state_handles_invalid_first_digit(): void
    {
        // Act & Assert - Should return null for invalid cases
        $this->assertNull(ZipCodeHelper::inferStateFromFirstDigit(''));
        $this->assertNull(ZipCodeHelper::inferStateFromFirstDigit('abc'));
    }
}

