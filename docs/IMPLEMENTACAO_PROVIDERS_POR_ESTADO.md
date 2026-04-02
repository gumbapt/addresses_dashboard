# 🎯 Implementação: Providers por Estado no Daily Report

## 📋 Estrutura Proposta

```json
{
  "geographic": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "request_count": 32,
        "success_rate": 90.5,
        "avg_speed": 1500.0,
        "providers": [  // ✅ NOVO CAMPO OPCIONAL
          {"name": "AT&T", "count": 15},
          {"name": "Spectrum", "count": 12}
        ]
      }
    ]
  }
}
```

## 🗄️ 1. Criar Tabela de Cruzamento

### Migration: `create_report_state_providers_table.php`

```php
Schema::create('report_state_providers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('report_id')->constrained()->onDelete('cascade');
    $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
    $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
    $table->string('original_name'); // Nome original do provider
    $table->integer('request_count'); // Requests REAIS deste provider neste estado
    $table->decimal('success_rate', 5, 2)->default(0)->nullable();
    $table->decimal('avg_speed', 10, 2)->default(0)->nullable();
    $table->timestamps();
    
    // Indexes para performance
    $table->index(['report_id', 'state_id', 'provider_id'], 'idx_report_state_provider');
    $table->index(['state_id', 'provider_id'], 'idx_state_provider');
    $table->index('request_count', 'idx_request_count');
    
    // Evitar duplicatas
    $table->unique(['report_id', 'state_id', 'provider_id'], 'unique_report_state_provider');
});
```

## 🔧 2. Modificar ReportProcessor

### Atualizar `processStates()` para processar providers opcionais

```php
private function processStates(int $reportId, array $statesData): void
{
    if (empty($statesData)) {
        return;
    }

    Log::debug('Processing states', [
        'report_id' => $reportId,
        'state_count' => count($statesData)
    ]);

    foreach ($statesData as $stateData) {
        // Find or create state
        $state = $this->stateRepository->findOrCreateByCode(
            $stateData['code'],
            $stateData['name'] ?? null
        );
        
        // Create report state record (comportamento existente)
        ReportState::create([
            'report_id' => $reportId,
            'state_id' => $state->getId(),
            'request_count' => $stateData['request_count'] ?? 0,
            'success_rate' => $stateData['success_rate'] ?? 0,
            'avg_speed' => $stateData['avg_speed'] ?? 0,
        ]);
        
        // ✅ NOVO: Processar providers por estado (se existir)
        if (isset($stateData['providers']) && is_array($stateData['providers'])) {
            $this->processStateProviders($reportId, $state->getId(), $stateData['providers']);
        }
    }
}

/**
 * Process providers for a specific state
 */
private function processStateProviders(int $reportId, int $stateId, array $providersData): void
{
    if (empty($providersData)) {
        return;
    }

    Log::debug('Processing state providers', [
        'report_id' => $reportId,
        'state_id' => $stateId,
        'provider_count' => count($providersData)
    ]);

    foreach ($providersData as $providerData) {
        $providerName = $providerData['name'] ?? null;
        $requestCount = $providerData['count'] ?? 0;
        
        if (!$providerName || $requestCount <= 0) {
            continue; // Pular providers inválidos
        }
        
        // Normalizar nome do provider (usar mesmo helper do processProviders)
        $normalizedName = ProviderHelper::normalizeName($providerName);
        
        // Find or create provider (usar mesmo repositório)
        $provider = $this->providerRepository->findOrCreate(
            name: $normalizedName,
            technologies: [] // Tecnologia não vem no campo providers por estado
        );
        
        // Criar registro de cruzamento
        ReportStateProvider::create([
            'report_id' => $reportId,
            'state_id' => $stateId,
            'provider_id' => $provider->getId(),
            'original_name' => $providerName,
            'request_count' => $requestCount,
            'success_rate' => $providerData['success_rate'] ?? null,
            'avg_speed' => $providerData['avg_speed'] ?? null,
        ]);
    }

    Log::debug('State providers processing completed', [
        'report_id' => $reportId,
        'state_id' => $stateId,
        'processed_count' => count($providersData)
    ]);
}
```

## 📦 3. Criar Model ReportStateProvider

