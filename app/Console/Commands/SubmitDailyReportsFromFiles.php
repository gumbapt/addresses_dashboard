<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Domain;
use App\Application\UseCases\Report\CreateDailyReportUseCase;
use App\Jobs\ProcessReportJob;

class SubmitDailyReportsFromFiles extends Command
{
    public function __construct(
        private CreateDailyReportUseCase $createDailyReportUseCase
    ) {
        parent::__construct();
    }

    protected $signature = 'reports:submit-daily-files 
                            {--directory=docs/daily_reports : Directory containing daily report files}
                            {--domain=zip.50g.io : Domain name to submit reports for}
                            {--dry-run : Show what would be submitted without actually submitting}
                            {--force : Force submit even if reports already exist}
                            {--date-from= : Start date (YYYY-MM-DD)}
                            {--date-to= : End date (YYYY-MM-DD)}
                            {--limit= : Maximum number of files to process}
                            {--delay=1 : Delay between submissions in seconds}';

    protected $description = 'Submit daily reports from JSON files one by one using the API endpoint';

    public function handle(): int
    {
        $directory = $this->option('directory');
        $domainName = $this->option('domain');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        $limit = $this->option('limit');
        $delay = (int) $this->option('delay');

        $this->info('📊 SUBMISSOR DE RELATÓRIOS DIÁRIOS VIA API');
        $this->newLine();

        // 1. Verificar diretório
        if (!File::isDirectory($directory)) {
            $this->error("❌ Diretório não encontrado: {$directory}");
            return 1;
        }

        // 2. Buscar domínio e API key
        $domain = Domain::where('name', $domainName)->first();
        if (!$domain) {
            $this->error("❌ Domínio não encontrado: {$domainName}");
            $this->info("💡 Use --domain=nome-do-dominio ou crie o domínio primeiro");
            return 1;
        }

        $this->info("🌐 Domínio: {$domain->name} (ID: {$domain->id})");
        $this->info("🔑 API Key: {$domain->api_key}");
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
        
        // 5. Aplicar limite se especificado
        if ($limit) {
            $filteredFiles = array_slice($filteredFiles, 0, (int) $limit);
            $this->info("🔢 Limitado a {$limit} arquivos");
        }
        
        $this->newLine();

        // 6. Processar arquivos
        $submitted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($filteredFiles as $index => $file) {
            $fileNumber = $index + 1;
            $totalFiles = count($filteredFiles);
            
            $this->info("📤 Processando arquivo {$fileNumber}/{$totalFiles}: " . basename($file));
            
            $result = $this->submitFile($file, $domain, $dryRun, $force);
            
            switch ($result['status']) {
                case 'submitted':
                    $submitted++;
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
            
            // Delay entre submissões
            if ($delay > 0 && $fileNumber < $totalFiles) {
                $this->info("⏳ Aguardando {$delay}s...");
                sleep($delay);
            }
            
            $this->newLine();
        }

        // 7. Resumo
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("📊 RESUMO DA SUBMISSÃO:");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        if ($dryRun) {
            $this->warn("🔍 MODO DRY-RUN (nenhum dado foi submetido)");
            $this->newLine();
        }

        $this->table(
            ['Status', 'Quantidade'],
            [
                ['Submetidos', $submitted],
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
            $this->info("💡 Para submeter realmente, execute sem --dry-run");
        } else {
            $this->newLine();
            $this->info("🎉 Submissão concluída! {$submitted} relatórios submetidos.");
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

    private function submitFile(string $file, Domain $domain, bool $dryRun, bool $force): array
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

            // 2. Validar estrutura básica
            if (!isset($data['data']['date'])) {
                return [
                    'status' => 'error',
                    'message' => "{$filename}: Campo 'data.date' não encontrado"
                ];
            }

            $reportDate = $data['data']['date'];

            // 3. Verificar se já existe (se não for force)
            if (!$force) {
                $existingReport = \App\Models\Report::where('domain_id', $domain->id)
                    ->whereDate('report_date', $reportDate)
                    ->first();

                if ($existingReport) {
                    return [
                        'status' => 'skipped',
                        'message' => "{$filename}: Relatório para {$reportDate} já existe (use --force para sobrescrever)"
                    ];
                }
            }

            if ($dryRun) {
                return [
                    'status' => 'submitted',
                    'message' => "{$filename}: Seria submetido para {$reportDate} (DRY RUN)"
                ];
            }

            // 4. Criar relatório diretamente usando o Use Case
            $report = $this->createDailyReportUseCase->execute($domain->id, $data);

            // 5. Enfileirar processamento
            ProcessReportJob::dispatch($report->getId(), $data);

            return [
                'status' => 'submitted',
                'message' => "{$filename}: Submetido com sucesso - Relatório #{$report->getId()} para {$reportDate}"
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => "{$filename}: Erro - " . $e->getMessage()
            ];
        }
    }
}
