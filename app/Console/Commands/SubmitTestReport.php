<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Domain;

class SubmitTestReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:submit-test 
                            {--file=docs/newdata.json : Path to the JSON file to submit}
                            {--domain= : Domain name (optional, uses first active domain if not provided)}
                            {--create-domain : Create a test domain if none exists}
                            {--url=http://localhost:8006 : Base URL for the API}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submit a test report to the API simulating an external service (like 50gig)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file');
        $domainName = $this->option('domain');
        $createDomain = $this->option('create-domain');
        $baseUrl = $this->option('url');

        $this->info('🚀 Submitting Test Report to API...');
        $this->newLine();

        // 1. Read JSON file
        if (!File::exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");
            return 1;
        }

        $this->info("📄 Reading file: {$filePath}");
        $reportData = json_decode(File::get($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('❌ Invalid JSON file: ' . json_last_error_msg());
            return 1;
        }

        $this->info('✅ JSON file loaded successfully');
        $this->info('📊 Data structure: ' . count($reportData) . ' main sections');
        $this->newLine();

        // 2. Get or create domain
        $domain = null;

        if ($domainName) {
            $domain = Domain::where('name', $domainName)->first();
            if (!$domain) {
                $this->error("❌ Domain not found: {$domainName}");
                return 1;
            }
        } else {
            // Try to get domain from JSON
            $sourceDomain = $reportData['source']['domain'] ?? null;
            
            if ($sourceDomain) {
                $domain = Domain::where('name', $sourceDomain)->first();
                
                if (!$domain && $createDomain) {
                    $this->info("📝 Creating domain: {$sourceDomain}");
                    
                    // Extract data from JSON source section
                    $sourceData = $reportData['source'] ?? [];
                    
                    $domain = Domain::create([
                        'name' => $sourceDomain,
                        'slug' => str_replace('.', '-', $sourceDomain),
                        'domain_url' => 'https://' . $sourceDomain,
                        'site_id' => $sourceData['site_id'] ?? 'test-site-001',
                        'api_key' => 'test_' . bin2hex(random_bytes(32)),
                        'status' => 'active',
                        'timezone' => $sourceData['timezone'] ?? 'America/New_York',
                        'wordpress_version' => $sourceData['wordpress_version'] ?? '6.0.0',
                        'plugin_version' => $sourceData['plugin_version'] ?? '1.0.0',
                        'settings' => [],
                        'is_active' => true,
                    ]);
                    $this->info("✅ Domain created with API key: {$domain->api_key}");
                }
            }
            
            if (!$domain) {
                // Use first active domain
                $domain = Domain::where('is_active', true)->first();
                
                if (!$domain) {
                    $this->error('❌ No active domain found. Use --create-domain to create one.');
                    return 1;
                }
            }
        }

        $this->info("🌐 Using domain: {$domain->name}");
        $this->info("🔑 API Key: {$domain->api_key}");
        $this->newLine();

        // 3. Submit report using CURL
        $this->info("📡 Submitting report via API...");
        $this->info("📦 Data size: " . strlen(json_encode($reportData)) . " bytes");
        $this->newLine();

        try {
            $endpoint = "{$baseUrl}/api/reports/submit";
            $this->info("⏳ Processing request to: {$endpoint}");
            
            // Prepare JSON payload
            $jsonPayload = json_encode($reportData, JSON_UNESCAPED_SLASHES);
            
            // Create temporary file for payload
            $tempFile = tempnam(sys_get_temp_dir(), 'report_');
            file_put_contents($tempFile, $jsonPayload);
            
            // Execute CURL command
            $curlCommand = sprintf(
                'curl -X POST %s -H "X-API-Key: %s" -H "Content-Type: application/json" -H "Accept: application/json" -d @%s 2>/dev/null',
                escapeshellarg($endpoint),
                escapeshellarg($domain->api_key),
                escapeshellarg($tempFile)
            );
            
            $output = shell_exec($curlCommand);
            
            // Clean up temp file
            unlink($tempFile);
            
            if (!$output) {
                $this->error('❌ No response from API');
                return 1;
            }
            
            $responseData = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('❌ Invalid JSON response');
                $this->line('Raw response: ' . $output);
                return 1;
            }

            // 4. Display response
            $this->newLine();
            
            if (isset($responseData['success']) && $responseData['success']) {
                $this->info('✅ Report submitted successfully!');
                $this->newLine();
                
                $this->line('Response:');
                $this->line(json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
                if (isset($responseData['data']['report_id'])) {
                    $this->newLine();
                    $this->info("🎉 Report ID: {$responseData['data']['report_id']}");
                }
                
                if (isset($responseData['data']['report_date'])) {
                    $this->info("📅 Report Date: {$responseData['data']['report_date']}");
                }
                
                if (isset($responseData['data']['status'])) {
                    $this->info("📊 Status: {$responseData['data']['status']}");
                }
                
                return 0;
            } else {
                $this->error('❌ Request failed!');
                $this->newLine();
                $this->line('Response:');
                $this->line(json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
                if (isset($responseData['errors'])) {
                    $this->newLine();
                    $this->error('Validation Errors:');
                    foreach ($responseData['errors'] as $field => $errors) {
                        if (is_array($errors)) {
                            $this->error("  • {$field}: " . implode(', ', $errors));
                        } else {
                            $this->error("  • {$field}: {$errors}");
                        }
                    }
                }
                
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            $this->newLine();
            $this->error('Class: ' . get_class($e));
            return 1;
        }
    }
}
