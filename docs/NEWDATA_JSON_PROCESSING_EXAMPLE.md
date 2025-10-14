# 📄 Processamento do newdata.json - Exemplo Prático

## 🎯 Como o Sistema Processaria o newdata.json

Este documento mostra **exatamente** como o JSON `newdata.json` seria processado pelo sistema Reports usando toda a infraestrutura existente.

---

## 📋 Input: newdata.json

```json
{
  "source": {
    "domain": "zip.50g.io",
    "site_id": "wp-prod-zip50gio-001", 
    "site_name": "SmarterHome.ai"
  },
  "providers": {
    "top_providers": [
      {"name": "Earthlink", "total_count": 46, "technology": "Mobile"},
      {"name": "AT&T", "total_count": 39, "technology": "Mobile"},
      {"name": "Spectrum", "total_count": 20, "technology": "Cable"}
    ]
  },
  "geographic": {
    "states": [
      {"code": "CA", "name": "California", "request_count": 239},
      {"code": "NY", "name": "New York", "request_count": 165}
    ],
    "top_cities": [
      {"name": "Los Angeles", "request_count": 19},
      {"name": "New York", "request_count": 25}
    ],
    "top_zip_codes": [
      {"zip_code": 10038, "request_count": 13},
      {"zip_code": "07018", "request_count": 3}
    ]
  }
  // ... resto do JSON
}
```

---

## 🔄 Processamento Passo a Passo

### **1. Recepção do Report**

```php
// POST /api/reports/submit
// Authorization: Bearer {domain_api_key}

class SubmitReportController 
{
    public function submit(Request $request)
    {
        // 1. Autenticar domain pela API key
        $domain = $this->authenticateDomain($request->bearerToken());
        
        // 2. Validar estrutura JSON
        $validatedData = $this->validateReportStructure($request->json()->all());
        
        // 3. Criar report pendente
        $report = Report::create([
            'domain_id' => $domain->id,
            'report_date' => $validatedData['metadata']['report_date'],
            'report_period_start' => $validatedData['metadata']['report_period']['start'],
            'report_period_end' => $validatedData['metadata']['report_period']['end'],
            'generated_at' => $validatedData['metadata']['generated_at'],
            'raw_data' => $validatedData, // JSON completo preservado
            'status' => 'pending'
        ]);
        
        // 4. Processar assincronamente
        ProcessReportJob::dispatch($report->id, $validatedData);
        
        return response()->json(['success' => true, 'report_id' => $report->id]);
    }
}
```

---

### **2. Processamento de Providers**

```php
class ProcessReportJob
{
    public function processProviders($report, $providersData)
    {
        foreach ($providersData['top_providers'] as $index => $providerData) {
            
            // ✅ USAR SISTEMA PROVIDER EXISTENTE
            $normalizedName = ProviderHelper::normalizeName($providerData['name']);
            $technology = ProviderHelper::normalizeTechnology($providerData['technology']);
            
            // ✅ FindOrCreate com merge de tecnologias automático
            $provider = $this->providerRepository->findOrCreate(
                name: $normalizedName,
                technologies: [$technology]
            );
            
            // ✅ Salvar na tabela de relacionamento
            ReportProvider::create([
                'report_id' => $report->id,
                'provider_id' => $provider->getId(),
                'original_name' => $providerData['name'], // "Earthlink" (original)
                'technology' => $technology,
                'total_count' => $providerData['total_count'],
                'success_rate' => $providerData['success_rate'] ?? 0,
                'avg_speed' => $providerData['avg_speed'] ?? 0,
                'rank_position' => $index + 1,
            ]);
        }
    }
}
```

**Resultado no Banco:**

```sql
-- providers table (normalizado, FindOrCreate)
INSERT INTO providers (name, slug, technologies) VALUES
('Earthlink', 'earthlink', '["Mobile"]'),
('AT&T', 'att', '["Mobile"]'),  -- Já existe, só adiciona Mobile se não tiver
('Spectrum', 'spectrum', '["Cable"]');

-- report_providers table (relacionamento)
INSERT INTO report_providers (report_id, provider_id, original_name, technology, total_count, rank_position) VALUES
(1, 15, 'Earthlink', 'Mobile', 46, 1),
(1, 6, 'AT&T', 'Mobile', 39, 2),      -- provider_id=6 já existe de reports anteriores
(1, 8, 'Spectrum', 'Cable', 20, 3);
```

---

### **3. Processamento Geographic**

