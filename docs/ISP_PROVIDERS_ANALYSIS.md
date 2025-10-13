# 🏢 ISP Providers - Análise e Estrutura

## 📊 Análise do JSON

### Estrutura dos Providers no JSON

```json
{
  "providers": {
    "top_providers": [
      {
        "name": "Earthlink",
        "total_count": 46,
        "success_rate": 0,
        "avg_speed": 0,
        "technology": "Mobile"
      },
      {
        "name": "AT&T",
        "total_count": 39,
        "success_rate": 0,
        "avg_speed": 0,
        "technology": "Mobile"
      }
    ],
    "by_state": [],
    "total_unique": 0,
    "by_technology": {
      "Cable": 1928,
      "Fiber": 606,
      "Mobile": 4688,
      "DSL": 270,
      "Satellite": 1353,
      "Wireless": 42
    },
    "by_provider": {
      "Spectrum": {
        "Cable": 1928,
        "Fiber": 606
      },
      "AT&T": {
        "DSL": 2660,
        "Fiber": 698,
        "Mobile": 3256
      }
    }
  }
}
```

---

## 🎯 Estratégia de Modelagem

### 1. **ISP Providers como Entidades Normalizadas**

**Por quê normalizar?**
- ✅ **Deduplicação**: "AT&T" aparece em múltiplos relatórios
- ✅ **Consistência**: Evitar variações ("AT&T" vs "AT & T" vs "ATT")
- ✅ **Enriquecimento**: Adicionar dados corporativos (website, logo, etc)
- ✅ **Performance**: JOINs rápidos vs strings repetidas
- ✅ **Analytics**: Agregações por provedor ao longo do tempo

### 2. **Tecnologias como Enum/Lookup**

**Tecnologias identificadas:**
- Cable
- Fiber  
- Mobile
- DSL
- Satellite
- Wireless

---

## 🏗️ Estrutura Proposta

### **Entidade: ISP Provider**

```php
class IspProvider
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,           // "AT&T", "Spectrum"
        public readonly string $slug,           // "att", "spectrum"
        public readonly ?string $website = null,
        public readonly ?string $logo_url = null,
        public readonly ?string $description = null,
        public readonly array $technologies = [], // ["Fiber", "Mobile"]
        public readonly bool $is_active = true
    ) {}
}
```

### **Tabela: isp_providers**

```sql
CREATE TABLE isp_providers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    website VARCHAR(500),
    logo_url VARCHAR(500),
    description TEXT,
    technologies JSONB, -- ["Fiber", "Mobile", "Cable"]
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(name),
    UNIQUE(slug),
    INDEX idx_name (name),
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
);
```

---

## 📈 Tabelas de Métricas

### **report_providers** (Dados por Relatório)

```sql
CREATE TABLE report_providers (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL REFERENCES reports(id) ON DELETE CASCADE,
    isp_provider_id BIGINT REFERENCES isp_providers(id) ON DELETE SET NULL,
    provider_name VARCHAR(255) NOT NULL, -- Backup do nome original
    technology VARCHAR(50) NOT NULL,     -- "Fiber", "Cable", etc
    total_count INTEGER NOT NULL,
    success_rate DECIMAL(5,2),
    avg_speed INTEGER,
    rank_position INTEGER,              -- Posição no top_providers
    created_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_report_provider (report_id, isp_provider_id),
    INDEX idx_provider_tech (isp_provider_id, technology),
    INDEX idx_report_rank (report_id, rank_position)
);
```

### **provider_technologies** (Lookup Table)

```sql
CREATE TABLE provider_technologies (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,   -- "Fiber", "Cable", "Mobile"
    display_name VARCHAR(100) NOT NULL, -- "Fiber Optic", "Cable Internet"
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    
    UNIQUE(name)
);

-- Seed data
INSERT INTO provider_technologies (name, display_name) VALUES
('Fiber', 'Fiber Optic'),
('Cable', 'Cable Internet'),
('Mobile', 'Mobile/Cellular'),
('DSL', 'Digital Subscriber Line'),
('Satellite', 'Satellite Internet'),
('Wireless', 'Fixed Wireless');
```

---

## 🔄 Fluxo de Processamento

### **1. Estratégia FindOrCreate para ISPs**

