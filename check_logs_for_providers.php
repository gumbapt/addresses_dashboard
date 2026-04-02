<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 VERIFICANDO LOGS - Campo Providers\n";
echo str_repeat("=", 60) . "\n\n";

$logFile = storage_path('logs/laravel.log');

if (!file_exists($logFile)) {
    echo "❌ Arquivo de log não encontrado!\n";
    exit(1);
}

echo "📋 Lendo últimas 100 linhas do log...\n\n";

$lines = file($logFile);
$lastLines = array_slice($lines, -100);

$foundLogs = [];
foreach ($lastLines as $line) {
    if (strpos($line, 'Report recebido do WordPress') !== false ||
        strpos($line, 'Report validado') !== false ||
        strpos($line, 'convertGeographic') !== false ||
        strpos($line, 'Raw data convertido') !== false ||
        strpos($line, 'Processing state providers') !== false ||
        strpos($line, 'providers') !== false) {
        $foundLogs[] = trim($line);
    }
}

if (empty($foundLogs)) {
    echo "⚠️  Nenhum log relevante encontrado nas últimas 100 linhas.\n";
    echo "   Envie um novo report do WordPress e execute este script novamente.\n";
} else {
    echo "✅ Logs encontrados:\n\n";
    foreach ($foundLogs as $log) {
        echo "   " . $log . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 Verificando último report no banco...\n\n";

$lastReport = \App\Models\Report::orderBy('id', 'desc')->first();

if ($lastReport) {
    echo "   Report ID: {$lastReport->id}\n";
    echo "   Report Date: {$lastReport->report_date}\n";
    echo "   Status: {$lastReport->status}\n";
    
    $rawData = $lastReport->raw_data;
    echo "   - raw_data.geographic.states[0].providers existe: " . (isset($rawData['geographic']['states'][0]['providers']) ? '✅ SIM' : '❌ NÃO') . "\n";
    
    if (isset($rawData['geographic']['states'][0]['providers'])) {
        echo "   - providers count: " . count($rawData['geographic']['states'][0]['providers']) . "\n";
    } else {
        echo "   - Estrutura do primeiro estado: " . json_encode($rawData['geographic']['states'][0] ?? [], JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n   Verificando report_state_providers...\n";
    $stateProviders = \App\Models\ReportStateProvider::where('report_id', $lastReport->id)->get();
    echo "   - Registros salvos: " . $stateProviders->count() . "\n";
    
    if ($stateProviders->count() > 0) {
        echo "   ✅ Dados salvos:\n";
        foreach ($stateProviders->take(5) as $sp) {
            echo "      - {$sp->provider->name} em {$sp->state->code}\n";
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Verificação concluída!\n";

