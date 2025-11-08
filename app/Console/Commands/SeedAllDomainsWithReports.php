<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Domain;
use App\Application\UseCases\Report\CreateDailyReportUseCase;
use App\Jobs\ProcessReportJob;
use App\Models\Report;

class SeedAllDomainsWithReports extends Command
{
    public function __construct(
        private CreateDailyReportUseCase $createDailyReportUseCase
    ) {
        parent::__construct();
    }

    protected $signature = 'reports:seed-all-domains 
                            {--directory=docs/daily_reports : Directory containing daily report files}
                            {--real-group=production : The group slug that gets real data without modification}
                            {--dry-run : Show what would be done without actually doing it}
                            {--force : Force submit even if reports already exist}
                            {--date-from= : Start date (YYYY-MM-DD)}
                            {--date-to= : End date (YYYY-MM-DD)}
                            {--limit= : Maximum number of files to process per domain}
                            {--sync : Process reports synchronously without using queue}';

    protected $description = 'Seed all domains with report data (real data for 50g, synthetic for others)';

    public function handle(): int
    {
        $directory = $this->option('directory');
        $realGroupSlug = $this->option('real-group');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        $limit = $this->option('limit');
        $sync = $this->option('sync');

        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║  📊 SEED DE RELATÓRIOS PARA TODOS OS DOMÍNIOS                 ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // 1. Get all active domains
        $domains = Domain::where('is_active', true)->get();
        if ($domains->isEmpty()) {
            $this->error('❌ Nenhum domínio ativo encontrado. Execute php artisan db:seed --class=DomainSeeder primeiro.');
            return 1;
        }

        $this->info("🌐 Encontrados {$domains->count()} domínios ativos:");
        foreach ($domains as $domain) {
            $isRealGroup = $domain->domainGroup && $domain->domainGroup->slug === $realGroupSlug;
            $groupName = $domain->domainGroup ? $domain->domainGroup->name : '[sem grupo]';
            $this->line("   • {$domain->name} (ID: {$domain->id}) - 📁 {$groupName} " . ($isRealGroup ? '📊 REAL' : '🎲 SYNTHETIC'));
        }
        $this->newLine();

        // 2. Read JSON files
        if (!File::exists($directory)) {
            $this->error("❌ Diretório não encontrado: {$directory}");
            return 1;
        }

        $files = File::files($directory);
        $reportFiles = [];
        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $reportFiles[] = $file->getPathname();
            }
        }
        sort($reportFiles);

        $this->info("📁 Encontrados " . count($reportFiles) . " arquivos JSON");
        $this->newLine();

        // Apply date filter
        $filteredFiles = $this->filterFilesByDate($reportFiles, $dateFrom, $dateTo);
        $this->info("📅 Arquivos após filtro de data: " . count($filteredFiles));

        // Apply limit
        if ($limit) {
            $filteredFiles = array_slice($filteredFiles, 0, $limit);
            $this->info("🔢 Limitado a {$limit} arquivos por domínio");
        }
        $this->newLine();

        if ($dryRun) {
            $this->info("━━━ EXECUTANDO TESTE (DRY RUN) ━━━");
            $this->newLine();
        }

        if ($sync) {
            $this->info("━━━ MODO SÍNCRONO ATIVADO (sem queue) ━━━");
            $this->newLine();
        }

        // 3. Process each domain
        $results = ['total_submitted' => 0, 'total_ignored' => 0, 'total_errors' => 0];
        
        foreach ($domains as $domain) {
            // Verificar se é do grupo "real" (production por padrão)
            $isRealGroup = $domain->domainGroup && $domain->domainGroup->slug === $realGroupSlug;
            
            // Mostrar informações do grupo
            $groupInfo = $domain->domainGroup 
                ? "📁 Grupo: {$domain->domainGroup->name}" 
                : "📁 Grupo: [nenhum]";
            
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("🌐 Processando domínio: {$domain->name}");
            $this->info("   Tipo: " . ($isRealGroup ? '📊 DADOS REAIS' : '🎲 DADOS SINTÉTICOS'));
            $this->info("   {$groupInfo}");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->newLine();

            $domainResults = $this->processDomain($domain, $filteredFiles, $isRealGroup, $dryRun, $force, $sync);
            
            $results['total_submitted'] += $domainResults['submitted'];
            $results['total_ignored'] += $domainResults['ignored'];
            $results['total_errors'] += $domainResults['errors'];
            
            $this->newLine();
        }

        // 4. Final summary
        $this->displayFinalSummary($results, $domains->count(), count($filteredFiles), $dryRun);

        return 0;
    }

    private function processDomain(Domain $domain, array $files, bool $isRealDomain, bool $dryRun, bool $force, bool $sync = false): array
    {
        $results = ['submitted' => 0, 'ignored' => 0, 'errors' => 0];
        $totalFiles = count($files);

        foreach ($files as $index => $filePath) {
            $filename = basename($filePath);
            $reportDate = basename($filePath, '.json');

            // Read and modify JSON data
            $jsonData = File::get($filePath);
            $data = json_decode($jsonData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $results['errors']++;
                $this->error("   ❌ {$filename}: JSON inválido");
                continue;
            }

            // Modify data if not real domain (synthetic data)
            if (!$isRealDomain) {
                $data = $this->synthesizeData($data, $domain);
            }

            // Check for existing report
            $existingReport = Report::where('domain_id', $domain->id)
                ->whereDate('report_date', $reportDate)
                ->first();

            if ($existingReport && !$force) {
                $results['ignored']++;
                if (($index + 1) % 10 == 0 || $index == 0 || $index == $totalFiles - 1) {
                    $this->line("   ⚠️  Processado " . ($index + 1) . "/{$totalFiles} (alguns ignorados, já existem)");
                }
                continue;
            }

            if ($dryRun) {
                $results['submitted']++;
                if (($index + 1) % 10 == 0 || $index == 0 || $index == $totalFiles - 1) {
                    $this->line("   📄 Processado " . ($index + 1) . "/{$totalFiles} (DRY RUN)");
                }
                continue;
            }

            // Create report
            try {
                $report = $this->createDailyReportUseCase->execute($domain->id, $data);
                
                // Buscar o report do banco para pegar o raw_data convertido
                $reportModel = \App\Models\Report::find($report->getId());
                
                if ($sync) {
                    // Processar sincronamente (sem queue) - para servidores sem workers
                    $processor = app(\App\Application\Services\ReportProcessor::class);
                    $processor->process($reportModel->id, $reportModel->raw_data);
                    
                    // Atualizar status para processed
                    $reportModel->update(['status' => 'processed']);
                } else {
                    // Usar queue (modo normal com workers)
                    ProcessReportJob::dispatch($report->getId(), $reportModel->raw_data);
                }
                
                $results['submitted']++;
                
                if (($index + 1) % 10 == 0 || $index == 0 || $index == $totalFiles - 1) {
                    $this->line("   ✅ Processado " . ($index + 1) . "/{$totalFiles}");
                }
            } catch (\Exception $e) {
                $results['errors']++;
                $this->error("   ❌ {$filename}: " . $e->getMessage());
            }
        }

        $this->info("   📊 Resumo para {$domain->name}:");
        $this->line("      Submetidos: {$results['submitted']}");
        $this->line("      Ignorados: {$results['ignored']}");
        $this->line("      Erros: {$results['errors']}");

        return $results;
    }

    private function synthesizeData(array $data, Domain $domain): array
    {
        // Carregar o grupo do domínio (se houver)
        $domainGroup = $domain->domainGroup;
        
        // Profiles por GRUPO (simples)
        $groupProfiles = [
            'production' => [
                'volume_multiplier' => 1.0,      // Dados reais (sem modificação)
                'success_bias' => 0,
                'state_focus' => [],
                'tech_preference' => null,
                'provider_shuffle' => 0,
            ],
            'testing' => [
                'volume_multiplier' => 1.5,      // 150% requests (ambiente de teste)
                'success_bias' => 0.02,           // +2% success rate
                'state_focus' => ['CA', 'NY', 'TX', 'FL'],
                'tech_preference' => 'Mixed',
                'provider_shuffle' => 0.5,
            ],
        ];
        
        // Fallback: profiles por nome de domínio (compatibilidade - para domínios sem grupo)
        $domainProfiles = [
            'zip.50g.io' => [
                'volume_multiplier' => 1.0,
                'success_bias' => 0,
                'state_focus' => [],
                'tech_preference' => null,
                'provider_shuffle' => 0,
            ],
            'fiberfinder.com' => [
                'volume_multiplier' => 1.0,
                'success_bias' => 0,
                'state_focus' => [],
                'tech_preference' => null,
                'provider_shuffle' => 0,
            ],
            'smarterhome.ai' => [
                'volume_multiplier' => 1.5,
                'success_bias' => 0.02,
                'state_focus' => ['CA', 'NY', 'TX', 'FL'],
                'tech_preference' => 'Mixed',
                'provider_shuffle' => 0.5,
            ],
            'ispfinder.net' => [
                'volume_multiplier' => 1.5,
                'success_bias' => 0.02,
                'state_focus' => ['CA', 'NY', 'TX', 'FL'],
                'tech_preference' => 'Mixed',
                'provider_shuffle' => 0.5,
            ],
            'broadbandcheck.io' => [
                'volume_multiplier' => 1.5,
                'success_bias' => 0.02,
                'state_focus' => ['CA', 'NY', 'TX', 'FL'],
                'tech_preference' => 'Mixed',
                'provider_shuffle' => 0.5,
            ],
        ];
        
        // Prioridade: 1) Profile do grupo, 2) Profile do domínio, 3) Default
        $profileSource = 'default';
        if ($domainGroup && isset($groupProfiles[$domainGroup->slug])) {
            $profile = $groupProfiles[$domainGroup->slug];
            $profileSource = "group:{$domainGroup->name}";
        } elseif (isset($domainProfiles[$domain->name])) {
            $profile = $domainProfiles[$domain->name];
            $profileSource = "domain:{$domain->name}";
        } else {
            $profile = [
                'volume_multiplier' => 1.0,
                'success_bias' => 0,
                'state_focus' => [],
                'tech_preference' => null,
                'provider_shuffle' => 0,
            ];
        }
        
        // Armazenar source no profile para debug/logging
        $profile['_profile_source'] = $profileSource;

        // Modify source information
        $data['source']['site_id'] = $domain->site_id;
        $data['source']['site_name'] = ucfirst(explode('.', $domain->name)[0]);
        $data['source']['site_url'] = $domain->domain_url;
        $data['source']['wordpress_version'] = $domain->wordpress_version;
        $data['source']['plugin_version'] = $domain->plugin_version;

        // Apply volume multiplier with some randomness
        $volumeMultiplier = $profile['volume_multiplier'] * (1 + (rand(-20, 20) / 100));
        
        // Modify summary metrics
        if (isset($data['data']['summary'])) {
            $totalRequests = $data['data']['summary']['total_requests'] ?? 0;
            $newTotal = (int) round($totalRequests * $volumeMultiplier);
            
            $data['data']['summary']['total_requests'] = $newTotal;
            
            // Adjust success rate based on bias
            if (isset($data['data']['summary']['success_rate'])) {
                $newSuccessRate = min(100, max(0, 
                    $data['data']['summary']['success_rate'] + ($profile['success_bias'] * 100)
                ));
                $data['data']['summary']['success_rate'] = round($newSuccessRate, 2);
                
                // Recalculate successful and failed requests
                $data['data']['summary']['successful_requests'] = (int) round($newTotal * ($newSuccessRate / 100));
                $data['data']['summary']['failed_requests'] = $newTotal - $data['data']['summary']['successful_requests'];
            }
            
            // Vary unique counts
            if (isset($data['data']['summary']['unique_providers'])) {
                $data['data']['summary']['unique_providers'] = (int) round(
                    $data['data']['summary']['unique_providers'] * (0.7 + rand(0, 60) / 100)
                );
            }
        }

        // Apply geographic focus
        if (isset($data['data']['geographic']['states']) && !empty($profile['state_focus'])) {
            $newStates = [];
            $totalCount = array_sum($data['data']['geographic']['states']);
            
            // Boost focused states
            foreach ($data['data']['geographic']['states'] as $state => $count) {
                $multiplier = in_array($state, $profile['state_focus']) ? 
                    (2.0 + rand(0, 100) / 100) : // 2-3x for focused states
                    (0.3 + rand(0, 40) / 100);    // 0.3-0.7x for others
                
                $newCount = (int) round($count * $multiplier * $volumeMultiplier);
                if ($newCount > 0) {
                    $newStates[$state] = $newCount;
                }
            }
            
            $data['data']['geographic']['states'] = $newStates;
        } else if (isset($data['data']['geographic']['states'])) {
            // Just apply volume multiplier
            foreach ($data['data']['geographic']['states'] as $state => $count) {
                $data['data']['geographic']['states'][$state] = (int) round($count * $volumeMultiplier);
            }
        }

        // Vary cities and zipcodes
        if (isset($data['data']['geographic']['cities'])) {
            foreach ($data['data']['geographic']['cities'] as $city => $count) {
                $data['data']['geographic']['cities'][$city] = (int) round($count * $volumeMultiplier * (0.5 + rand(0, 150) / 100));
            }
        }
        if (isset($data['data']['geographic']['zipcodes'])) {
            foreach ($data['data']['geographic']['zipcodes'] as $zip => $count) {
                $data['data']['geographic']['zipcodes'][$zip] = (int) round($count * $volumeMultiplier * (0.5 + rand(0, 150) / 100));
            }
        }

        // Shuffle and modify providers
        if (isset($data['data']['providers']['available'])) {
            $providers = $data['data']['providers']['available'];
            $newProviders = [];
            
            foreach ($providers as $provider => $count) {
                // Random chance to significantly boost or reduce provider
                if (rand(1, 100) / 100 < $profile['provider_shuffle']) {
                    $multiplier = rand(20, 300) / 100; // 0.2x to 3x
                } else {
                    $multiplier = $volumeMultiplier * (0.8 + rand(0, 40) / 100);
                }
                
                $newCount = (int) round($count * $multiplier);
                if ($newCount > 0) {
                    $newProviders[$provider] = $newCount;
                }
            }
            
            $data['data']['providers']['available'] = $newProviders;
        }
        
        if (isset($data['data']['providers']['excluded'])) {
            foreach ($data['data']['providers']['excluded'] as $provider => $count) {
                $multiplier = $volumeMultiplier * (0.5 + rand(0, 150) / 100);
                $data['data']['providers']['excluded'][$provider] = (int) round($count * $multiplier);
            }
        }

        // Vary speed metrics if present
        if (isset($data['data']['summary']['avg_speed_mbps'])) {
            $speedMultiplier = 0.6 + rand(0, 100) / 100; // 0.6x to 1.6x
            $data['data']['summary']['avg_speed_mbps'] = round($data['data']['summary']['avg_speed_mbps'] * $speedMultiplier, 2);
            
            if (isset($data['data']['summary']['max_speed_mbps'])) {
                $data['data']['summary']['max_speed_mbps'] = round($data['data']['summary']['max_speed_mbps'] * $speedMultiplier, 2);
            }
            if (isset($data['data']['summary']['min_speed_mbps'])) {
                $data['data']['summary']['min_speed_mbps'] = round($data['data']['summary']['min_speed_mbps'] * $speedMultiplier, 2);
            }
        }

        // Add speed metrics by state and provider
        $baseSpeed = $data['data']['summary']['avg_speed_mbps'] ?? 1000;
        
        if (!isset($data['speed_metrics'])) {
            $data['speed_metrics'] = [
                'overall' => [
                    'avg' => $baseSpeed,
                    'max' => $data['data']['summary']['max_speed_mbps'] ?? $baseSpeed * 2,
                    'min' => $data['data']['summary']['min_speed_mbps'] ?? $baseSpeed * 0.1,
                ],
            ];
        }
        
        $data['speed_metrics']['by_state'] = $this->generateSpeedByState($data, $baseSpeed, $profile);
        $data['speed_metrics']['by_provider'] = $this->generateSpeedByProvider($data, $baseSpeed, $profile);

        return $data;
    }

    private function generateSpeedByState(array $data, float $baseSpeed, array $profile): array
    {
        $speedByState = [];
        
        if (!isset($data['data']['geographic']['states'])) {
            return $speedByState;
        }

        foreach ($data['data']['geographic']['states'] as $state => $count) {
            // Focused states get better speeds
            $isFocused = in_array($state, $profile['state_focus'] ?? []);
            
            if ($isFocused) {
                $multiplier = 1.2 + rand(0, 30) / 100; // 1.2x to 1.5x for focused states
            } else {
                $multiplier = 0.7 + rand(0, 50) / 100; // 0.7x to 1.2x for others
            }
            
            $avgSpeed = round($baseSpeed * $multiplier, 2);
            $speedByState[$state] = [
                'state_code' => $state,
                'avg_speed' => $avgSpeed,
                'request_count' => $count,
            ];
        }

        return $speedByState;
    }

    private function generateSpeedByProvider(array $data, float $baseSpeed, array $profile): array
    {
        $speedByProvider = [];
        
        if (!isset($data['data']['providers']['available'])) {
            return $speedByProvider;
        }

        // Define technology speeds (relative to base)
        $techSpeeds = [
            'Fiber' => 2.0,      // 2x faster
            'Cable' => 1.5,      // 1.5x faster
            'Mobile' => 1.2,     // 1.2x faster
            'Satellite' => 0.6,  // 0.6x slower
            'DSL' => 0.5,        // 0.5x slower
            'Unknown' => 1.0,    // base speed
        ];

        foreach ($data['data']['providers']['available'] as $provider => $count) {
            // Infer technology from provider name
            $technology = $this->inferTechnology($provider);
            $techMultiplier = $techSpeeds[$technology] ?? 1.0;
            
            // Add some random variation
            $variation = 0.8 + rand(0, 40) / 100; // 0.8x to 1.2x
            
            $avgSpeed = round($baseSpeed * $techMultiplier * $variation, 2);
            
            $speedByProvider[$provider] = [
                'provider_name' => $provider,
                'technology' => $technology,
                'avg_speed' => $avgSpeed,
                'request_count' => $count,
            ];
        }

        return $speedByProvider;
    }

    private function inferTechnology(string $providerName): string
    {
        $mobileProviders = ['Verizon', 'AT&T', 'T-Mobile', 'Sprint'];
        $cableProviders = ['Spectrum', 'Xfinity', 'Cox Communications', 'Optimum'];
        $fiberProviders = ['Google Fiber', 'Verizon FiOS', 'AT&T Fiber'];
        $satelliteProviders = ['HughesNet', 'Viasat'];
        $dslProviders = ['CenturyLink', 'Frontier', 'Windstream'];

        $providerLower = strtolower($providerName);
        
        foreach ($mobileProviders as $mobile) {
            if (str_contains($providerLower, strtolower($mobile))) {
                return 'Mobile';
            }
        }
        
        foreach ($cableProviders as $cable) {
            if (str_contains($providerLower, strtolower($cable))) {
                return 'Cable';
            }
        }
        
        foreach ($fiberProviders as $fiber) {
            if (str_contains($providerLower, strtolower($fiber))) {
                return 'Fiber';
            }
        }
        
        foreach ($satelliteProviders as $satellite) {
            if (str_contains($providerLower, strtolower($satellite))) {
                return 'Satellite';
            }
        }
        
        foreach ($dslProviders as $dsl) {
            if (str_contains($providerLower, strtolower($dsl))) {
                return 'DSL';
            }
        }

        return 'Unknown';
    }

    private function filterFilesByDate(array $files, ?string $dateFrom, ?string $dateTo): array
    {
        $filtered = [];
        foreach ($files as $filePath) {
            $filename = basename($filePath, '.json');
            $fileDate = \DateTime::createFromFormat('Y-m-d', $filename);

            if ($fileDate === false) {
                continue;
            }

            if ($dateFrom && $fileDate < new \DateTime($dateFrom)) {
                continue;
            }
            if ($dateTo && $fileDate > new \DateTime($dateTo)) {
                continue;
            }
            $filtered[] = $filePath;
        }
        return $filtered;
    }

    private function displayFinalSummary(array $results, int $domainCount, int $fileCount, bool $dryRun): void
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RESUMO FINAL:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        if ($dryRun) {
            $this->comment('🔍 MODO DRY-RUN (nenhum dado foi submetido)');
            $this->newLine();
        }

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Domínios processados', $domainCount],
                ['Arquivos por domínio', $fileCount],
                ['Total de relatórios', $domainCount * $fileCount],
                ['Submetidos', $results['total_submitted']],
                ['Ignorados', $results['total_ignored']],
                ['Erros', $results['total_errors']],
            ]
        );
        $this->newLine();

        if ($results['total_errors'] > 0) {
            $this->error("⚠️  {$results['total_errors']} relatórios tiveram erro.");
            return;
        }

        $this->info("🎉 Seed concluído! {$results['total_submitted']} relatórios submetidos para {$domainCount} domínios.");
        $this->info("💡 Os jobs estão sendo processados em background.");
        $this->newLine();

        $this->info("━━━ PRÓXIMOS PASSOS ━━━");
        $this->newLine();
        $this->info("✅ Para verificar os dados:");
        $this->comment("docker-compose exec app php artisan tinker --execute=\"echo 'Total de reports: ' . App\Models\Report::count() . PHP_EOL; echo 'Reports por domínio: ' . PHP_EOL; App\Models\Domain::all()->each(fn(\$d) => print('  ' . \$d->name . ': ' . \$d->reports()->count() . ' reports' . PHP_EOL));\"");
        $this->newLine();
        $this->info("✅ Para testar o ranking global (quando implementado):");
        $this->comment("curl -s http://localhost:8006/api/admin/reports/global/domain-ranking -H \"Authorization: Bearer \$TOKEN\" | jq .");
        $this->newLine();
    }
}