```php
class ProcessReportJob
{
    public function processProviders($reportData, $reportId)
    {
        foreach ($reportData['providers']['top_providers'] as $index => $providerData) {
            // 1. Normalizar nome
            $normalizedName = $this->normalizeProviderName($providerData['name']);
            
            // 2. FindOrCreate ISP Provider
            $ispProvider = $this->ispProviderRepository->findOrCreate(
                name: $normalizedName,
                technologies: [$providerData['technology']]
            );
            
            // 3. Salvar métricas do relatório
            ReportProvider::create([
                'report_id' => $reportId,
                'isp_provider_id' => $ispProvider->getId(),
                'provider_name' => $providerData['name'], // Original
                'technology' => $providerData['technology'],
                'total_count' => $providerData['total_count'],
                'success_rate' => $providerData['success_rate'],
                'avg_speed' => $providerData['avg_speed'],
                'rank_position' => $index + 1,
            ]);
        }
        
        // 4. Processar by_provider (múltiplas tecnologias)
        foreach ($reportData['providers']['by_provider'] as $providerName => $technologies) {
            $ispProvider = $this->ispProviderRepository->findOrCreate(
                name: $this->normalizeProviderName($providerName),
                technologies: array_keys($technologies)
            );
            
            foreach ($technologies as $tech => $count) {
                ReportProvider::create([
                    'report_id' => $reportId,
                    'isp_provider_id' => $ispProvider->getId(),
                    'provider_name' => $providerName,
                    'technology' => $tech,
                    'total_count' => $count,
                    'rank_position' => null, // Não está no top_providers
                ]);
            }
        }
    }
    
    private function normalizeProviderName(string $name): string
    {
        // Normalizar variações comuns
        $normalized = trim($name);
        
        $replacements = [
            'AT & T' => 'AT&T',
            'ATT' => 'AT&T',
            'Comcast' => 'Xfinity',
            'Charter' => 'Spectrum',
            'Verizon Wireless' => 'Verizon',
        ];
        
        return $replacements[$normalized] ?? $normalized;
    }
}
```

---

## 🎨 API Endpoints

### **ISP Providers Management**

```php
// GET /api/admin/isp-providers
// Lista todos os ISPs com paginação
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "AT&T",
      "slug": "att",
      "website": "https://att.com",
      "technologies": ["Fiber", "Mobile", "DSL"],
      "is_active": true,
      "total_reports": 45,      // Quantos relatórios mencionam
      "avg_requests": 2847.3    // Média de requests por relatório
    }
  ],
  "pagination": {...}
}

// GET /api/admin/isp-providers/{id}
// Detalhes de um ISP específico
{
  "success": true,
  "data": {
    "id": 1,
    "name": "AT&T",
    "slug": "att",
    "technologies": ["Fiber", "Mobile", "DSL"],
    "recent_reports": [
      {
        "report_id": 123,
        "date": "2025-10-11",
        "total_count": 3256,
        "technology": "Mobile",
        "rank_position": 2
      }
    ],
    "technology_breakdown": {
      "Mobile": 65.2,    // % das requests
      "Fiber": 21.4,
      "DSL": 13.4
    }
  }
}

// GET /api/admin/isp-providers/technologies
// Lista todas as tecnologias
{
  "success": true,
  "data": [
    {"name": "Fiber", "display_name": "Fiber Optic", "provider_count": 23},
    {"name": "Cable", "display_name": "Cable Internet", "provider_count": 15}
  ]
}
```

---

## 📊 Analytics e Dashboards

### **1. Top ISPs Dashboard**

```sql
-- Top ISPs por período
SELECT 
    ip.name,
    ip.slug,
    COUNT(DISTINCT rp.report_id) as report_count,
    SUM(rp.total_count) as total_requests,
    AVG(rp.success_rate) as avg_success_rate,
    AVG(rp.avg_speed) as avg_speed
FROM isp_providers ip
JOIN report_providers rp ON ip.id = rp.isp_provider_id
JOIN reports r ON rp.report_id = r.id
WHERE r.report_date BETWEEN '2025-10-01' AND '2025-10-31'
GROUP BY ip.id, ip.name, ip.slug
ORDER BY total_requests DESC
LIMIT 10;
```

### **2. Technology Distribution**

```sql
-- Distribuição por tecnologia
SELECT 
    rp.technology,
    COUNT(DISTINCT rp.isp_provider_id) as provider_count,
    SUM(rp.total_count) as total_requests,
    AVG(rp.success_rate) as avg_success_rate
FROM report_providers rp
JOIN reports r ON rp.report_id = r.id
WHERE r.report_date >= '2025-10-01'
GROUP BY rp.technology
ORDER BY total_requests DESC;
```

### **3. ISP Performance Over Time**

```sql
-- Performance de um ISP ao longo do tempo
SELECT 
    DATE(r.report_date) as date,
    rp.technology,
    SUM(rp.total_count) as requests,
    AVG(rp.success_rate) as success_rate,
    AVG(rp.avg_speed) as avg_speed
FROM report_providers rp
JOIN reports r ON rp.report_id = r.id
WHERE rp.isp_provider_id = 1  -- AT&T
  AND r.report_date >= '2025-10-01'
GROUP BY DATE(r.report_date), rp.technology
ORDER BY date DESC, requests DESC;
```

---

## 🔍 Casos de Uso

### **1. Análise de Market Share**

```php
// Qual ISP domina em Fiber?
$fiberLeaders = $this->reportProviderRepository->getTopByTechnology('Fiber', $period);

// Crescimento de um ISP específico
$growth = $this->reportProviderRepository->getGrowthTrend('AT&T', $startDate, $endDate);
```

