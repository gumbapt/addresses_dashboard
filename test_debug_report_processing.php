<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Domain;
use App\Application\UseCases\Report\CreateDailyReportUseCase;
use App\Application\Services\ReportProcessor;
use App\Jobs\ProcessReportJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "🔍 TESTE DE DEBUG - Processamento de Report\n";
echo str_repeat("=", 60) . "\n\n";

$reportData = [
    "source" => [
        "domain" => "zip.50g.io",
        "site_id" => "wp-zip.50g.io",
        "site_name" => "SmarterHome.ai",
        "site_url" => "https://zip.50g.io",
        "wordpress_version" => "6.8.3",
        "plugin_version" => "1.0.0"
    ],
    "metadata" => [
        "report_date" => "2025-11-23",
        "report_period" => [
            "start" => "2025-11-23 00:00:00",
            "end" => "2025-11-23 23:59:59"
        ],
        "generated_at" => "2025-11-23 12:31:06",
        "total_processing_time" => 0,
        "data_version" => "2.0.0"
    ],
    "summary" => [
        "total_requests" => 1,
        "successful_requests" => 1,
        "failed_requests" => 0,
        "success_rate" => 100,
        "avg_requests_per_hour" => 0.04,
        "unique_providers" => 11,
        "unique_states" => 1,
        "unique_cities" => 1,
        "unique_zip_codes" => 1,
        "avg_speed_mbps" => 1038.02,
        "max_speed_mbps" => 10000,
        "min_speed_mbps" => 10
    ],
    "technology_metrics" => [
        "distribution" => [
            "Mobile Wireless" => 18,
            "Fiber" => 12,
            "DSL" => 6,
            "Cable" => 4,
            "Satellite" => 3
        ]
    ],
    "providers" => [
        "top_providers" => [
            [
                "name" => "AT&T",
                "total_count" => 1,
                "technology" => "Mobile"
            ],
            [
                "name" => "Spectrum",
                "total_count" => 1,
                "technology" => "Fiber"
            ]
        ]
    ],
    "geographic" => [
        "states" => [
            [
                "code" => "NC",
                "name" => "North Carolina",
                "request_count" => 1,
                "success_rate" => 100,
                "avg_speed" => 1038.02,
                "providers" => [
                    [
                        "name" => "AT&T",
                        "count" => 1
                    ],
                    [
                        "name" => "Spectrum",
                        "count" => 1
                    ],
                    [
                        "name" => "T-Mobile",
                        "count" => 1
                    ]
                ]
            ]
        ],
        "top_cities" => [
            [
                "name" => "Charlotte",
                "request_count" => 1,
                "zip_codes" => []
            ]
        ],
        "top_zip_codes" => [
            [
                "zip_code" => "28202",
                "request_count" => 1,
                "percentage" => 100
            ]
        ]
    ],
    "performance" => [
        "hourly_distribution" => [
            "13" => 1
        ]
    ],
    "api_version" => "2.0.0",
    "report_type" => "daily",
    "timestamp" => "2025-11-23 12:31:06",
    "data" => [
        "date" => "2025-11-23"
    ]
];

