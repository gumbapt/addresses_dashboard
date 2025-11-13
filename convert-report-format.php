#!/usr/bin/env php
<?php
/**
 * Script para converter formato de relatório antigo (chave-valor) para novo (array de objetos)
 * 
 * Uso:
 *   php convert-report-format.php input.json output.json
 */

if ($argc < 3) {
    echo "Uso: php convert-report-format.php <input.json> <output.json>\n";
    echo "Exemplo: php convert-report-format.php old-format.json new-format.json\n";
    exit(1);
}

$inputFile = $argv[1];
$outputFile = $argv[2];

if (!file_exists($inputFile)) {
    echo "❌ Erro: Arquivo '$inputFile' não encontrado!\n";
    exit(1);
}

echo "🔄 Convertendo formato do relatório...\n";
echo "   Input:  $inputFile\n";
echo "   Output: $outputFile\n\n";

$json = file_get_contents($inputFile);
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

// Converter geographic.states de objeto para array
if (isset($data['geographic']['states']) && is_array($data['geographic']['states'])) {
    $oldStates = $data['geographic']['states'];
    
    // Verificar se já está no formato array de objetos
    $isAlreadyConverted = false;
    if (count($oldStates) > 0) {
        $firstKey = array_key_first($oldStates);
        if (is_numeric($firstKey)) {
            $isAlreadyConverted = true;
        }
    }
    
    if (!$isAlreadyConverted) {
        echo "   ✓ Convertendo geographic.states\n";
        $newStates = [];
        foreach ($oldStates as $code => $count) {
            $newStates[] = [
                'code' => $code,
                'name' => getStateName($code),
                'request_count' => $count
            ];
        }
        $data['geographic']['states'] = $newStates;
    }
}

// Converter geographic.cities
if (isset($data['geographic']['cities']) && is_array($data['geographic']['cities'])) {
    $oldCities = $data['geographic']['cities'];
    
    // Verificar se já está no formato array de objetos
    if (!isset($oldCities[0]['name'])) {
        echo "   ✓ Convertendo geographic.cities → top_cities\n";
        $newCities = [];
        foreach ($oldCities as $name => $count) {
            $newCities[] = [
                'name' => $name,
                'request_count' => $count
            ];
        }
        unset($data['geographic']['cities']);
        $data['geographic']['top_cities'] = $newCities;
    }
}

// Converter geographic.zipcodes
if (isset($data['geographic']['zipcodes']) && is_array($data['geographic']['zipcodes'])) {
    $oldZipcodes = $data['geographic']['zipcodes'];
    
    // Verificar se já está no formato array de objetos
    if (!isset($oldZipcodes[0]['zip_code'])) {
        echo "   ✓ Convertendo geographic.zipcodes → top_zip_codes\n";
        $newZipcodes = [];
        $totalRequests = $data['summary']['total_requests'] ?? 100;
        
        foreach ($oldZipcodes as $zipcode => $count) {
            $newZipcodes[] = [
                'zip_code' => $zipcode,
                'request_count' => $count,
                'percentage' => round(($count / $totalRequests) * 100, 2)
            ];
        }
        unset($data['geographic']['zipcodes']);
        $data['geographic']['top_zip_codes'] = $newZipcodes;
    }
}

// Converter providers.available
if (isset($data['providers']['available']) && is_array($data['providers']['available'])) {
    echo "   ✓ Convertendo providers.available → top_providers\n";
    $oldProviders = $data['providers']['available'];
    $newProviders = [];
    
    foreach ($oldProviders as $name => $count) {
        $newProviders[] = [
            'name' => $name,
            'total_count' => $count
        ];
    }
    unset($data['providers']['available']);
    $data['providers']['top_providers'] = $newProviders;
}

// Converter providers.excluded para exclusion_metrics
if (isset($data['providers']['excluded']) && is_array($data['providers']['excluded'])) {
    echo "   ✓ Movendo providers.excluded → exclusion_metrics.by_provider\n";
    
    if (!isset($data['exclusion_metrics'])) {
        $data['exclusion_metrics'] = [];
    }
    
    $data['exclusion_metrics']['by_provider'] = $data['providers']['excluded'];
    unset($data['providers']['excluded']);
}

// Converter technologies para technology_metrics
if (isset($data['technologies']) && is_array($data['technologies'])) {
    echo "   ✓ Movendo technologies → technology_metrics.distribution\n";
    
    if (!isset($data['technology_metrics'])) {
        $data['technology_metrics'] = [];
    }
    
    $data['technology_metrics']['distribution'] = $data['technologies'];
    unset($data['technologies']);
}

// Ajustar summary fields
if (isset($data['summary']['successful_requests'])) {
    echo "   ✓ Removendo summary.successful_requests (não usado)\n";
    unset($data['summary']['successful_requests']);
}

if (isset($data['summary']['unique_cities'])) {
    echo "   ✓ Removendo summary.unique_cities (não usado)\n";
    unset($data['summary']['unique_cities']);
}

if (isset($data['summary']['unique_zipcodes'])) {
    echo "   ✓ Renomeando unique_zipcodes → unique_zip_codes\n";
    $data['summary']['unique_zip_codes'] = $data['summary']['unique_zipcodes'];
    unset($data['summary']['unique_zipcodes']);
}

// Adicionar avg_requests_per_hour se não existir
if (!isset($data['summary']['avg_requests_per_hour']) && isset($data['summary']['total_requests'])) {
    $data['summary']['avg_requests_per_hour'] = round($data['summary']['total_requests'] / 24, 2);
}

// Remover campos não suportados
$unsupportedFields = ['coordinates', 'avg_speed_mbps', 'max_speed_mbps', 'min_speed_mbps'];
foreach ($unsupportedFields as $field) {
    if (isset($data['geographic'][$field])) {
        unset($data['geographic'][$field]);
    }
    if (isset($data['summary'][$field])) {
        unset($data['summary'][$field]);
    }
}

// Salvar arquivo convertido
$output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($outputFile, $output);

echo "\n✅ Conversão concluída com sucesso!\n";
echo "   Arquivo salvo: $outputFile\n\n";
echo "🧪 Teste com:\n";
echo "   curl -X POST https://dash3.50g.io/api/reports/submit \\\n";
echo "     -H \"Content-Type: application/json\" \\\n";
echo "     -H \"Authorization: Bearer YOUR_API_KEY\" \\\n";
echo "     -d @$outputFile\n\n";

/**
 * Helper para obter nome completo do estado
 */
function getStateName($code) {
    $states = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
        'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
        'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
        'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
        'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
        'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
        'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
        'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
        'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
        'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
        'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
        'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia',
        'PR' => 'Puerto Rico', 'VI' => 'Virgin Islands', 'GU' => 'Guam'
    ];
    
    return $states[$code] ?? $code;
}

