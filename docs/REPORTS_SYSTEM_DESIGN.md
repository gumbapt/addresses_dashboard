# 📊 Reports System - Design Completo

## 🎯 Visão Geral

Sistema para processar e armazenar relatórios JSON enviados pelos domínios, utilizando toda a infraestrutura normalizada já implementada (Provider, Domain, State, City, ZipCode).

---

## 📋 Análise da Estrutura JSON

### Estrutura do `newdata.json`:

```json
{
  "source": {           // → Domain (já existe)
    "domain": "zip.50g.io",
    "site_id": "wp-prod-zip50gio-001", 
    "site_name": "SmarterHome.ai"
  },
  "metadata": {         // → Report metadata
    "report_date": "2025-10-11",
    "report_period": {...}
  },
  "summary": {...},     // → Report summary metrics
  "providers": {        // → Usar Provider system (já existe)
    "top_providers": [...],
    "by_state": [...]
  },
  "geographic": {       // → Usar State/City/ZipCode (já existe)
    "states": [...],
    "top_cities": [...],
    "top_zip_codes": [...]
  },
  "performance": {...}, // → Performance metrics
  "speed_metrics": {...}, // → Speed metrics  
  "technology_metrics": {...}, // → Technology metrics
  "exclusion_metrics": {...}, // → Exclusion metrics
  "health": {...}       // → Health metrics
}
```

---

## 🏗️ Estrutura das Entidades

### **1. Report (Entidade Principal)**

```php
class Report
{
    public function __construct(
        public readonly int $id,
        public readonly int $domainId,           // FK → domains
        public readonly string $reportDate,      // 2025-10-11
        public readonly DateTime $reportPeriodStart,
        public readonly DateTime $reportPeriodEnd, 
        public readonly DateTime $generatedAt,
        public readonly int $totalProcessingTime,
        public readonly string $dataVersion,     // 2.0.0
        public readonly array $rawData,          // JSON completo original
        public readonly string $status = 'processed', // pending, processing, processed, failed
        public readonly DateTime $createdAt,
        public readonly DateTime $updatedAt
    ) {}
}
```

### **2. ReportSummary (Métricas Resumo)**

```php
class ReportSummary  
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,           // FK → reports
        public readonly int $totalRequests,     // 1502
        public readonly float $successRate,     // 85.15
        public readonly int $failedRequests,    // 223
        public readonly float $avgRequestsPerHour, // 1.56
        public readonly int $uniqueProviders,   // 0 (será calculado)
        public readonly int $uniqueStates,      // 0 (será calculado)
        public readonly int $uniqueZipCodes     // 0 (será calculado)
    ) {}
}
```

### **3. ReportProvider (Providers do Relatório)**

```php
class ReportProvider
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,          // FK → reports
        public readonly int $providerId,        // FK → providers (normalizado)
        public readonly string $originalName,   // Nome original no JSON
        public readonly string $technology,     // Mobile, Fiber, etc.
        public readonly int $totalCount,        // 46
        public readonly float $successRate,     // 0
        public readonly float $avgSpeed,        // 0
        public readonly ?int $rankPosition      // posição no top_providers
    ) {}
}
```

### **4. ReportState (Estados do Relatório)**

```php
class ReportState
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,          // FK → reports
        public readonly int $stateId,           // FK → states (normalizado)
        public readonly int $requestCount,      // 239
        public readonly float $successRate,     // 0
        public readonly float $avgSpeed         // 0
    ) {}
}
```

### **5. ReportCity (Cidades do Relatório)**

```php
class ReportCity
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,          // FK → reports
        public readonly int $cityId,            // FK → cities (normalizado)
        public readonly int $requestCount,      // 19
        public readonly array $zipCodes         // [] array de zip codes
    ) {}
}
```

### **6. ReportZipCode (ZipCodes do Relatório)**

```php
class ReportZipCode
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,          // FK → reports
        public readonly int $zipCodeId,         // FK → zip_codes (normalizado)
        public readonly int $requestCount,      // 13
        public readonly float $percentage       // 0 (será calculado)
    ) {}
}
```

---

## 🎮 Métricas Específicas

### **7. ReportPerformance**

```php
class ReportPerformance
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,
        public readonly array $hourlyDistribution, // [{hour: 0, count: 50}, ...]
        public readonly float $avgResponseTime,    // 0
        public readonly float $minResponseTime,    // 0
        public readonly float $maxResponseTime,    // 0
        public readonly array $searchTypes         // {direct: {...}, fallback: {...}}
    ) {}
}
```

### **8. ReportSpeedMetric**

