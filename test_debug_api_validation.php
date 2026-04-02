<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Requests\SubmitDailyReportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

echo "🔍 TESTE DE DEBUG - Validação da API\n";
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
    echo "📋 PASSO 1: Dados ANTES da validação...\n";
    echo "   - geographic.states existe: " . (isset($reportData['geographic']['states']) ? '✅ SIM' : '❌ NÃO') . "\n";
    echo "   - geographic.states[0].providers existe: " . (isset($reportData['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
    if (isset($reportData['geographic']['states'][0]['providers'])) {
        echo "   - providers count: " . count($reportData['geographic']['states'][0]['providers']) . "\n";
    }
    echo "\n";
    
    echo "📋 PASSO 2: Simulando Request do Laravel...\n";
    $request = Request::create('/api/reports/submit-daily', 'POST', $reportData);
    $request->headers->set('Content-Type', 'application/json');
    
    echo "   - Request criado\n";
    echo "   - request->input('geographic') existe: " . ($request->input('geographic') !== null ? '✅ SIM' : '❌ NÃO') . "\n";
    echo "   - request->input('geographic.states') existe: " . ($request->input('geographic.states') !== null ? '✅ SIM' : '❌ NÃO') . "\n";
    
    $geographic = $request->input('geographic');
    $geographicStates = $geographic['states'] ?? null;
    $firstState = $geographicStates[0] ?? null;
    $firstStateProviders = $firstState['providers'] ?? null;
    
    echo "   - request->input('geographic.states[0].providers') existe: " . ($firstStateProviders !== null ? '✅ SIM' : '❌ NÃO') . "\n";
    if ($firstStateProviders !== null) {
        echo "   - providers count no request: " . count($firstStateProviders) . "\n";
    }
    echo "\n";
    
    echo "📋 PASSO 3: Criando SubmitDailyReportRequest...\n";
    $formRequest = new SubmitDailyReportRequest();
    $formRequest->merge($reportData);
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));
    
    echo "   - FormRequest criado\n\n";
    
    echo "📋 PASSO 4: Executando validação...\n";
    try {
        $validated = $formRequest->validated();
        
        echo "   ✅ Validação passou!\n";
        echo "   - validated['geographic'] existe: " . (isset($validated['geographic']) ? '✅ SIM' : '❌ NÃO') . "\n";
        echo "   - validated['geographic']['states'] existe: " . (isset($validated['geographic']['states']) ? '✅ SIM' : '❌ NÃO') . "\n";
        echo "   - validated['geographic']['states'][0]['providers'] existe: " . (isset($validated['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
        
        if (isset($validated['geographic']['states'][0]['providers'])) {
            echo "   - providers count no validated: " . count($validated['geographic']['states'][0]['providers']) . "\n";
            echo "   - providers no validated: " . json_encode($validated['geographic']['states'][0]['providers']) . "\n";
        } else {
            echo "   ⚠️  PROBLEMA: Campo providers NÃO está no validated!\n";
            echo "   - Estrutura do primeiro estado: " . json_encode($validated['geographic']['states'][0] ?? []) . "\n";
            echo "   - Todas as chaves do primeiro estado: " . (isset($validated['geographic']['states'][0]) ? implode(', ', array_keys($validated['geographic']['states'][0])) : 'N/A') . "\n";
        }
        echo "\n";
        
        echo "📋 PASSO 5: Comparando dados...\n";
        echo "   - ANTES da validação tem providers: " . (isset($reportData['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
        echo "   - DEPOIS da validação tem providers: " . (isset($validated['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
        
        if (isset($reportData['geographic']['states'][0]['providers']) && !isset($validated['geographic']['states'][0]['providers'])) {
            echo "\n   ❌ PROBLEMA IDENTIFICADO:\n";
            echo "      O campo 'providers' está sendo PERDIDO durante a validação!\n";
            echo "      Isso acontece porque o Laravel só retorna campos que foram validados.\n";
            echo "      Verifique se as regras de validação para 'geographic.states.*.providers' estão corretas.\n";
        }
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        echo "   ❌ Validação falhou!\n";
        echo "   Erros:\n";
        foreach ($e->errors() as $field => $errors) {
            echo "      - {$field}: " . implode(', ', $errors) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📍 " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n📋 Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

