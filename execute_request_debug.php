<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Domain;
use Illuminate\Http\Request;

echo "🚀 EXECUTANDO REQUISIÇÃO PARA DEBUG (dd() no controller)\n";
echo str_repeat("=", 60) . "\n\n";

// Buscar domínio
$domain = Domain::where('name', 'zip.50g.io')->first();
if (!$domain) {
    echo "❌ Domínio não encontrado!\n";
    exit(1);
}

echo "✅ Domínio: {$domain->name}\n";
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
        "generated_at" => date('Y-m-d H:i:s'),
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
        ],
        "excluded" => []
    ],
    "data" => [
        "date" => "2025-11-23",
        "summary" => [
            "total_requests" => 1,
            "successful_requests" => 1,
            "failed_requests" => 0,
            "success_rate" => 100,
            "unique_providers" => 11,
            "unique_states" => 1,
            "unique_cities" => 1,
            "unique_zipcodes" => 1,
            "avg_speed_mbps" => 1038.02,
            "max_speed_mbps" => 10000,
            "min_speed_mbps" => 10
        ],
        "providers" => [
            "available" => ["AT&T" => 1, "Spectrum" => 1],
            "excluded" => []
        ],
        "geographic" => [
            "cities" => ["Charlotte" => 1],
            "zipcodes" => ["28202" => 1]
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

echo "📋 Dados do report:\n";
echo "   - geographic.states[0].providers existe: " . (isset($reportData['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
if (isset($reportData['geographic']['states'][0]['providers'])) {
    echo "   - providers count: " . count($reportData['geographic']['states'][0]['providers']) . "\n";
}
echo "\n";

echo "📡 Criando Request e chamando controller diretamente...\n";
echo "   (O dd() vai parar a execução e mostrar os dados)\n\n";

// Criar request simulando HTTP POST com JSON
$request = Request::create('/api/reports/submit-daily', 'POST', $reportData);
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');
$request->headers->set('X-API-Key', $domain->api_key);

// Criar FormRequest
$formRequest = \App\Http\Requests\SubmitDailyReportRequest::createFrom($request);
$formRequest->setContainer(app());
$formRequest->setRedirector(app('redirect'));

// Validar manualmente
$validator = \Validator::make($reportData, $formRequest->rules(), $formRequest->messages());
if ($validator->fails()) {
    echo "❌ Validação falhou:\n";
    foreach ($validator->errors()->all() as $error) {
        echo "   - {$error}\n";
    }
    exit(1);
}
$formRequest->setValidator($validator);

// Chamar controller - o dd() vai parar aqui
echo "🔍 Chamando controller->submitDaily()...\n";
echo "   (dd() será executado no controller)\n\n";

$controller = app(\App\Http\Controllers\Api\ReportController::class);
$response = $controller->submitDaily($formRequest);

// Se chegou aqui, o dd() não foi executado ou foi removido
echo "⚠️  dd() não foi executado ou foi removido!\n";
echo "   Response: " . $response->getContent() . "\n";