```php
class ReportSpeedMetric
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,
        public readonly string $type,           // overall, by_state, by_provider
        public readonly ?string $entityType,    // state, provider
        public readonly ?int $entityId,         // FK → states/providers
        public readonly string $entityName,     // "New York", "AT&T"
        public readonly float $avgSpeed,        // 4560
        public readonly int $sampleCount        // 0
    ) {}
}
```

### **9. ReportTechnologyMetric**

```php
class ReportTechnologyMetric
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,
        public readonly string $scope,          // distribution, by_state, by_provider
        public readonly ?int $stateId,          // FK → states (se scope = by_state)
        public readonly ?int $providerId,       // FK → providers (se scope = by_provider)
        public readonly string $technology,     // Mobile, Fiber, Cable, etc.
        public readonly int $count,             // 18188
        public readonly float $percentage       // 46.8
    ) {}
}
```

### **10. ReportExclusion**

```php
class ReportExclusion
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,
        public readonly int $totalExclusions,   // 1348
        public readonly float $exclusionRate,   // 134800
        public readonly ?int $stateId,          // FK → states
        public readonly ?int $providerId,       // FK → providers
        public readonly int $exclusionCount,    // quantidade de exclusões
        public readonly ?string $reason         // motivo da exclusão
    ) {}
}
```

### **11. ReportHealth**

```php
class ReportHealth
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,
        public readonly string $status,         // "healthy"
        public readonly float $uptimePercentage, // 99.5
        public readonly float $avgCpuUsage,     // 0
        public readonly float $avgMemoryUsage,  // 123
        public readonly float $diskUsage,       // 15506.59
        public readonly DateTime $lastCronRun   // "2025-10-11 18:54:50"
    ) {}
}
```

---

## 📊 Estrutura das Tabelas

### **1. reports**
```sql
CREATE TABLE reports (
    id BIGSERIAL PRIMARY KEY,
    domain_id BIGINT NOT NULL,           -- FK → domains
    report_date DATE NOT NULL,           -- 2025-10-11
    report_period_start TIMESTAMP NOT NULL,
    report_period_end TIMESTAMP NOT NULL,
    generated_at TIMESTAMP NOT NULL,
    total_processing_time INT DEFAULT 0,
    data_version VARCHAR(20) NOT NULL,   -- 2.0.0
    raw_data JSON NOT NULL,              -- JSON original completo
    status VARCHAR(20) DEFAULT 'pending', -- pending, processing, processed, failed
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (domain_id) REFERENCES domains(id),
    INDEX idx_domain_date (domain_id, report_date),
    INDEX idx_status (status),
    INDEX idx_generated_at (generated_at)
);
```

### **2. report_summaries**
```sql
CREATE TABLE report_summaries (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    total_requests INT NOT NULL,
    success_rate DECIMAL(5,2) NOT NULL,
    failed_requests INT NOT NULL,
    avg_requests_per_hour DECIMAL(10,2) NOT NULL,
    unique_providers INT DEFAULT 0,
    unique_states INT DEFAULT 0,
    unique_zip_codes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    UNIQUE(report_id)
);
```

### **3. report_providers**
```sql
CREATE TABLE report_providers (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    provider_id BIGINT NOT NULL,         -- FK → providers (normalizado)
    original_name VARCHAR(255) NOT NULL, -- Nome original no JSON
    technology VARCHAR(50) NOT NULL,
    total_count INT NOT NULL,
    success_rate DECIMAL(5,2) DEFAULT 0,
    avg_speed DECIMAL(10,2) DEFAULT 0,
    rank_position INT NULL,              -- NULL se não estiver no top
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES providers(id),
    INDEX idx_report_provider (report_id, provider_id),
    INDEX idx_technology (technology),
    INDEX idx_rank (rank_position)
);
```

### **4. report_states**
```sql
CREATE TABLE report_states (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    state_id BIGINT NOT NULL,            -- FK → states (normalizado)
    request_count INT NOT NULL,
    success_rate DECIMAL(5,2) DEFAULT 0,
    avg_speed DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (state_id) REFERENCES states(id),
    INDEX idx_report_state (report_id, state_id)
);
```

### **5. report_cities**
```sql
CREATE TABLE report_cities (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    city_id BIGINT NOT NULL,             -- FK → cities (normalizado)
    request_count INT NOT NULL,
    zip_codes JSON DEFAULT '[]',         -- Array de zip codes
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (city_id) REFERENCES cities(id),
    INDEX idx_report_city (report_id, city_id)
);
```

### **6. report_zip_codes**
```sql
CREATE TABLE report_zip_codes (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    zip_code_id BIGINT NOT NULL,         -- FK → zip_codes (normalizado)
    request_count INT NOT NULL,
    percentage DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (zip_code_id) REFERENCES zip_codes(id),
    INDEX idx_report_zip (report_id, zip_code_id)
);
```

