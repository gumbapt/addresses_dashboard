<?php

namespace App\Application\UseCases\Report;

class ValidateReportStructureUseCase
{
    /**
     * Required sections in report structure
     */
    private const REQUIRED_SECTIONS = [
        'source',
        'metadata',
        'summary'
    ];

    /**
     * Required fields in source section
     */
    private const REQUIRED_SOURCE_FIELDS = [
        'domain',
        'site_id',
        'site_name'
    ];

    /**
     * Required fields in metadata section
     */
    private const REQUIRED_METADATA_FIELDS = [
        'report_date',
        'report_period',
        'generated_at',
        'data_version'
    ];

    /**
     * Validate report structure
     */
    public function execute(array $reportData): array
    {
        $errors = [];

        // Validate required sections exist
        foreach (self::REQUIRED_SECTIONS as $section) {
            if (!isset($reportData[$section]) || !is_array($reportData[$section])) {
                $errors[] = "Missing or invalid '{$section}' section";
                continue; // Skip field validation for missing sections
            }
        }

        // Validate source section
        if (isset($reportData['source']) && is_array($reportData['source'])) {
            $errors = array_merge($errors, $this->validateSourceSection($reportData['source']));
        }

        // Validate metadata section
        if (isset($reportData['metadata']) && is_array($reportData['metadata'])) {
            $errors = array_merge($errors, $this->validateMetadataSection($reportData['metadata']));
        }

        // Validate summary section
        if (isset($reportData['summary']) && is_array($reportData['summary'])) {
            $errors = array_merge($errors, $this->validateSummarySection($reportData['summary']));
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $this->generateWarnings($reportData)
        ];
    }

    /**
     * Validate source section
     */
    private function validateSourceSection(array $sourceData): array
    {
        $errors = [];

        foreach (self::REQUIRED_SOURCE_FIELDS as $field) {
            if (!isset($sourceData[$field]) || empty($sourceData[$field])) {
                $errors[] = "Missing or empty 'source.{$field}' field";
            }
        }

        // Validate domain format (basic check since FILTER_VALIDATE_DOMAIN may not be available)
        if (isset($sourceData['domain']) && !$this->isValidDomain($sourceData['domain'])) {
            $errors[] = "Invalid domain format in 'source.domain'";
        }

        return $errors;
    }

    /**
     * Validate metadata section
     */
    private function validateMetadataSection(array $metadataData): array
    {
        $errors = [];

        foreach (self::REQUIRED_METADATA_FIELDS as $field) {
            if (!isset($metadataData[$field])) {
                $errors[] = "Missing 'metadata.{$field}' field";
            }
        }

        // Validate report_date format
        if (isset($metadataData['report_date'])) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $metadataData['report_date'])) {
                $errors[] = "Invalid 'metadata.report_date' format (expected YYYY-MM-DD)";
            }
        }

        // Validate report_period structure
        if (isset($metadataData['report_period'])) {
            if (!is_array($metadataData['report_period'])) {
                $errors[] = "Invalid 'metadata.report_period' structure (expected array)";
            } elseif (!isset($metadataData['report_period']['start']) || !isset($metadataData['report_period']['end'])) {
                $errors[] = "Missing 'metadata.report_period.start' or 'metadata.report_period.end'";
            }
        }

        // Validate generated_at format
        if (isset($metadataData['generated_at'])) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $metadataData['generated_at'])) {
                $errors[] = "Invalid 'metadata.generated_at' format (expected YYYY-MM-DD HH:MM:SS)";
            }
        }

        return $errors;
    }

    /**
     * Validate summary section
     */
    private function validateSummarySection(array $summaryData): array
    {
        $errors = [];

        // Check for reasonable data types
        $numericFields = ['total_requests', 'failed_requests', 'unique_providers', 'unique_states', 'unique_zip_codes'];
        foreach ($numericFields as $field) {
            if (isset($summaryData[$field]) && !is_numeric($summaryData[$field])) {
                $errors[] = "Invalid 'summary.{$field}' value (expected numeric)";
            }
        }

        // Validate success_rate range
        if (isset($summaryData['success_rate'])) {
            $successRate = $summaryData['success_rate'];
            if (!is_numeric($successRate) || $successRate < 0 || $successRate > 100) {
                $errors[] = "Invalid 'summary.success_rate' value (expected 0-100)";
            }
        }

        return $errors;
    }

    /**
     * Generate warnings for optional but recommended fields
     */
    private function generateWarnings(array $reportData): array
    {
        $warnings = [];

        // Warn about missing optional sections that provide valuable data
        $optionalSections = ['providers', 'geographic', 'performance', 'speed_metrics'];
        foreach ($optionalSections as $section) {
            if (!isset($reportData[$section]) || empty($reportData[$section])) {
                $warnings[] = "Optional '{$section}' section is missing - analytics will be limited";
            }
        }

        // Warn about missing provider data
        if (isset($reportData['providers']) && 
            (!isset($reportData['providers']['top_providers']) || empty($reportData['providers']['top_providers']))) {
            $warnings[] = "No provider data found - provider analytics will be unavailable";
        }

        // Warn about missing geographic data
        if (isset($reportData['geographic']) && 
            (!isset($reportData['geographic']['states']) || empty($reportData['geographic']['states']))) {
            $warnings[] = "No geographic state data found - location analytics will be limited";
        }

        return $warnings;
    }

    /**
     * Validate domain format
     */
    private function isValidDomain(string $domain): bool
    {
        // Basic domain validation
        if (empty($domain) || strlen($domain) > 253) {
            return false;
        }

        // Must contain at least one dot for TLD
        if (strpos($domain, '.') === false) {
            return false;
        }

        // Check for valid characters and structure - must end with a TLD
        return preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)+$/', $domain);
    }
}