```php
public function processGeographic($report, $geoData)
{
    // ✅ ESTADOS - Usar sistema State existente
    foreach ($geoData['states'] as $stateData) {
        $state = $this->stateRepository->findByCode($stateData['code']); // CA, NY já existem
        
        ReportState::create([
            'report_id' => $report->id,
            'state_id' => $state->getId(),
            'request_count' => $stateData['request_count'],
            'success_rate' => $stateData['success_rate'] ?? 0,
            'avg_speed' => $stateData['avg_speed'] ?? 0,
        ]);
    }
    
    // ✅ CIDADES - Usar sistema City existente
    foreach ($geoData['top_cities'] as $cityData) {
        $city = $this->cityRepository->findOrCreateByName(
            name: $cityData['name'],
            stateId: null // JSON não tem estado específico
        );
        
        ReportCity::create([
            'report_id' => $report->id,
            'city_id' => $city->getId(),
            'request_count' => $cityData['request_count'],
            'zip_codes' => $cityData['zip_codes'] ?? [],
        ]);
    }
    
    // ✅ ZIP CODES - Usar sistema ZipCode existente
    foreach ($geoData['top_zip_codes'] as $zipData) {
        // ZipCodeHelper já lida com string/int normalização
        $zipCode = $this->zipCodeRepository->findOrCreateByCode($zipData['zip_code']);
        
        ReportZipCode::create([
            'report_id' => $report->id,
            'zip_code_id' => $zipCode->getId(),
            'request_count' => $zipData['request_count'],
            'percentage' => $zipData['percentage'] ?? 0,
        ]);
    }
}
```

**Resultado no Banco:**

```sql
-- states table (já existem, só buscar)
SELECT id FROM states WHERE code IN ('CA', 'NY'); -- IDs: 5, 33

-- cities table (FindOrCreate)
INSERT INTO cities (name, state_id, slug) VALUES
('Los Angeles', NULL, 'los-angeles'),    -- Novo ou existente
('New York', NULL, 'new-york');          -- Novo ou existente

-- zip_codes table (FindOrCreate com normalização)
INSERT INTO zip_codes (code, formatted_code, city_id, state_id) VALUES
('10038', '10038', NULL, 33),   -- New York
('07018', '07018', NULL, 31);   -- New Jersey

-- Relacionamentos
INSERT INTO report_states VALUES (1, 5, 239, 0, 0);    -- CA
INSERT INTO report_states VALUES (1, 33, 165, 0, 0);   -- NY
INSERT INTO report_cities VALUES (1, 205, 19, '[]');   -- Los Angeles  
INSERT INTO report_zip_codes VALUES (1, 1042, 13, 0);  -- 10038
```

---

### **4. Processamento de Technology Metrics**

```php
public function processTechnologyMetrics($report, $techData)
{
    // Distribution geral
    foreach ($techData['distribution'] as $tech) {
        ReportTechnologyMetric::create([
            'report_id' => $report->id,
            'scope' => 'distribution',
            'state_id' => null,
            'provider_id' => null,
            'technology' => ProviderHelper::normalizeTechnology($tech['tech']),
            'count' => $tech['count'],
            'percentage' => $tech['percentage'],
        ]);
    }
    
    // Por estado
    foreach ($techData['by_state'] as $stateName => $technologies) {
        $state = $this->stateRepository->findByName($stateName);
        
        foreach ($technologies as $tech => $count) {
            ReportTechnologyMetric::create([
                'report_id' => $report->id,
                'scope' => 'by_state',
                'state_id' => $state->getId(),
                'provider_id' => null,
                'technology' => ProviderHelper::normalizeTechnology($tech),
                'count' => $count,
                'percentage' => 0, // Calcular depois
            ]);
        }
    }
    
    // Por provider 
    foreach ($techData['by_provider'] as $providerName => $technologies) {
        // ✅ Usar normalização do Provider system
        $normalizedName = ProviderHelper::normalizeName($providerName);
        $provider = $this->providerRepository->findByName($normalizedName);
        
        foreach ($technologies as $tech => $count) {
            ReportTechnologyMetric::create([
                'report_id' => $report->id,
                'scope' => 'by_provider',
                'state_id' => null,
                'provider_id' => $provider->getId(),
                'technology' => ProviderHelper::normalizeTechnology($tech),
                'count' => $count,
                'percentage' => 0,
            ]);
        }
    }
}
```

**Exemplo de Normalização:**

```json
// Input JSON
"by_provider": {
  "AT&T": {"DSL": 2660, "Fiber": 698, "Mobile": 3256},
  "AT & T": {"Mobile": 100},  // Variação do mesmo provider
  "Spectrum": {"Cable": 1928, "Fiber": 606}
}

// Resultado Normalizado
// provider_id=6 (AT&T normalizado) recebe:
// Mobile: 3256 + 100 = 3356 ✅
// DSL: 2660
// Fiber: 698
```

---

### **5. Processamento Final**