### **7. report_performance**
```sql
CREATE TABLE report_performance (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    hourly_distribution JSON NOT NULL,   -- [{hour: 0, count: 50}, ...]
    avg_response_time DECIMAL(10,3) DEFAULT 0,
    min_response_time DECIMAL(10,3) DEFAULT 0,
    max_response_time DECIMAL(10,3) DEFAULT 0,
    search_types JSON NOT NULL,          -- {direct: {...}, fallback: {...}}
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    UNIQUE(report_id)
);
```

### **8. report_speed_metrics**
```sql
CREATE TABLE report_speed_metrics (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    type VARCHAR(50) NOT NULL,           -- overall, by_state, by_provider
    entity_type VARCHAR(20) NULL,        -- state, provider
    entity_id BIGINT NULL,               -- FK → states/providers
    entity_name VARCHAR(255) NOT NULL,   -- "New York", "AT&T"
    avg_speed DECIMAL(10,2) NOT NULL,
    sample_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_report_type (report_id, type),
    INDEX idx_entity (entity_type, entity_id)
);
```

### **9. report_technology_metrics**
```sql
CREATE TABLE report_technology_metrics (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    scope VARCHAR(50) NOT NULL,          -- distribution, by_state, by_provider
    state_id BIGINT NULL,                -- FK → states (se scope = by_state)
    provider_id BIGINT NULL,             -- FK → providers (se scope = by_provider)
    technology VARCHAR(50) NOT NULL,
    count INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (state_id) REFERENCES states(id),
    FOREIGN KEY (provider_id) REFERENCES providers(id),
    INDEX idx_report_scope (report_id, scope),
    INDEX idx_technology (technology)
);
```

### **10. report_exclusions**
```sql
CREATE TABLE report_exclusions (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    total_exclusions INT NOT NULL,
    exclusion_rate DECIMAL(10,2) NOT NULL,
    state_id BIGINT NULL,                -- FK → states
    provider_id BIGINT NULL,             -- FK → providers
    exclusion_count INT NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (state_id) REFERENCES states(id),
    FOREIGN KEY (provider_id) REFERENCES providers(id),
    INDEX idx_report_exclusion (report_id, state_id, provider_id)
);
```

### **11. report_health**
```sql
CREATE TABLE report_health (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL,
    status VARCHAR(20) NOT NULL,
    uptime_percentage DECIMAL(5,2) NOT NULL,
    avg_cpu_usage DECIMAL(5,2) DEFAULT 0,
    avg_memory_usage DECIMAL(10,2) DEFAULT 0,
    disk_usage DECIMAL(15,2) DEFAULT 0,
    last_cron_run TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    UNIQUE(report_id)
);
```

---

## 🔄 Fluxo de Processamento

### **1. Recepção do Relatório**

```php
POST /api/reports/submit
Authorization: Bearer {domain_api_key}
Content-Type: application/json

{
  "source": {...},
  "metadata": {...},
  "summary": {...},
  // ... resto do JSON
}
```

### **2. Pipeline de Processamento**

```php
class ProcessReportJob
{
    public function handle($reportData, $domainId)
    {
        DB::transaction(function () use ($reportData, $domainId) {
            // 1. Criar Report principal
            $report = $this->createReport($reportData, $domainId);
            
            // 2. Processar Summary
            $this->processSummary($report, $reportData['summary']);
            
            // 3. Processar Providers (usar FindOrCreate)
            $this->processProviders($report, $reportData['providers']);
            
            // 4. Processar Geographic (States/Cities/ZipCodes)
            $this->processGeographic($report, $reportData['geographic']);
            
            // 5. Processar Performance Metrics
            $this->processPerformance($report, $reportData['performance']);
            
            // 6. Processar Speed Metrics
            $this->processSpeedMetrics($report, $reportData['speed_metrics']);
            
            // 7. Processar Technology Metrics
            $this->processTechnologyMetrics($report, $reportData['technology_metrics']);
            
            // 8. Processar Exclusions
            $this->processExclusions($report, $reportData['exclusion_metrics']);
            
            // 9. Processar Health
            $this->processHealth($report, $reportData['health']);
            
            // 10. Atualizar status
            $report->updateStatus('processed');
        });
    }
}
```

### **3. Normalização Automática**

