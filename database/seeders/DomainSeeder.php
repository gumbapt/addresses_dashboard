<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Domain;
use Illuminate\Support\Str;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $domains = [
            [
                'name' => 'zip.50g.io',
                'slug' => 'zip-50g-io',
                'domain_url' => 'http://zip.50g.io',
                'site_id' => 'wp-zip-daily-test',
                'api_key' => Str::random(32),
                'status' => 'active',
                'timezone' => 'America/New_York',
                'wordpress_version' => '6.8.3',
                'plugin_version' => '1.0.0',
                'settings' => json_encode([
                    'enable_notifications' => true,
                    'auto_submit' => true,
                    'retention_days' => 90,
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'smarterhome.ai',
                'slug' => 'smarterhome-ai',
                'domain_url' => 'https://smarterhome.ai',
                'site_id' => 'wp-smarterhome-prod',
                'api_key' => Str::random(32),
                'status' => 'active',
                'timezone' => 'America/Los_Angeles',
                'wordpress_version' => '6.8.1',
                'plugin_version' => '1.0.0',
                'settings' => json_encode([
                    'enable_notifications' => true,
                    'auto_submit' => true,
                    'retention_days' => 60,
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'ispfinder.net',
                'slug' => 'ispfinder-net',
                'domain_url' => 'https://ispfinder.net',
                'site_id' => 'wp-ispfinder-main',
                'api_key' => Str::random(32),
                'status' => 'active',
                'timezone' => 'America/Chicago',
                'wordpress_version' => '6.7.5',
                'plugin_version' => '0.9.8',
                'settings' => json_encode([
                    'enable_notifications' => false,
                    'auto_submit' => true,
                    'retention_days' => 30,
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'broadbandcheck.io',
                'slug' => 'broadbandcheck-io',
                'domain_url' => 'https://broadbandcheck.io',
                'site_id' => 'wp-broadband-checker',
                'api_key' => Str::random(32),
                'status' => 'active',
                'timezone' => 'America/Denver',
                'wordpress_version' => '6.8.2',
                'plugin_version' => '1.0.1',
                'settings' => json_encode([
                    'enable_notifications' => true,
                    'auto_submit' => false,
                    'retention_days' => 120,
                ]),
                'is_active' => true,
            ],
        ];

        foreach ($domains as $domainData) {
            $domain = Domain::firstOrCreate(
                ['name' => $domainData['name']],
                $domainData
            );

            if ($domain->wasRecentlyCreated) {
                $this->command->info("✅ Domínio criado: {$domain->name} (API Key: {$domain->api_key})");
            } else {
                $this->command->info("ℹ️  Domínio já existe: {$domain->name}");
            }
        }

        $this->command->newLine();
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📊 RESUMO DOS DOMÍNIOS:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();

        $allDomains = Domain::all();
        foreach ($allDomains as $domain) {
            $this->command->info("🌐 {$domain->name}");
            $this->command->line("   ID: {$domain->id}");
            $this->command->line("   API Key: {$domain->api_key}");
            $this->command->line("   Status: {$domain->status}");
            $this->command->newLine();
        }
    }
}