try {
    echo "📋 PASSO 1: Verificando domínio...\n";
    $domain = Domain::where('name', 'zip.50g.io')->first();
    
    if (!$domain) {
        echo "❌ Domínio não encontrado!\n";
        exit(1);
    }
    
    echo "✅ Domínio encontrado: ID {$domain->id}\n\n";
    
    echo "📋 PASSO 2: Verificando dados ANTES do Use Case...\n";
    echo "   - geographic.states existe: " . (isset($reportData['geographic']['states']) ? '✅ SIM' : '❌ NÃO') . "\n";
    echo "   - geographic.states[0].providers existe: " . (isset($reportData['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
    if (isset($reportData['geographic']['states'][0]['providers'])) {
        echo "   - providers count: " . count($reportData['geographic']['states'][0]['providers']) . "\n";
        echo "   - providers: " . json_encode($reportData['geographic']['states'][0]['providers']) . "\n";
    }
    echo "\n";
    
    echo "📋 PASSO 3: Criando report via Use Case...\n";
    $createDailyReportUseCase = app(CreateDailyReportUseCase::class);
    $report = $createDailyReportUseCase->execute($domain->id, $reportData);
    
    echo "✅ Report criado: ID {$report->getId()}\n\n";
    
    echo "📋 PASSO 4: Verificando raw_data salvo no banco...\n";
    $reportModel = \App\Models\Report::find($report->getId());
    
    if (!$reportModel) {
        echo "❌ Report não encontrado no banco!\n";
        exit(1);
    }
    
    $rawData = $reportModel->raw_data;
    
    echo "   - raw_data.geographic.states existe: " . (isset($rawData['geographic']['states']) ? '✅ SIM' : '❌ NÃO') . "\n";
    echo "   - raw_data.geographic.states[0].providers existe: " . (isset($rawData['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
    
    if (isset($rawData['geographic']['states'][0]['providers'])) {
        echo "   - providers count no raw_data: " . count($rawData['geographic']['states'][0]['providers']) . "\n";
        echo "   - providers no raw_data: " . json_encode($rawData['geographic']['states'][0]['providers']) . "\n";
    } else {
        echo "   ⚠️  PROBLEMA: Campo providers NÃO está no raw_data!\n";
        echo "   - Estrutura do primeiro estado: " . json_encode($rawData['geographic']['states'][0] ?? []) . "\n";
    }
    echo "\n";
    
    echo "📋 PASSO 5: Simulando ProcessReportJob...\n";
    echo "   (Buscando report do banco e usando raw_data)\n\n";
    
    // Simular o que o ProcessReportJob faz
    $reportModelForJob = \App\Models\Report::find($report->getId());
    $reportDataForJob = $reportModelForJob->raw_data ?? $reportData;
    
    echo "   - reportData usado pelo job tem geographic.states: " . (isset($reportDataForJob['geographic']['states']) ? '✅ SIM' : '❌ NÃO') . "\n";
    echo "   - reportData usado pelo job tem providers: " . (isset($reportDataForJob['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
    
    if (isset($reportDataForJob['geographic']['states'][0]['providers'])) {
        echo "   - providers count no reportData do job: " . count($reportDataForJob['geographic']['states'][0]['providers']) . "\n";
    }
    echo "\n";
    
    echo "📋 PASSO 6: Processando report...\n";
    $processor = app(ReportProcessor::class);
    
    // Limpar dados anteriores se existirem
    \App\Models\ReportStateProvider::where('report_id', $reportModel->id)->delete();
    
    $processor->process($reportModel->id, $reportDataForJob);
    
    echo "✅ Report processado!\n\n";
    
    echo "📋 PASSO 7: Verificando tabela report_state_providers...\n";
    $stateProviders = \App\Models\ReportStateProvider::where('report_id', $reportModel->id)->get();
    
    echo "   - Registros salvos: " . $stateProviders->count() . "\n";
    
    if ($stateProviders->count() > 0) {
        echo "\n   ✅ SUCESSO! Dados salvos:\n";
        foreach ($stateProviders as $sp) {
            echo "      - {$sp->provider->name} em {$sp->state->code}: {$sp->request_count} requests\n";
        }
    } else {
        echo "\n   ❌ PROBLEMA: Nenhum registro encontrado na tabela!\n";
        echo "\n   🔍 Verificando logs recentes...\n";
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $lastLines = array_slice($lines, -30);
            foreach ($lastLines as $line) {
                if (strpos($line, 'Processing state providers') !== false || 
                    strpos($line, 'report_id') !== false ||
                    strpos($line, 'convertGeographic') !== false) {
                    echo "      " . trim($line) . "\n";
                }
            }
        }
    }
    
    echo "\n";
    echo "📋 PASSO 8: Resumo final...\n";
    echo "   - Report ID: {$reportModel->id}\n";
    echo "   - Status: {$reportModel->status}\n";
    echo "   - Providers no raw_data: " . (isset($rawData['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
    echo "   - Registros em report_state_providers: {$stateProviders->count()}\n";
    
    if (!isset($rawData['geographic']['states'][0]['providers'])) {
        echo "\n   ⚠️  PROBLEMA IDENTIFICADO:\n";
        echo "      O campo 'providers' não está sendo preservado no raw_data!\n";
        echo "      Verifique os logs do CreateDailyReportUseCase.\n";
    } elseif ($stateProviders->count() == 0) {
        echo "\n   ⚠️  PROBLEMA IDENTIFICADO:\n";
        echo "      O campo 'providers' está no raw_data, mas não está sendo processado!\n";
        echo "      Verifique os logs do ReportProcessor.\n";
    } else {
        echo "\n   ✅ TUDO FUNCIONANDO CORRETAMENTE!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📍 " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n📋 Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