### `app/Models/ReportStateProvider.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportStateProvider extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'report_id',
        'state_id',
        'provider_id',
        'original_name',
        'request_count',
        'success_rate',
        'avg_speed',
    ];

    protected $casts = [
        'request_count' => 'integer',
        'success_rate' => 'decimal:2',
        'avg_speed' => 'decimal:2',
    ];

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    // Scopes
    public function scopeByState($query, int $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeByStateAndProvider($query, int $stateId, int $providerId)
    {
        return $query->where('state_id', $stateId)
                     ->where('provider_id', $providerId);
    }
}
```

## 🔍 4. Validação no Request

### Atualizar `SubmitDailyReportRequest.php`

```php
public function rules(): array
{
    return [
        // ... regras existentes ...
        
        'data.geographic.states.*.providers' => 'nullable|array',
        'data.geographic.states.*.providers.*.name' => 'required_with:data.geographic.states.*.providers|string|max:255',
        'data.geographic.states.*.providers.*.count' => 'required_with:data.geographic.states.*.providers|integer|min:0',
        'data.geographic.states.*.providers.*.success_rate' => 'nullable|numeric|min:0|max:100',
        'data.geographic.states.*.providers.*.avg_speed' => 'nullable|numeric|min:0',
    ];
}
```

## 📊 5. Queries Precisas (Após Implementação)

### Ranking de Providers por Estado

```php
// Use Case: GetProviderRankingByStateUseCase
public function execute(int $stateId, ?string $dateFrom = null, ?string $dateTo = null): array
{
    $query = DB::table('report_state_providers as rsp')
        ->join('providers as p', 'rsp.provider_id', '=', 'p.id')
        ->join('reports as r', 'rsp.report_id', '=', 'r.id')
        ->join('domains as d', 'r.domain_id', '=', 'd.id')
        ->join('states as s', 'rsp.state_id', '=', 's.id')
        ->where('r.status', 'processed')
        ->where('d.is_active', true)
        ->where('s.id', $stateId);
    
    if ($dateFrom) {
        $query->where('r.report_date', '>=', $dateFrom);
    }
    
    if ($dateTo) {
        $query->where('r.report_date', '<=', $dateTo);
    }
    
    return $query
        ->select(
            'd.id as domain_id',
            'd.name as domain_name',
            'p.id as provider_id',
            'p.name as provider_name',
            's.code as state_code',
            's.name as state_name',
            DB::raw('SUM(rsp.request_count) as total_requests'),
            DB::raw('AVG(rsp.success_rate) as avg_success_rate'),
            DB::raw('AVG(rsp.avg_speed) as avg_speed'),
            DB::raw('COUNT(DISTINCT r.id) as report_count')
        )
        ->groupBy('d.id', 'd.name', 'p.id', 'p.name', 's.id', 's.code', 's.name')
        ->orderByDesc('total_requests')
        ->get()
        ->toArray();
}
```

## ✅ 6. Vantagens da Implementação

### Antes (Aproximado):
- ❌ JOIN cria produto cartesiano
- ❌ Números inflacionados
- ❌ Não sabemos distribuição real

### Depois (Preciso):
- ✅ Dados reais e normalizados
- ✅ Performance excelente (indexado)
- ✅ Queries simples e precisas
- ✅ Suporta agregações complexas

## 🔄 7. Compatibilidade

### Retrocompatibilidade:
- ✅ Campo `providers` é **opcional**
- ✅ Se não existir, comportamento atual é mantido
- ✅ Reports antigos continuam funcionando
- ✅ Apenas reports novos com o campo terão dados precisos

### Migração de Dados:
- ⚠️ Reports antigos não terão dados na nova tabela
- 💡 Pode criar job para processar `raw_data` de reports antigos (se tiverem o campo)

## 📝 8. Checklist de Implementação

- [ ] Criar migration `create_report_state_providers_table`
- [ ] Criar model `ReportStateProvider`
- [ ] Atualizar `ReportProcessor::processStates()`
- [ ] Adicionar método `processStateProviders()`
- [ ] Atualizar validação em `SubmitDailyReportRequest`
- [ ] Criar Use Case `GetProviderRankingByStateUseCase`
- [ ] Criar endpoint no `ReportController`
- [ ] Adicionar testes unitários
- [ ] Adicionar testes de integração
- [ ] Documentar API

## 🎯 Resultado Final

Com essa implementação, você terá:
- ✅ Dados precisos de provider por estado
- ✅ Queries eficientes e indexadas
- ✅ Gráficos com números reais
- ✅ Compatibilidade com dados antigos
- ✅ Base para análises avançadas

