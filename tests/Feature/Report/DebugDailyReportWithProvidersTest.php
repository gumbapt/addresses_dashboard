<?php

namespace Tests\Feature\Report;

use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportStateProvider;
use App\Jobs\ProcessReportJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugDailyReportWithProvidersTest extends TestCase
{
    // Usando banco real - sem RefreshDatabase
    use RefreshDatabase;
    private Domain $testDomain;
    private array $wordpressReportData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Usar domínio existente ou criar se não existir (banco real)
        $this->testDomain = Domain::firstOrCreate(
            ['name' => 'zip.50g.io'],
            [
                'slug' => 'zip-50g-io',
                'domain_url' => 'https://zip.50g.io',
                'site_id' => 'wp-zip.50g.io',
                'api_key' => str_repeat('a', 64),
                'status' => 'active',
                'timezone' => 'America/New_York',
                'wordpress_version' => '6.8.3',
                'plugin_version' => '1.0.0',
                'settings' => [],
                'is_active' => true,
            ]
        );
        

        // JSON no formato esperado pela rota /api/reports/submit
        $this->wordpressReportData = [
            "source" => [
                "domain" => "zip.50g.io",
                "site_id" => "wp-zip.50g.io",
                "site_name" => "SmarterHome.ai",
                "site_url" => "https://zip.50g.io",
                "wordpress_version" => "6.8.3",
                "plugin_version" => "1.0.0"
            ],
            "metadata" => [
                "report_date" => "2025-11-25",
                "report_period" => [
                    "start" => "2025-11-25 00:00:00",
                    "end" => "2025-11-25 23:59:59"
                ],
                "generated_at" => "2025-11-25 00:32:35",
                "total_processing_time" => 0,
                "data_version" => "2.0.0"
            ],
            "summary" => [
                "total_requests" => 3,
                "successful_requests" => 3,
                "failed_requests" => 0,
                "success_rate" => 100,
                "avg_requests_per_hour" => 0.13,
                "unique_providers" => 8,
                "unique_states" => 3,
                "unique_cities" => 3,
                "unique_zip_codes" => 3,
                "avg_speed_mbps" => 476.47,
                "max_speed_mbps" => 5000,
                "min_speed_mbps" => 10
            ],
            "technology_metrics" => [
                "distribution" => [
                    "Mobile Wireless" => 32,
                    "Fiber" => 9,
                    "Satellite" => 8,
                    "DSL" => 3,
                    "Cable" => 3
                ]
            ],
            "providers" => [
                "top_providers" => [
                    ["name" => "T-Mobile", "total_count" => 3, "technology" => "Mobile"],
                    ["name" => "HughesNet", "total_count" => 3, "technology" => "Satellite"],
                    ["name" => "Verizon", "total_count" => 3, "technology" => "Mobile"],
                    ["name" => "Viasat Carrier Services Inc", "total_count" => 3, "technology" => "Satellite"],
                    ["name" => "Earthlink", "total_count" => 3, "technology" => "Mobile"],
                    ["name" => "AT&T", "total_count" => 2, "technology" => "Mobile"],
                    ["name" => "Xfinity", "total_count" => 2, "technology" => "Cable"],
                    ["name" => "Frontier", "total_count" => 1, "technology" => "DSL"]
                ]
            ],
            "geographic" => [
                "states" => [
                    [
                        "code" => "CA",
                        "name" => "California",
                        "request_count" => 1,
                        "success_rate" => 100,
                        "avg_speed" => 95.56,
                        "providers" => [
                            ["name" => "Frontier", "count" => 1],
                            ["name" => "T-Mobile", "count" => 1],
                            ["name" => "HughesNet", "count" => 1],
                            ["name" => "Verizon", "count" => 1],
                            ["name" => "Viasat Carrier Services Inc", "count" => 1],
                            ["name" => "Earthlink", "count" => 1]
                        ]
                    ],
                    [
                        "code" => "NJ",
                        "name" => "New Jersey",
                        "request_count" => 1,
                        "success_rate" => 100,
                        "avg_speed" => 460.7,
                        "providers" => [
                            ["name" => "AT&T", "count" => 1],
                            ["name" => "Xfinity", "count" => 1],
                            ["name" => "T-Mobile", "count" => 1],
                            ["name" => "HughesNet", "count" => 1],
                            ["name" => "Verizon", "count" => 1],
                            ["name" => "Viasat Carrier Services Inc", "count" => 1],
                            ["name" => "Earthlink", "count" => 1]
                        ]
                    ],
                    [
                        "code" => "TX",
                        "name" => "Texas",
                        "request_count" => 1,
                        "success_rate" => 100,
                        "avg_speed" => 641.3,
                        "providers" => [
                            ["name" => "AT&T", "count" => 1],
                            ["name" => "Xfinity", "count" => 1],
                            ["name" => "T-Mobile", "count" => 1],
                            ["name" => "HughesNet", "count" => 1],
                            ["name" => "Verizon", "count" => 1],
                            ["name" => "Viasat Carrier Services Inc", "count" => 1],
                            ["name" => "Earthlink", "count" => 1]
                        ]
                    ]
                ],
                "top_cities" => [
                    ["name" => "Redlands", "request_count" => 1, "zip_codes" => []],
                    ["name" => "Trenton", "request_count" => 1, "zip_codes" => []],
                    ["name" => "Houston", "request_count" => 1, "zip_codes" => []]
                ],
                "top_zip_codes" => [
                    ["zip_code" => "32335", "request_count" => 1, "percentage" => 33.33],
                    ["zip_code" => "08618", "request_count" => 1, "percentage" => 33.33],
                    ["zip_code" => "77026", "request_count" => 1, "percentage" => 33.33]
                ]
            ],
            "performance" => [
                "hourly_distribution" => ["08" => 3]
            ]
        ];
    }

    /**
     * Teste para debugar se o campo providers está sendo preservado no raw_data
     */
    public function test_debug_report_preserves_providers_in_raw_data(): void
    {
        Queue::fake();

        // Submeter report via endpoint submit
        $response = $this->postJson('/api/reports/submit', $this->wordpressReportData, [
            'X-API-Key' => $this->testDomain->api_key,
            'Content-Type' => 'application/json'
        ]);

        // Debug: ver o erro se houver
        if ($response->status() !== 201) {
            dump([
                'status' => $response->status(),
                'response' => $response->json(),
                'errors' => $response->json('errors') ?? 'no errors',
            ]);
        }
        
        $response->assertStatus(201);

        // Buscar report criado
        $report = Report::where('domain_id', $this->testDomain->id)
            ->whereDate('report_date', '2025-11-25')
            ->first();

        $this->assertNotNull($report, 'Report deve ter sido criado no banco de dados');

        // 🔍 DEBUG: Verificar se providers está no raw_data
        $rawData = $report->raw_data;
        
        // Verificar se geographic.states existe
        $this->assertArrayHasKey('geographic', $rawData, 'raw_data deve ter geographic');
        $this->assertArrayHasKey('states', $rawData['geographic'], 'geographic deve ter states');
        $this->assertNotEmpty($rawData['geographic']['states'], 'states não deve estar vazio');
        
        // Verificar se o primeiro estado tem providers
        $firstState = $rawData['geographic']['states'][0];
        $this->assertArrayHasKey('providers', $firstState, 'Primeiro estado deve ter campo providers no raw_data');
        $this->assertIsArray($firstState['providers'], 'providers deve ser um array');
        $this->assertNotEmpty($firstState['providers'], 'providers não deve estar vazio');
        
        // Verificar conteúdo dos providers
        $this->assertCount(6, $firstState['providers'], 'CA deve ter 6 providers');
        $this->assertEquals('Frontier', $firstState['providers'][0]['name'], 'Primeiro provider deve ser Frontier');
        $this->assertEquals(1, $firstState['providers'][0]['count'], 'Count do Frontier deve ser 1');
    }

    /**
     * Teste para verificar se os providers são processados e salvos na tabela report_state_providers
     */
    public function test_debug_report_processes_state_providers(): void
    {
        // Não usar Queue::fake() para processar realmente
        $response = $this->postJson('/api/reports/submit', $this->wordpressReportData, [
            'X-API-Key' => $this->testDomain->api_key,
            'Content-Type' => 'application/json'
        ]);

        // Debug: ver o erro se houver
        if ($response->status() !== 201) {
            dump([
                'status' => $response->status(),
                'response' => $response->json(),
                'errors' => $response->json('errors') ?? 'no errors',
            ]);
        }

        $response->assertStatus(201);

        // Buscar report
        $report = Report::where('domain_id', $this->testDomain->id)
            ->whereDate('report_date', '2025-11-25')
            ->first();

        $this->assertNotNull($report, 'Report deve ter sido criado');

        // Verificar se o raw_data contém providers
        $rawData = $report->raw_data;
        $this->assertArrayHasKey('geographic', $rawData, 'raw_data deve ter geographic');
        $this->assertArrayHasKey('states', $rawData['geographic'], 'geographic deve ter states');
        
        // Verificar se o primeiro estado tem providers no raw_data
        if (isset($rawData['geographic']['states'][0])) {
            $firstState = $rawData['geographic']['states'][0];
            $this->assertArrayHasKey('providers', $firstState, 'Primeiro estado deve ter campo providers no raw_data');
            $this->assertIsArray($firstState['providers'], 'providers deve ser um array');
        }

        // Processar job manualmente (simular queue worker)
        $job = new ProcessReportJob($report->id, []);
        $processor = app(\App\Application\Services\ReportProcessor::class);
        $reportRepository = app(\App\Infrastructure\Repositories\ReportRepository::class);
        
        $job->handle($processor, $reportRepository);

        // Verificar se os dados foram salvos em report_state_providers
        $stateProviders = ReportStateProvider::where('report_id', $report->id)->get();
        
        // Deve ter providers para CA (6), NJ (7), TX (7) = 20 total
        $this->assertGreaterThan(0, $stateProviders->count(), 'Deve ter registros em report_state_providers. Total encontrado: ' . $stateProviders->count());
        
        // Verificar CA - deve ter 6 providers
        $caState = \App\Models\State::where('code', 'CA')->first();
        if ($caState) {
            $caProviders = ReportStateProvider::where('report_id', $report->id)
                ->where('state_id', $caState->id)
                ->get();
            $this->assertCount(6, $caProviders, 'CA deve ter 6 providers. Encontrados: ' . $caProviders->count());
            
            // Verificar se os providers corretos foram salvos
            $providerNames = $caProviders->pluck('original_name')->toArray();
            $this->assertContains('Frontier', $providerNames, 'CA deve ter Frontier');
            $this->assertContains('T-Mobile', $providerNames, 'CA deve ter T-Mobile');
        }
        
        // Verificar NJ - deve ter 7 providers
        $njState = \App\Models\State::where('code', 'NJ')->first();
        if ($njState) {
            $njProviders = ReportStateProvider::where('report_id', $report->id)
                ->where('state_id', $njState->id)
                ->get();
            $this->assertCount(7, $njProviders, 'NJ deve ter 7 providers. Encontrados: ' . $njProviders->count());
            
            // Verificar se os providers corretos foram salvos
            $providerNames = $njProviders->pluck('original_name')->toArray();
            $this->assertContains('AT&T', $providerNames, 'NJ deve ter AT&T');
            $this->assertContains('Xfinity', $providerNames, 'NJ deve ter Xfinity');
        }
        
        // Verificar TX - deve ter 7 providers
        $txState = \App\Models\State::where('code', 'TX')->first();
        if ($txState) {
            $txProviders = ReportStateProvider::where('report_id', $report->id)
                ->where('state_id', $txState->id)
                ->get();
            $this->assertCount(7, $txProviders, 'TX deve ter 7 providers. Encontrados: ' . $txProviders->count());
            
            // Verificar se os providers corretos foram salvos
            $providerNames = $txProviders->pluck('original_name')->toArray();
            $this->assertContains('AT&T', $providerNames, 'TX deve ter AT&T');
            $this->assertContains('Xfinity', $providerNames, 'TX deve ter Xfinity');
        }
    }
}

