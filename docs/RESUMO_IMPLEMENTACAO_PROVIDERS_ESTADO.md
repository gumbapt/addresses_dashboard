# 📋 Resumo: Como Lidar com Providers por Estado

## 🎯 Estrutura do Campo Opcional

```json
{
  "geographic": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "request_count": 32,
        "providers": [  // ✅ Campo opcional
          {"name": "AT&T", "count": 15},
          {"name": "Spectrum", "count": 12}
        ]
      }
    ]
  }
}
```

## 🔧 Modificações Necessárias

### 1. **Criar Tabela de Cruzamento**

```sql
CREATE TABLE report_state_providers (
    id BIGINT PRIMARY KEY,
    report_id BIGINT,
    state_id BIGINT,
    provider_id BIGINT,
    original_name VARCHAR(255),
    request_count INT,  -- ✅ Número REAL de requests
    success_rate DECIMAL(5,2),
    avg_speed DECIMAL(10,2),
    UNIQUE(report_id, state_id, provider_id)
);
```

### 2. **Modificar `ReportProcessor::processStates()`**

```php
private function processStates(int $reportId, array $statesData): void
{
    foreach ($statesData as $stateData) {
        // Processar estado (comportamento existente)
        $state = $this->stateRepository->findOrCreateByCode(...);
        ReportState::create([...]);
        
        // ✅ NOVO: Processar providers se existir
        if (isset($stateData['providers']) && is_array($stateData['providers'])) {
            foreach ($stateData['providers'] as $providerData) {
                // Normalizar nome do provider
                $normalizedName = ProviderHelper::normalizeName($providerData['name']);
                
                // Find or create provider
                $provider = $this->providerRepository->findOrCreate(
                    name: $normalizedName,
                    technologies: []
                );
                
                // Salvar cruzamento preciso
                ReportStateProvider::create([
                    'report_id' => $reportId,
                    'state_id' => $state->getId(),
                    'provider_id' => $provider->getId(),
                    'original_name' => $providerData['name'],
                    'request_count' => $providerData['count'], // ✅ Número REAL
                ]);
            }
        }
    }
}
```

## ✅ Vantagens

1. **Dados Precisos**: Cada linha representa requests **reais** do provider naquele estado
2. **Performance**: Tabela indexada, queries rápidas
3. **Retrocompatível**: Campo é opcional, reports antigos continuam funcionando
4. **Normalizado**: Usa mesmos providers e states já existentes

## 📊 Query Precisa (Depois)

```sql
-- Ranking de providers por estado (PRECISO)
SELECT 
    p.name as provider_name,
    s.code as state_code,
    SUM(rsp.request_count) as total_requests  -- ✅ Número REAL
FROM report_state_providers rsp
JOIN providers p ON rsp.provider_id = p.id
JOIN states s ON rsp.state_id = s.id
WHERE s.id = ?
GROUP BY p.id, s.id
ORDER BY total_requests DESC;
```

## 🔄 Compatibilidade

- ✅ **Campo opcional**: Se não existir, ignora (comportamento atual)
- ✅ **Reports antigos**: Continuam funcionando normalmente
- ✅ **Novos reports**: Terão dados precisos se enviarem o campo

## 📝 Resumo

**Como lidaria:**
1. Criar tabela `report_state_providers` para dados cruzados
2. Modificar `processStates()` para processar campo opcional `providers`
3. Normalizar providers usando `ProviderHelper` (mesmo do `processProviders`)
4. Salvar dados precisos na nova tabela
5. Criar queries que usam a nova tabela para dados reais

**Resultado:** Dados precisos de provider por estado, sem aproximações!

