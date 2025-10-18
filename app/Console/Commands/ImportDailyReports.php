<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Domain;
use App\Models\Report;
use App\Jobs\ProcessReportJob;
use Carbon\Carbon;

class ImportDailyReports extends Command
{
    protected $signature = 'reports:import-daily 
                            {--directory=docs/daily_reports : Directory containing daily report files}
                            {--domain=zip.50g.io : Domain name to import reports for}
                            {--dry-run : Show what would be imported without actually importing}
                            {--force : Force import even if reports already exist}
                            {--date-from= : Start date (YYYY-MM-DD)}
                            {--date-to= : End date (YYYY-MM-DD)}';

    protected $description = 'Import daily reports from JSON files in bulk';

    public function handle(): int
    {
        $directory = $this->option('directory');
        $domainName = $this->option('domain');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        $this->info('📊 IMPORTADOR DE RELATÓRIOS DIÁRIOS');
        $this->newLine();
        
        // 1. Verificar diretório
        if (!File::isDirectory($directory)) {
            $this->error("❌ Diretório não encontrado: {$directory}");
            return 1;
        }

        // 2. Buscar ou criar domínio
        $domain = Domain::where('name', $domainName)->first();
        if (!$domain) {
            $this->error("❌ Domínio não encontrado: {$domainName}");
            $this->info("💡 Use --domain=nome-do-dominio ou crie o domínio primeiro");
            return 1;
        }

        $this->info("🌐 Domínio: {$domain->name} (ID: {$domain->id})");
        $this->newLine();

        // 3. Buscar arquivos JSON
        $files = File::glob($directory . '/*.json');
        if (empty($files)) {
            $this->error("❌ Nenhum arquivo JSON encontrado em: {$directory}");
            return 1;
        }

        $this->info("📁 Encontrados " . count($files) . " arquivos JSON");
        $this->newLine();

        // 4. Filtrar por data se especificado
        $filteredFiles = $this->filterFilesByDate($files, $dateFrom, $dateTo);
        $this->info("📅 Arquivos após filtro de data: " . count($filteredFiles));
        $this->newLine();

        // 5. Processar arquivos
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($filteredFiles as $file) {
            $result = $this->processFile($file, $domain, $dryRun, $force);
            
            switch ($result['status']) {
                case 'imported':
                    $imported++;
                    $this->info("✅ {$result['message']}");
                    break;
                case 'skipped':
                    $skipped++;
                    $this->warn("⏭️  {$result['message']}");
                    break;
                case 'error':
                    $errors++;
                    $this->error("❌ {$result['message']}");
                    break;
            }
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("📊 RESUMO DA IMPORTAÇÃO:");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        if ($dryRun) {
            $this->warn("🔍 MODO DRY-RUN (nenhum dado foi importado)");
            $this->newLine();
        }

        $this->table(
            ['Status', 'Quantidade'],
            [
                ['Importados', $imported],
                ['Ignorados', $skipped],
                ['Erros', $errors],
                ['Total', count($filteredFiles)],
            ]
        );

        if ($errors > 0) {
            $this->newLine();
            $this->error("⚠️  {$errors} arquivos tiveram erro. Verifique os logs acima.");
            return 1;
        }

        if ($dryRun) {
            $this->newLine();
            $this->info("💡 Para importar realmente, execute sem --dry-run");
        } else {
            $this->newLine();
            $this->info("🎉 Importação concluída! {$imported} relatórios processados.");
            $this->info("💡 Os jobs estão sendo processados em background.");
        }

        return 0;
    }

    private function filterFilesByDate(array $files, ?string $dateFrom, ?string $dateTo): array
    {
        if (!$dateFrom && !$dateTo) {
            return $files;
        }

        $filtered = [];
        foreach ($files as $file) {
            $filename = basename($file, '.json');
            
            // Extrair data do nome do arquivo (formato: YYYY-MM-DD.json)
            if (preg_match('/^(\d{4}-\d{2}-\d{2})$/', $filename, $matches)) {
                $fileDate = $matches[1];
                
                $include = true;
                
                if ($dateFrom && $fileDate < $dateFrom) {
                    $include = false;
                }
                
                if ($dateTo && $fileDate > $dateTo) {
                    $include = false;
                }
                
                if ($include) {
                    $filtered[] = $file;
                }
            }
        }

        return $filtered;
    }

    private function processFile(string $file, Domain $domain, bool $dryRun, bool $force): array
    {
        $filename = basename($file);
        
        try {
            // 1. Ler e validar JSON
            $content = File::get($file);
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'status' => 'error',
                    'message' => "{$filename}: JSON inválido - " . json_last_error_msg()
                ];
            }

            // 2. Validar estrutura
            if (!isset($data['data']['date'])) {
                return [
                    'status' => 'error',
                    'message' => "{$filename}: Campo 'data.date' não encontrado"
                ];
            }

            $reportDate = $data['data']['date'];

            // 3. Verificar se já existe
            $existingReport = Report::where('domain_id', $domain->id)
                ->whereDate('report_date', $reportDate)
                ->first();

            if ($existingReport && !$force) {
                return [
                    'status' => 'skipped',
                    'message' => "{$filename}: Relatório para {$reportDate} já existe (use --force para sobrescrever)"
                ];
            }

            // 4. Converter para formato esperado pelo sistema
            $convertedData = $this->convertToSystemFormat($data, $reportDate);

            if ($dryRun) {
                return [
                    'status' => 'imported',
                    'message' => "{$filename}: Seria importado para {$reportDate} (DRY RUN)"
                ];
            }

            // 5. Criar/atualizar relatório
            if ($existingReport) {
                $existingReport->update([
                    'raw_data' => $convertedData,
                    'status' => 'pending',
                ]);
                
                // Limpar dados processados antigos
                $this->clearProcessedData($existingReport->id);
                
                $report = $existingReport;
                $action = "Atualizado";
            } else {
                $report = Report::create([
                    'domain_id' => $domain->id,
                    'report_date' => $reportDate,
                    'report_period_start' => Carbon::parse($reportDate)->startOfDay(),
                    'report_period_end' => Carbon::parse($reportDate)->endOfDay(),
                    'generated_at' => Carbon::parse($data['timestamp'] ?? now()),
                    'total_processing_time' => 0,
                    'data_version' => $data['api_version'] ?? '1.0',
                    'raw_data' => $convertedData,
                    'status' => 'pending',
                ]);
                $action = "Criado";
            }

            // 6. Enfileirar processamento
            ProcessReportJob::dispatch($report->id, $convertedData);

            return [
                'status' => 'imported',
                'message' => "{$filename}: {$action} relatório #{$report->id} para {$reportDate}"
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => "{$filename}: Erro - " . $e->getMessage()
            ];
        }
    }

    private function convertToSystemFormat(array $data, string $reportDate): array
    {
        // Converter formato diário para formato esperado pelo sistema
        return [
            'source' => [
                'domain' => 'zip.50g.io',
                'site_id' => $data['source']['site_id'] ?? 'wp-zip-daily-test',
                'site_name' => $data['source']['site_name'] ?? 'SmarterHome.ai',
            ],
            'metadata' => [
                'report_date' => $reportDate,
                'data_version' => $data['api_version'] ?? '1.0',
                'generated_at' => $data['timestamp'] ?? now()->toISOString(),
                'report_period' => [
                    'start' => Carbon::parse($reportDate)->startOfDay()->toISOString(),
                    'end' => Carbon::parse($reportDate)->endOfDay()->toISOString(),
                ],
                'total_processing_time' => 0,
            ],
            'summary' => [
                'total_requests' => $data['data']['summary']['total_requests'] ?? 0,
                'failed_requests' => $data['data']['summary']['failed_requests'] ?? 0,
                'success_rate' => $data['data']['summary']['success_rate'] ?? 0,
                'unique_providers' => $data['data']['summary']['unique_providers'] ?? 0,
                'unique_states' => $data['data']['summary']['unique_states'] ?? 0,
                'unique_zip_codes' => $data['data']['summary']['unique_zipcodes'] ?? 0,
                'avg_requests_per_hour' => ($data['data']['summary']['total_requests'] ?? 0) / 24,
            ],
            'providers' => $this->convertProviders($data),
            'geographic' => $this->convertGeographic($data),
            'performance' => $this->convertPerformance($data),
            'speed_metrics' => $this->convertSpeedMetrics($data),
            'exclusion_metrics' => $this->convertExclusionMetrics($data),
        ];
    }

    private function convertProviders(array $data): array
    {
        $providers = [];
        
        if (isset($data['data']['providers']['available'])) {
            foreach ($data['data']['providers']['available'] as $name => $count) {
                $providers[] = [
                    'name' => $name,
                    'technology' => $this->inferTechnology($name),
                    'total_count' => $count,
                    'success_rate' => 0,
                    'avg_speed' => 0,
                ];
            }
        }

        return [
            'top_providers' => $providers,
            'by_state' => [],
        ];
    }

    private function convertGeographic(array $data): array
    {
        $states = [];
        $cities = [];
        $zipCodes = [];

        // Converter estados
        if (isset($data['data']['geographic']['states'])) {
            foreach ($data['data']['geographic']['states'] as $code => $count) {
                $states[] = [
                    'code' => $code,
                    'name' => $this->getStateName($code),
                    'request_count' => $count,
                    'success_rate' => 0,
                    'avg_speed' => 0,
                ];
            }
        }

        // Converter cidades
        if (isset($data['data']['geographic']['cities'])) {
            foreach ($data['data']['geographic']['cities'] as $name => $count) {
                $cities[] = [
                    'name' => $name,
                    'request_count' => $count,
                    'zip_codes' => [],
                ];
            }
        }

        // Converter CEPs
        if (isset($data['data']['geographic']['zipcodes'])) {
            foreach ($data['data']['geographic']['zipcodes'] as $zip => $count) {
                $zipCodes[] = [
                    'zip_code' => $zip,
                    'request_count' => $count,
                    'percentage' => 0,
                ];
            }
        }

        return [
            'states' => $states,
            'top_cities' => $cities,
            'top_zip_codes' => $zipCodes,
        ];
    }

    private function convertPerformance(array $data): array
    {
        return [
            'search_types' => [
                'direct' => [
                    'count' => ($data['data']['summary']['total_requests'] ?? 0) * 0.8,
                    'avg_confidence' => 0,
                    'avg_response_time' => 0,
                ],
                'fallback' => [
                    'count' => ($data['data']['summary']['total_requests'] ?? 0) * 0.2,
                    'avg_confidence' => 0,
                    'avg_response_time' => 0,
                ],
            ],
            'hourly_distribution' => $this->generateHourlyDistribution($data),
        ];
    }

    private function convertSpeedMetrics(array $data): array
    {
        return [
            'overall' => [
                'avg' => $data['data']['summary']['avg_speed_mbps'] ?? 0,
                'max' => $data['data']['summary']['max_speed_mbps'] ?? 0,
                'min' => $data['data']['summary']['min_speed_mbps'] ?? 0,
            ],
            'by_state' => [],
            'by_provider' => [],
        ];
    }

    private function convertExclusionMetrics(array $data): array
    {
        $exclusions = [];
        
        if (isset($data['data']['providers']['excluded'])) {
            foreach ($data['data']['providers']['excluded'] as $provider => $count) {
                $exclusions[$provider] = $count;
            }
        }

        return [
            'by_provider' => $exclusions,
            'distribution' => [],
        ];
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

    private function generateHourlyDistribution(array $data): array
    {
        $totalRequests = $data['data']['summary']['total_requests'] ?? 0;
        $hourlyData = [];
        
        // Gerar distribuição simulada baseada no total
        for ($hour = 0; $hour < 24; $hour++) {
            $baseCount = $totalRequests / 24;
            $variation = sin(($hour - 6) * M_PI / 12) * 0.5 + 1; // Pico às 18h
            $count = max(0, round($baseCount * $variation));
            
            $hourlyData[] = [
                'hour' => $hour,
                'count' => $count,
            ];
        }

        return $hourlyData;
    }

    private function getStateName(string $code): string
    {
        $states = [
            'CA' => 'California', 'NY' => 'New York', 'TX' => 'Texas', 'FL' => 'Florida',
            'OH' => 'Ohio', 'NH' => 'New Hampshire', 'TN' => 'Tennessee', 'MO' => 'Missouri',
            'MA' => 'Massachusetts', 'NV' => 'Nevada', 'AL' => 'Alabama', 'WI' => 'Wisconsin',
            'WV' => 'West Virginia', 'MN' => 'Minnesota', 'OK' => 'Oklahoma', 'CO' => 'Colorado',
            'MI' => 'Michigan', 'CT' => 'Connecticut', 'PA' => 'Pennsylvania', 'WA' => 'Washington',
            'KY' => 'Kentucky', 'GA' => 'Georgia', 'NC' => 'North Carolina', 'SC' => 'South Carolina',
            'VA' => 'Virginia', 'MD' => 'Maryland', 'DE' => 'Delaware', 'NJ' => 'New Jersey',
            'RI' => 'Rhode Island', 'VT' => 'Vermont', 'ME' => 'Maine', 'NH' => 'New Hampshire',
        ];

        return $states[$code] ?? $code;
    }

    private function clearProcessedData(int $reportId): void
    {
        \App\Models\ReportSummary::where('report_id', $reportId)->delete();
        \App\Models\ReportProvider::where('report_id', $reportId)->delete();
        \App\Models\ReportState::where('report_id', $reportId)->delete();
        \App\Models\ReportCity::where('report_id', $reportId)->delete();
        \App\Models\ReportZipCode::where('report_id', $reportId)->delete();
    }
}