### **2. Geographic Analysis**

```sql
-- ISPs por estado
SELECT 
    rg.state_code,
    rg.state_name,
    ip.name as isp_name,
    SUM(rp.total_count) as requests
FROM report_providers rp
JOIN isp_providers ip ON rp.isp_provider_id = ip.id
JOIN reports r ON rp.report_id = r.id
JOIN report_geographic rg ON r.id = rg.report_id
GROUP BY rg.state_code, rg.state_name, ip.id, ip.name
ORDER BY requests DESC;
```

### **3. Technology Migration Tracking**

```sql
-- ISPs expandindo para Fiber
SELECT 
    ip.name,
    COUNT(CASE WHEN rp.technology = 'Fiber' THEN 1 END) as fiber_reports,
    COUNT(CASE WHEN rp.technology = 'Cable' THEN 1 END) as cable_reports,
    COUNT(CASE WHEN rp.technology = 'DSL' THEN 1 END) as dsl_reports
FROM isp_providers ip
JOIN report_providers rp ON ip.id = rp.isp_provider_id
JOIN reports r ON rp.report_id = r.id
WHERE r.report_date >= '2025-01-01'
GROUP BY ip.id, ip.name
HAVING fiber_reports > 0 AND (cable_reports > 0 OR dsl_reports > 0)
ORDER BY fiber_reports DESC;
```

---

## 🚀 Implementação Sugerida

### **Fase 1: Core Entities**
1. ✅ Criar `IspProvider` Entity
2. ✅ Criar `IspProviderRepository` 
3. ✅ Criar migration `isp_providers`
4. ✅ Criar migration `provider_technologies`
5. ✅ Implementar FindOrCreate pattern

### **Fase 2: Report Integration**
1. ✅ Criar migration `report_providers`
2. ✅ Atualizar `ProcessReportJob`
3. ✅ Implementar normalização de nomes
4. ✅ Processar `top_providers` e `by_provider`

### **Fase 3: API & Dashboard**
1. ✅ Criar `IspProviderController`
2. ✅ Endpoints CRUD + Analytics
3. ✅ Dashboard com charts
4. ✅ Filtros por tecnologia/período

### **Fase 4: Advanced Analytics**
1. ✅ Market share analysis
2. ✅ Geographic distribution
3. ✅ Technology migration tracking
4. ✅ Performance benchmarking

---

## 📋 Benefícios da Abordagem

### **1. Normalização**
- ✅ **Deduplicação**: ISPs únicos no sistema
- ✅ **Consistência**: Nomes padronizados
- ✅ **Enriquecimento**: Dados corporativos centralizados

### **2. Performance**
- ✅ **JOINs rápidos**: FK vs string matching
- ✅ **Índices otimizados**: Por ISP, tecnologia, período
- ✅ **Agregações eficientes**: GROUP BY em integers

### **3. Analytics Poderosos**
- ✅ **Tendências temporais**: Performance ao longo do tempo
- ✅ **Market share**: Dominância por tecnologia/região
- ✅ **Comparações**: ISPs side-by-side
- ✅ **Alertas**: Mudanças significativas

### **4. Escalabilidade**
- ✅ **Crescimento orgânico**: Novos ISPs automaticamente
- ✅ **Multi-tenant**: Dados isolados por dominio
- ✅ **Histórico preservado**: Métricas por relatório

---

## 🎯 Exemplo de Dashboard

### **ISP Overview Widget**

```json
{
  "top_isps": [
    {
      "name": "AT&T",
      "total_requests": 156789,
      "market_share": 23.4,
      "growth": "+12.3%",
      "technologies": ["Fiber", "Mobile", "DSL"],
      "avg_success_rate": 87.2
    },
    {
      "name": "Spectrum", 
      "total_requests": 134567,
      "market_share": 20.1,
      "growth": "+8.7%",
      "technologies": ["Cable", "Fiber"],
      "avg_success_rate": 91.5
    }
  ],
  "technology_breakdown": {
    "Fiber": {"requests": 234567, "isps": 23, "growth": "+15.2%"},
    "Cable": {"requests": 198765, "isps": 15, "growth": "+5.1%"},
    "Mobile": {"requests": 345678, "isps": 8, "growth": "+22.8%"}
  }
}
```

---

## 🎉 Conclusão

**ISP Providers** são uma peça fundamental do sistema de analytics. Com essa estrutura:

1. ✅ **Normalização** elimina duplicatas e inconsistências
2. ✅ **FindOrCreate** pattern permite crescimento orgânico
3. ✅ **Métricas granulares** por tecnologia e período
4. ✅ **Analytics avançados** para insights de mercado
5. ✅ **Performance otimizada** com índices adequados

**Próximo passo:** Implementar as entidades e migrations! 🚀

---

**Data:** 2025-10-11  
**Versão:** 1.0.0  
**Status:** 📋 Design Ready

