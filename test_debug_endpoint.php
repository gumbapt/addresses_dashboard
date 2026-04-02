<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Domain;

echo "🔍 TESTE DE DEBUG - Endpoint Real\n";
echo str_repeat("=", 60) . "\n\n";

// Buscar domínio
$domain = Domain::where('name', 'zip.50g.io')->first();
if (!$domain) {
    echo "❌ Domínio não encontrado!\n";
    exit(1);
}

echo "✅ Domínio encontrado: ID {$domain->id}\n";
echo "🔑 API Key: {$domain->api_key}\n\n";

// Dados do report (formato exato do WordPress)
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
            ["name" => "AT&T", "total_count" => 1, "technology" => "Mobile"],
            ["name" => "Spectrum", "total_count" => 1, "technology" => "Fiber"]
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
                    ["name" => "AT&T", "count" => 1],
                    ["name" => "Spectrum", "count" => 1],
                    ["name" => "T-Mobile", "count" => 1]
                ]
            ]
        ],
        "top_cities" => [
            ["name" => "Charlotte", "request_count" => 1, "zip_codes" => []]
        ],
        "top_zip_codes" => [
            ["zip_code" => "28202", "request_count" => 1, "percentage" => 100]
        ]
    ],
    "performance" => [
        "hourly_distribution" => ["13" => 1]
    ],
    "report_type" => "daily"
];

echo "📋 PASSO 1: Verificando dados do report...\n";
echo "   - geographic.states[0].providers existe: " . (isset($reportData['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
if (isset($reportData['geographic']['states'][0]['providers'])) {
    echo "   - providers count: " . count($reportData['geographic']['states'][0]['providers']) . "\n";
}
echo "\n";

echo "📋 PASSO 2: Simulando requisição HTTP...\n";
echo "   Endpoint: /api/reports/submit-daily\n";
echo "   Method: POST\n";
echo "   Headers: X-API-Key: {$domain->api_key}\n\n";

// Criar request simulando HTTP
$request = \Illuminate\Http\Request::create('/api/reports/submit-daily', 'POST', $reportData);
$request->headers->set('X-API-Key', $domain->api_key);
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');

// Chamar controller diretamente
try {
    // Criar FormRequest com os dados e validar
    $formRequest = \App\Http\Requests\SubmitDailyReportRequest::createFrom($request);
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));
    $formRequest->headers->set('X-API-Key', $domain->api_key);
    
    // Validar o request manualmente
    $validator = \Validator::make($request->all(), $formRequest->rules());
    if ($validator->fails()) {
        throw new \Illuminate\Validation\ValidationException($validator);
    }
    $formRequest->setValidator($validator);
    
    $controller = app(\App\Http\Controllers\Api\ReportController::class);
    $response = $controller->submitDaily($formRequest);
    
    $responseData = json_decode($response->getContent(), true);
    $httpCode = $response->getStatusCode();
    
    echo "   Status HTTP: {$httpCode}\n";
    
    if ($httpCode == 201) {
        echo "   ✅ Report criado com sucesso!\n";
        echo "   Report ID: " . ($responseData['data']['id'] ?? 'N/A') . "\n\n";
        
        $reportId = $responseData['data']['id'] ?? null;
        
        if ($reportId) {
            echo "📋 PASSO 3: Verificando raw_data no banco...\n";
            $reportModel = \App\Models\Report::find($reportId);
            
            if ($reportModel) {
                $rawData = $reportModel->raw_data;
                
                echo "   - raw_data.geographic.states[0].providers existe: " . (isset($rawData['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
                
                if (isset($rawData['geographic']['states'][0]['providers'])) {
                    echo "   - providers count no raw_data: " . count($rawData['geographic']['states'][0]['providers']) . "\n";
                    echo "   ✅ SUCESSO: Campo providers está no raw_data!\n";
                } else {
                    echo "   ❌ PROBLEMA: Campo providers NÃO está no raw_data!\n";
                    echo "   - Estrutura do primeiro estado: " . json_encode($rawData['geographic']['states'][0] ?? []) . "\n";
                }
                echo "\n";
                
                echo "📋 PASSO 4: Verificando logs do Laravel...\n";
                $logFile = storage_path('logs/laravel.log');
                if (file_exists($logFile)) {
                    $lines = file($logFile);
                    $lastLines = array_slice($lines, -50);
                    
                    $foundLogs = false;
                    foreach ($lastLines as $line) {
                        if (strpos($line, 'Report recebido do WordPress') !== false ||
                            strpos($line, 'Report validado') !== false ||
                            strpos($line, 'convertGeographic') !== false ||
                            strpos($line, 'Raw data convertido') !== false) {
                            echo "   " . trim($line) . "\n";
                            $foundLogs = true;
                        }
                    }
                    
                    if (!$foundLogs) {
                        echo "   ⚠️  Nenhum log de debug encontrado\n";
                    }
                }
                echo "\n";
                
                echo "📋 PASSO 5: Processando job manualmente...\n";
                $job = new \App\Jobs\ProcessReportJob($reportId, []);
                $processor = app(\App\Application\Services\ReportProcessor::class);
                $reportRepository = app(\App\Infrastructure\Repositories\ReportRepository::class);
                
                $job->handle($processor, $reportRepository);
                
                echo "   ✅ Job processado!\n\n";
                
                echo "📋 PASSO 6: Verificando report_state_providers...\n";
                $stateProviders = \App\Models\ReportStateProvider::where('report_id', $reportId)->get();
                echo "   - Registros salvos: " . $stateProviders->count() . "\n";
                
                if ($stateProviders->count() > 0) {
                    echo "   ✅ SUCESSO! Dados salvos:\n";
                    foreach ($stateProviders as $sp) {
                        echo "      - {$sp->provider->name} em {$sp->state->code}: {$sp->request_count} requests\n";
                    }
                } else {
                    echo "   ❌ PROBLEMA: Nenhum registro encontrado!\n";
                }
            }
        }
    } else {
        echo "   ❌ Erro na requisição!\n";
        echo "   Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exceção: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Teste concluído!\n";
