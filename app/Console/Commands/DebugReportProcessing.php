<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Models\ReportSummary;
use App\Models\ReportProvider;
use App\Models\ReportState;
use App\Models\ReportCity;
use App\Models\ReportZipCode;
use App\Models\State;
use App\Models\City;
use App\Models\ZipCode;
use App\Models\Provider;

class DebugReportProcessing extends Command
{
    protected $signature = 'report:debug 
                            {report_id? : ID do relatório para debugar (opcional)}
                            {--latest : Debugar o relatório mais recente}
                            {--full : Mostrar todos os detalhes}';

    protected $description = 'Debug detalhado do processamento de um relatório';

    public function handle(): int
    {
        $this->info('🔍 DEBUG DE PROCESSAMENTO DE RELATÓRIO');
        $this->newLine();

        // 1. Encontrar relatório
        $reportId = $this->argument('report_id');
        
        if ($this->option('latest')) {
            $report = Report::latest()->first();
            if (!$report) {
                $this->error('❌ Nenhum relatório encontrado no banco');
                return 1;
            }
            $reportId = $report->id;
        }
        
        if (!$reportId) {
            $this->error('❌ Forneça um report_id ou use --latest');
            $this->info('Uso: php artisan report:debug {report_id}');
            $this->info('  ou: php artisan report:debug --latest');
            return 1;
        }

        $report = Report::with(['domain', 'summary'])->find($reportId);
        
        if (!$report) {
            $this->error("❌ Relatório #{$reportId} não encontrado");
            return 1;
        }

        // 2. Informações básicas do relatório
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("📄 RELATÓRIO #{$report->id}");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $report->id],
                ['Domínio', $report->domain->name ?? 'N/A'],
                ['Domain ID', $report->domain_id],
                ['Data do Relatório', $report->report_date],
                ['Status', $this->getStatusEmoji($report->status) . ' ' . $report->status],
                ['Versão', $report->data_version ?? 'N/A'],
                ['Criado em', $report->created_at],
                ['Atualizado em', $report->updated_at],
            ]
        );

        // 3. Summary
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📈 RESUMO ESTATÍSTICO');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($report->summary) {
            $summary = $report->summary;
            $this->newLine();
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Total de Requisições', number_format($summary->total_requests)],
                    ['Taxa de Sucesso', round($summary->success_rate, 2) . '%'],
                    ['Requisições Falhadas', number_format($summary->failed_requests)],
                    ['Média por Hora', round($summary->avg_requests_per_hour, 2)],
                    ['Providers Únicos', $summary->unique_providers ?? 0],
                    ['Estados Únicos', $summary->unique_states ?? 0],
                    ['CEPs Únicos', $summary->unique_zip_codes ?? 0],
                ]
            );
        } else {
            $this->warn('⚠️  Nenhum resumo processado ainda');
        }

        // 4. Providers processados
        $this->newLine();
        $providersCount = ReportProvider::where('report_id', $report->id)->count();
        $this->info("📡 PROVEDORES PROCESSADOS: {$providersCount}");
        
        if ($providersCount > 0) {
            $topProviders = ReportProvider::where('report_id', $report->id)
                ->orderBy('rank_position')
                ->limit(5)
                ->get();
            
            if ($topProviders->count() > 0) {
                $this->newLine();
                $this->table(
                    ['Rank', 'Provider ID', 'Total', 'Tecnologia'],
                    $topProviders->map(fn($p) => [
                        $p->rank_position ?? '-',
                        $p->provider_id,
                        $p->total_count,
                        $p->technology ?? 'N/A'
                    ])->toArray()
                );
            }
        }

        // 5. Estados processados
        $this->newLine();
        $statesCount = ReportState::where('report_id', $report->id)->count();
        $this->info("🗺️  ESTADOS PROCESSADOS: {$statesCount}");
        
        if ($statesCount > 0 && $this->option('full')) {
            $topStates = ReportState::where('report_id', $report->id)
                ->orderBy('request_count', 'desc')
                ->limit(10)
                ->get();
            
            $this->newLine();
            $this->table(
                ['State ID', 'Requests', 'Success Rate'],
                $topStates->map(fn($s) => [
                    $s->state_id,
                    $s->request_count,
                    round($s->success_rate, 2) . '%'
                ])->toArray()
            );
        }

        // 6. Cidades processadas
        $this->newLine();
        $citiesCount = ReportCity::where('report_id', $report->id)->count();
        $this->info("🏙️  CIDADES PROCESSADAS: {$citiesCount}");

        // 7. CEPs processados
        $this->newLine();
        $zipCodesCount = ReportZipCode::where('report_id', $report->id)->count();
        $this->info("📮 CEPs PROCESSADOS: {$zipCodesCount}");

        // 8. Dados mestres criados
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('💾 DADOS MESTRES NO BANCO');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        $this->table(
            ['Tabela', 'Total'],
            [
                ['States', State::count()],
                ['Cities', City::count()],
                ['ZipCodes', ZipCode::count()],
                ['Providers', Provider::count()],
            ]
        );

        // 9. Verificar se há erros
        $this->newLine();
        if ($report->status === 'failed') {
            $this->error('⚠️  RELATÓRIO FALHOU NO PROCESSAMENTO');
            $this->warn('Verifique os logs para mais detalhes:');
            $this->line('docker-compose logs app | grep "Report.*' . $report->id . '"');
        } elseif ($report->status === 'pending') {
            $this->warn('⏳ RELATÓRIO AGUARDANDO PROCESSAMENTO');
            $this->info('Execute o worker da queue:');
            $this->line('docker-compose exec app php artisan queue:work --once');
        } elseif ($report->status === 'processed') {
            $this->info('✅ RELATÓRIO PROCESSADO COM SUCESSO!');
        }

        $this->newLine();
        
        // 10. Sugestões
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('💡 COMANDOS ÚTEIS');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        $this->line('Ver raw data completo:');
        $this->comment("  php artisan tinker --execute=\"App\Models\Report::find({$report->id})->raw_data\"");
        $this->newLine();
        
        $this->line('Reprocessar relatório:');
        $this->comment("  App\Jobs\ProcessReportJob::dispatch({$report->id}, \$rawData);");
        $this->newLine();
        
        $this->line('Ver via API:');
        $this->comment("  curl -s http://localhost:8006/api/admin/reports/{$report->id} -H \"Authorization: Bearer \$TOKEN\" | jq '.'");
        $this->newLine();

        return 0;
    }

    private function getStatusEmoji(string $status): string
    {
        return match($status) {
            'pending' => '⏳',
            'processing' => '🔄',
            'processed' => '✅',
            'failed' => '❌',
            default => '❓',
        };
    }
}