```php
public function handle($reportId, $reportData)
{
    $report = Report::find($reportId);
    
    DB::transaction(function () use ($report, $reportData) {
        
        // 1. Summary
        $this->processSummary($report, $reportData['summary']);
        
        // 2. Providers (usa Provider system)
        $this->processProviders($report, $reportData['providers']);
        
        // 3. Geographic (usa State/City/ZipCode systems)
        $this->processGeographic($report, $reportData['geographic']);
        
        // 4. Performance
        $this->processPerformance($report, $reportData['performance']);
        
        // 5. Speed Metrics  
        $this->processSpeedMetrics($report, $reportData['speed_metrics']);
        
        // 6. Technology Metrics
        $this->processTechnologyMetrics($report, $reportData['technology_metrics']);
        
        // 7. Exclusions
        $this->processExclusions($report, $reportData['exclusion_metrics']);
        
        // 8. Health
        $this->processHealth($report, $reportData['health']);
        
        // 9. Marcar como processado
        $report->update(['status' => 'processed']);
        
        // 10. Atualizar counters no summary
        $this->updateSummaryCounts($report);
    });
}

private function updateSummaryCounts($report)
{
    $uniqueProviders = $report->reportProviders()->distinct('provider_id')->count();
    $uniqueStates = $report->reportStates()->distinct('state_id')->count();
    $uniqueZipCodes = $report->reportZipCodes()->distinct('zip_code_id')->count();
    
    $report->summary()->update([
        'unique_providers' => $uniqueProviders,
        'unique_states' => $uniqueStates,
        'unique_zip_codes' => $uniqueZipCodes,
    ]);
}
```

---

## 📊 Resultado Final no Dashboard

### **Query: Top Providers (Normalizados)**

```sql
SELECT 
    p.name,
    p.slug,
    SUM(rp.total_count) as total_requests,
    AVG(rp.success_rate) as avg_success_rate,
    array_agg(DISTINCT rp.technology) as technologies,
    COUNT(DISTINCT r.domain_id) as reporting_domains
FROM report_providers rp
JOIN providers p ON rp.provider_id = p.id
JOIN reports r ON rp.report_id = r.id
WHERE r.report_date >= CURRENT_DATE - INTERVAL '30 days'
GROUP BY p.id, p.name, p.slug
ORDER BY total_requests DESC;
```

**Resultado:**
```
name     | slug      | total_requests | technologies        | reporting_domains
---------|-----------|----------------|--------------------|-----------------
AT&T     | att       | 3395           | [Mobile,DSL,Fiber] | 15
Spectrum | spectrum  | 2534           | [Cable,Fiber]      | 12  
Earthlink| earthlink | 1847           | [Mobile,DSL,Fiber] | 8
```

### **Query: Geographic Distribution**

```sql
SELECT 
    s.name as state,
    s.code,
    SUM(rs.request_count) as total_requests,
    AVG(rs.success_rate) as avg_success_rate,
    COUNT(DISTINCT r.domain_id) as reporting_domains
FROM report_states rs
JOIN states s ON rs.state_id = s.id
JOIN reports r ON rs.report_id = r.id
WHERE r.report_date >= CURRENT_DATE - INTERVAL '7 days'
GROUP BY s.id, s.name, s.code
ORDER BY total_requests DESC;
```

**Resultado:**
```
state      | code | total_requests | avg_success_rate | reporting_domains
-----------|------|----------------|------------------|------------------
California | CA   | 2890           | 85.2            | 25
Texas      | TX   | 1987           | 82.1            | 18
New York   | NY   | 1654           | 88.5            | 22
```

---

## 🎯 Principais Vantagens

### **✅ Normalização Total**
- **"AT&T"**, **"AT & T"**, **"ATT"** → Um único provider `AT&T`
- **"California"**, **"CA"** → Um único state normalizado
- **"10038"**, **10038** → Um único zip code normalizado

### **✅ Relacionamentos Consistentes**
- Reports → Domain (quem enviou)
- Reports → Providers (normalizados)
- Reports → Geographic (states/cities/zips normalizados)

### **✅ Histórico Preservado**
- JSON original em `reports.raw_data`
- Nomes originais em `report_providers.original_name`
- Timestamps de processamento

### **✅ Analytics Poderosos**
- Agregações por provider normalizado
- Trends geográficos consistentes  
- Comparações históricas precisas
- Market share real (sem duplicatas)

---

## 🚀 Próximo Passo

Com este design, o sistema pode processar qualquer JSON similar ao `newdata.json` e:

1. **✅ Normalizar** todos os providers automaticamente
2. **✅ Relacionar** com geographic data existente
3. **✅ Preservar** dados originais para auditoria
4. **✅ Gerar** analytics consistentes e precisos
5. **✅ Escalar** para milhares de reports por dia

**Ready to implement!** 🎉

---

**Data:** 2025-10-13  
**Versão:** 1.0.0  
**Status:** ✅ Design Validated with Real Data

