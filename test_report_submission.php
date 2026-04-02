<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Domain;
use App\Application\UseCases\Report\CreateDailyReportUseCase;
use App\Application\Services\ReportProcessor;
use Illuminate\Support\Facades\Log;

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
        "generated_at" => "2025-11-23 09:17:06",
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
            ],
            [
                "name" => "T-Mobile",
                "total_count" => 1,
                "technology" => "Mobile"
            ],
            [
                "name" => "HughesNet",
                "total_count" => 1,
                "technology" => "Satellite"
            ],
            [
                "name" => "Mediacom Xtream",
                "total_count" => 1,
                "technology" => "Cable"
            ],
            [
                "name" => "ZAYO GROUP LLC",
                "total_count" => 1,
                "technology" => "Fiber"
            ],
            [
                "name" => "Verizon",
                "total_count" => 1,
                "technology" => "Mobile"
            ],
            [
                "name" => "Cogent Communication",
                "total_count" => 1,
                "technology" => "Fiber"
            ],
            [
                "name" => "Google Fiber",
                "total_count" => 1,
                "technology" => "Fiber"
            ],
            [
                "name" => "Viasat Carrier Services Inc",
                "total_count" => 1,
                "technology" => "Satellite"
            ],
            [
                "name" => "Earthlink",
                "total_count" => 1,
                "technology" => "Mobile"
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
                    ],
                    [
                        "name" => "HughesNet",
                        "count" => 1
                    ],
                    [
                        "name" => "Mediacom Xtream",
                        "count" => 1
                    ],
                    [
                        "name" => "ZAYO GROUP LLC",
                        "count" => 1
                    ],
                    [
                        "name" => "Verizon",
                        "count" => 1
                    ],
                    [
                        "name" => "Cogent Communication",
                        "count" => 1
                    ],
                    [
                        "name" => "Google Fiber",
                        "count" => 1
                    ],
                    [
                        "name" => "Viasat Carrier Services Inc",
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
    "timestamp" => "2025-11-23 09:17:06",
    "data" => [
        "date" => "2025-11-23"
    ]
];

try {
    echo "🔍 Buscando domínio zip.50g.io...\n";
    $domain = Domain::where('name', 'zip.50g.io')->first();
    
    if (!$domain) {
        echo "❌ Domínio não encontrado!\n";
        exit(1);
    }
    
    echo "✅ Domínio encontrado: ID {$domain->id}\n\n";
    
    echo "📝 Criando report...\n";
    $createDailyReportUseCase = app(CreateDailyReportUseCase::class);
    $report = $createDailyReportUseCase->execute($domain->id, $reportData);
    
    echo "✅ Report criado: ID {$report->getId()}\n\n";
    
    // Buscar o report do banco para pegar o raw_data
    $reportModel = \App\Models\Report::find($report->getId());
    
    if (!$reportModel) {
        echo "❌ Report não encontrado no banco!\n";
        exit(1);
    }
    
    echo "📊 Verificando raw_data...\n";
    $rawData = $reportModel->raw_data;
    
    // Verificar se o campo providers está no raw_data
    $hasProviders = isset($rawData['geographic']['states'][0]['providers']);
    $providersCount = $hasProviders ? count($rawData['geographic']['states'][0]['providers']) : 0;
    
    echo "   - Campo 'providers' no raw_data: " . ($hasProviders ? "✅ SIM ({$providersCount} providers)" : "❌ NÃO") . "\n";
    
    if ($hasProviders) {
        echo "   - Primeiro provider: " . ($rawData['geographic']['states'][0]['providers'][0]['name'] ?? 'N/A') . "\n";
    }
    
    echo "\n🔄 Processando report...\n";
    $processor = app(ReportProcessor::class);
    $processor->process($reportModel->id, $rawData);
    
    echo "✅ Report processado!\n\n";
    
    // Verificar se os dados foram salvos
    echo "🔍 Verificando tabela report_state_providers...\n";
    $stateProviders = \App\Models\ReportStateProvider::where('report_id', $reportModel->id)->get();
    
    echo "   - Registros salvos: " . $stateProviders->count() . "\n";
    
    if ($stateProviders->count() > 0) {
        echo "\n📋 Primeiros registros:\n";
        foreach ($stateProviders->take(5) as $sp) {
            echo "   - {$sp->provider->name} em {$sp->state->code}: {$sp->request_count} requests\n";
        }
    } else {
        echo "\n❌ Nenhum registro encontrado na tabela!\n";
        echo "\n🔍 Verificando logs...\n";
        $logFile = storage_path('logs/laravel.log');
        $lastLines = file_exists($logFile) ? array_slice(file($logFile), -50) : [];
        foreach ($lastLines as $line) {
            if (strpos($line, 'Processing state providers') !== false || 
                strpos($line, 'report_id') !== false) {
                echo "   " . trim($line) . "\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📍 " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n📋 Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