```php
private function processProviders($report, $providersData)
{
    // Top Providers
    foreach ($providersData['top_providers'] as $index => $providerData) {
        // Usar ProviderHelper para normalização
        $normalizedName = ProviderHelper::normalizeName($providerData['name']);
        $technology = ProviderHelper::normalizeTechnology($providerData['technology']);
        
        // FindOrCreate provider
        $provider = $this->providerRepository->findOrCreate(
            name: $normalizedName,
            technologies: [$technology]
        );
        
        // Salvar ReportProvider
        ReportProvider::create([
            'report_id' => $report->getId(),
            'provider_id' => $provider->getId(),
            'original_name' => $providerData['name'], // Manter original
            'technology' => $technology,
            'total_count' => $providerData['total_count'],
            'success_rate' => $providerData['success_rate'],
            'avg_speed' => $providerData['avg_speed'],
            'rank_position' => $index + 1,
        ]);
    }
}

private function processGeographic($report, $geoData)
{
    // States
    foreach ($geoData['states'] as $stateData) {
        $state = $this->stateRepository->findOrCreateByCode($stateData['code'], $stateData['name']);
        
        ReportState::create([
            'report_id' => $report->getId(),
            'state_id' => $state->getId(),
            'request_count' => $stateData['request_count'],
            'success_rate' => $stateData['success_rate'],
            'avg_speed' => $stateData['avg_speed'],
        ]);
    }
    
    // Cities
    foreach ($geoData['top_cities'] as $cityData) {
        // FindOrCreate city (sem estado específico no JSON)
        $city = $this->cityRepository->findOrCreateByName($cityData['name']);
        
        ReportCity::create([
            'report_id' => $report->getId(),
            'city_id' => $city->getId(),
            'request_count' => $cityData['request_count'],
            'zip_codes' => $cityData['zip_codes'],
        ]);
    }
    
    // ZipCodes
    foreach ($geoData['top_zip_codes'] as $zipData) {
        $zipCode = $this->zipCodeRepository->findOrCreateByCode($zipData['zip_code']);
        
        ReportZipCode::create([
            'report_id' => $report->getId(),
            'zip_code_id' => $zipCode->getId(),
            'request_count' => $zipData['request_count'],
            'percentage' => $zipData['percentage'],
        ]);
    }
}
```

---

## 🎯 Use Cases Implementados

### **1. SubmitReportUseCase**
- Recebe JSON do domínio
- Valida estrutura e autenticação
- Inicia processamento assíncrono

### **2. ProcessReportUseCase**
- Processa cada seção do relatório
- Normaliza dados usando helpers existentes
- Utiliza FindOrCreate patterns

### **3. GetReportAnalyticsUseCase**
- Agregações e dashboards
- Comparações históricas
- Métricas por período

### **4. GetProviderInsightsUseCase**
- Análise de providers normalizados
- Crescimento por tecnologia
- Market share

---

## 🚀 Vantagens do Design

### **✅ Normalização Completa**
- Providers: "AT & T" → "AT&T" (único record)
- States: códigos e nomes normalizados
- ZipCodes: validação e normalização automática
- Cities: deduplicação por nome

### **✅ Relacionamentos Consistentes**
- FK constraints garantem integridade
- Cascading deletes para limpeza
- Índices otimizados para queries

### **✅ Flexibilidade de Análise**
- Agregações por provider normalizado
- Comparações históricas por domínio
- Análises geográficas detalhadas
- Métricas de performance temporal

### **✅ Escalabilidade**
- Processamento assíncrono via Jobs
- Particionamento por data possível
- Índices otimizados para grandes volumes

### **✅ Rastreabilidade**
- JSON original preservado
- Nomes originais mantidos nos relacionamentos
- Histórico de processamento

---

## 📈 Queries de Exemplo

### **Top Providers Normalizados (Últimos 30 dias)**
```sql
SELECT 
    p.name as provider_name,
    SUM(rp.total_count) as total_requests,
    AVG(rp.success_rate) as avg_success_rate,
    COUNT(DISTINCT r.domain_id) as domain_count
FROM report_providers rp
JOIN providers p ON rp.provider_id = p.id
JOIN reports r ON rp.report_id = r.id
WHERE r.report_date >= CURRENT_DATE - INTERVAL '30 days'
GROUP BY p.id, p.name
ORDER BY total_requests DESC
LIMIT 10;
```

### **Análise de Crescimento por Estado**
```sql
SELECT 
    s.name as state_name,
    DATE_TRUNC('week', r.report_date) as week,
    SUM(rs.request_count) as total_requests,
    AVG(rs.success_rate) as avg_success_rate
FROM report_states rs
JOIN states s ON rs.state_id = s.id  
JOIN reports r ON rs.report_id = r.id
WHERE r.report_date >= CURRENT_DATE - INTERVAL '90 days'
GROUP BY s.id, s.name, week
ORDER BY week DESC, total_requests DESC;
```

---

**Data:** 2025-10-13  
**Versão:** 1.0.0  
**Status:** 📋 Design Ready for Implementation

