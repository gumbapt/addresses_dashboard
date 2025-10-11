# ISP Reporting System - Sistema Multi-Tenant de Agregação de Dados

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Arquitetura do Sistema](#arquitetura-do-sistema)
3. [Modelo de Dados](#modelo-de-dados)
4. [Fluxo de Dados](#fluxo-de-dados)
5. [Sistema de Permissões](#sistema-de-permissões)
6. [API Endpoints](#api-endpoints)
7. [Dashboards](#dashboards)
8. [Segurança](#segurança)
9. [Performance](#performance)
10. [Casos de Uso](#casos-de-uso)

---

## 🎯 Visão Geral

### Propósito

Sistema **multi-tenant** projetado para:
- Receber relatórios diários de múltiplos domínios parceiros sobre disponibilidade de ISPs (Internet Service Providers)
- Armazenar e processar dados históricos de forma eficiente
- Fornecer dashboards agregados e comparativos
- Controlar acesso granular através de sistema de permissões

### Conceitos Chave

- **Domain (Domínio):** Site parceiro que envia relatórios (ex: SmarterHome.ai)
- **Report (Relatório):** JSON diário contendo métricas de ISPs
- **Admin:** Usuário com permissões para visualizar dados de um ou mais domínios
- **Dashboard:** Interface de visualização de dados agregados

---

## 🏗️ Arquitetura do Sistema

### Diagrama de Alto Nível

```
┌─────────────────┐
│  Domain Sites   │
│  (Partners)     │
│  - Site A       │
│  - Site B       │
│  - Site N       │
└────────┬────────┘
         │ Daily JSON Reports
         │ (API POST)
         ▼
┌─────────────────────────────────────────┐
│         Ingestion Layer                 │
│  - Validation                           │
│  - Authentication                       │
│  - Rate Limiting                        │
└────────┬────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│       Processing Layer (Queue)          │
│  - Parse JSON                           │
│  - Normalize Data                       │
│  - Calculate Aggregations               │
└────────┬────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│          Storage Layer                  │
│  - PostgreSQL (Raw JSON + Normalized)   │
│  - Redis (Cache)                        │
└────────┬────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│          API Layer                      │
│  - Query Endpoints                      │
│  - Permission Checks                    │
│  - Data Aggregation                     │
└────────┬────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│       Dashboard Layer (Frontend)        │
│  - Global Views                         │
│  - Domain-Specific Views                │
│  - Comparative Views                    │
└─────────────────────────────────────────┘
```

### Camadas do Sistema

#### 1. **Ingestion Layer**
- Recebe POST requests com relatórios JSON
- Valida estrutura e autenticidade
- Aplica rate limiting
- Enfileira para processamento

#### 2. **Processing Layer**
- Jobs assíncronos (Laravel Queue)
- Normalização de dados
- Cálculo de agregações
- Detecção de anomalias

#### 3. **Storage Layer**
- PostgreSQL para dados estruturados
- JSONB para flexibilidade
- Redis para cache
- Backup automático

#### 4. **API Layer**
- RESTful endpoints
- Autenticação via Sanctum
- Autorização baseada em permissões
- Rate limiting por admin

#### 5. **Dashboard Layer**
- Interface web responsiva
- Visualizações interativas
- Filtros avançados
- Exportação de dados

---

## 📊 Modelo de Dados

### Diagrama Entidade-Relacionamento

```
┌─────────────┐        ┌─────────────┐        ┌──────────────┐
│   Domains   │1      *│   Reports   │1      *│ReportSummary│
│─────────────│────────│─────────────│────────│──────────────│
│ id          │        │ id          │        │ id           │
│ name        │        │ domain_id   │        │ report_id    │
│ slug        │        │ report_date │        │ total_req    │
│ domain_url  │        │ raw_json    │        │ success_rate │
│ api_key     │        │ status      │        │ ...          │
│ is_active   │        │ created_at  │        └──────────────┘
│ timezone    │        └─────────────┘
└─────────────┘                │
       │                       │1
       │                       │
       │                      *│
       │               ┌──────────────────┐
       │               │ ReportProviders  │
       │               │──────────────────│
       │               │ id               │
       │               │ report_id        │
       │               │ provider_name    │
       │               │ total_count      │
       │               │ avg_speed        │
       │               │ technology       │
       │               └──────────────────┘
       │
       │*               ┌──────────────────┐
       └────────────────│AdminDomainAccess │
                        │──────────────────│
                        │ id               │
                   1    │ admin_id         │
     ┌───────────────── │ domain_id        │
     │                  │ access_level     │
     │                  │ granted_at       │
     │                  └──────────────────┘
     │
┌────┴────┐
│ Admins  │
│─────────│
│ id      │
│ name    │
│ email   │
│ ...     │
└─────────┘
```

### Tabelas Principais

#### **domains**
Armazena informações sobre domínios parceiros.

```sql
CREATE TABLE domains (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    domain_url VARCHAR(255) NOT NULL,
    site_id VARCHAR(255),
    api_key VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT true,
    timezone VARCHAR(50) DEFAULT 'UTC',
    wordpress_version VARCHAR(20),
    plugin_version VARCHAR(20),
    settings JSONB,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_slug (slug),
    INDEX idx_api_key (api_key),
    INDEX idx_is_active (is_active)
);
```

**Exemplo:**
```json
{
  "id": 1,
  "name": "SmarterHome.ai",
  "slug": "smarterhome-ai",
  "domain_url": "zip.50g.io",
  "site_id": "wp-prod-zip50gio-001",
  "api_key": "sk_live_abc123...",
  "is_active": true,
  "timezone": "America/Los_Angeles"
}
```

#### **reports**
Armazena relatórios diários de cada domínio.

```sql
CREATE TABLE reports (
    id BIGSERIAL PRIMARY KEY,
    domain_id BIGINT NOT NULL REFERENCES domains(id) ON DELETE CASCADE,
    report_date DATE NOT NULL,
    report_period_start TIMESTAMP NOT NULL,
    report_period_end TIMESTAMP NOT NULL,
    generated_at TIMESTAMP NOT NULL,
    data_version VARCHAR(20),
    status VARCHAR(20) DEFAULT 'pending', -- pending, processing, completed, failed
    raw_json JSONB NOT NULL,
    processing_time INTEGER DEFAULT 0, -- em segundos
    error_message TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(domain_id, report_date),
    INDEX idx_domain_date (domain_id, report_date),
    INDEX idx_status (status),
    INDEX idx_report_date (report_date)
);
```

**Campos importantes:**
- `raw_json`: JSON completo recebido (flexibilidade para mudanças futuras)
- `status`: Estado do processamento
- `UNIQUE(domain_id, report_date)`: Garante 1 relatório por dia por domínio

#### **report_summaries**
Dados sumarizados extraídos do JSON para queries rápidas.

```sql
CREATE TABLE report_summaries (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL REFERENCES reports(id) ON DELETE CASCADE,
    total_requests INTEGER NOT NULL,
    success_rate DECIMAL(5,2),
    failed_requests INTEGER,
    avg_requests_per_hour DECIMAL(10,2),
    unique_providers INTEGER,
    unique_states INTEGER,
    unique_zip_codes INTEGER,
    created_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(report_id),
    INDEX idx_report_id (report_id)
);
```

#### **report_providers**
Top provedores de cada relatório.

```sql
CREATE TABLE report_providers (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL REFERENCES reports(id) ON DELETE CASCADE,
    provider_name VARCHAR(255) NOT NULL,
    total_count INTEGER NOT NULL,
    success_rate DECIMAL(5,2),
    avg_speed INTEGER,
    technology VARCHAR(50),
    rank INTEGER, -- posição no ranking
    created_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_report_provider (report_id, provider_name),
    INDEX idx_provider_name (provider_name)
);
```

#### **report_geographic**
Dados geográficos por estado.

```sql
CREATE TABLE report_geographic (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL REFERENCES reports(id) ON DELETE CASCADE,
    state_code VARCHAR(2) NOT NULL,
    state_name VARCHAR(100) NOT NULL,
    request_count INTEGER NOT NULL,
    success_rate DECIMAL(5,2),
    avg_speed INTEGER,
    created_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_report_state (report_id, state_code),
    INDEX idx_state_code (state_code)
);
```

#### **report_cities**
Dados por cidade.

```sql
CREATE TABLE report_cities (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL REFERENCES reports(id) ON DELETE CASCADE,
    city_name VARCHAR(255) NOT NULL,
    state_code VARCHAR(2),
    request_count INTEGER NOT NULL,
    zip_codes JSONB, -- array de zip codes
    created_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_report_city (report_id, city_name),
    INDEX idx_city_name (city_name)
);
```

#### **report_performance**
Distribuição horária de requisições.

```sql
CREATE TABLE report_performance (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL REFERENCES reports(id) ON DELETE CASCADE,
    hour INTEGER NOT NULL CHECK (hour >= 0 AND hour <= 23),
    request_count INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(report_id, hour),
    INDEX idx_report_hour (report_id, hour)
);
```

#### **report_technologies**
Distribuição por tipo de tecnologia.

```sql
CREATE TABLE report_technologies (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL REFERENCES reports(id) ON DELETE CASCADE,
    technology VARCHAR(50) NOT NULL, -- Mobile, DSL, Fiber, Satellite, Cable, Wireless
    count INTEGER NOT NULL,
    percentage DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_report_tech (report_id, technology)
);
```

#### **report_speeds**
Métricas de velocidade por entidade.

```sql
CREATE TABLE report_speeds (
    id BIGSERIAL PRIMARY KEY,
    report_id BIGINT NOT NULL REFERENCES reports(id) ON DELETE CASCADE,
    entity_type VARCHAR(20) NOT NULL, -- 'state' ou 'provider'
    entity_name VARCHAR(255) NOT NULL,
    avg_speed INTEGER NOT NULL,
    max_speed INTEGER,
    min_speed INTEGER,
    sample_count INTEGER,
    created_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_report_entity (report_id, entity_type, entity_name)
);
```

#### **admin_domain_access**
Controla quais domínios cada admin pode acessar.

```sql
CREATE TABLE admin_domain_access (
    id BIGSERIAL PRIMARY KEY,
    admin_id BIGINT NOT NULL REFERENCES admins(id) ON DELETE CASCADE,
    domain_id BIGINT NOT NULL REFERENCES domains(id) ON DELETE CASCADE,
    access_level VARCHAR(20) DEFAULT 'read', -- read, write, admin
    granted_at TIMESTAMP DEFAULT NOW(),
    granted_by BIGINT REFERENCES admins(id),
    expires_at TIMESTAMP,
    is_active BOOLEAN DEFAULT true,
    
    UNIQUE(admin_id, domain_id),
    INDEX idx_admin_domain (admin_id, domain_id),
    INDEX idx_is_active (is_active)
);
```

**Access Levels:**
- `read`: Pode visualizar dados
- `write`: Pode visualizar e exportar
- `admin`: Controle total (gerenciar acessos)

#### **domain_groups** (Opcional)
Agrupa domínios para facilitar permissões.

```sql
CREATE TABLE domain_groups (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by BIGINT REFERENCES admins(id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_created_by (created_by)
);

CREATE TABLE domain_group_members (
    id BIGSERIAL PRIMARY KEY,
    domain_group_id BIGINT NOT NULL REFERENCES domain_groups(id) ON DELETE CASCADE,
    domain_id BIGINT NOT NULL REFERENCES domains(id) ON DELETE CASCADE,
    added_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(domain_group_id, domain_id)
);

CREATE TABLE admin_domain_group_access (
    id BIGSERIAL PRIMARY KEY,
    admin_id BIGINT NOT NULL REFERENCES admins(id) ON DELETE CASCADE,
    domain_group_id BIGINT NOT NULL REFERENCES domain_groups(id) ON DELETE CASCADE,
    access_level VARCHAR(20) DEFAULT 'read',
    granted_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(admin_id, domain_group_id)
);
```

---

## 🔄 Fluxo de Dados

### 1. Ingestão de Relatórios

#### Endpoint: `POST /api/reports/ingest`

**Request:**
```http
POST /api/reports/ingest HTTP/1.1
Host: addresses-dashboard.com
Content-Type: application/json
Authorization: Bearer sk_live_abc123...

{
  "source": {
    "domain": "zip.50g.io",
    "site_id": "wp-prod-zip50gio-001",
    "site_name": "SmarterHome.ai",
    ...
  },
  "metadata": {
    "report_date": "2025-10-11",
    ...
  },
  "summary": { ... },
  "providers": { ... },
  ...
}
```

**Fluxo:**

```
1. Request chega no endpoint
   ↓
2. Middleware valida API key
   ↓
3. Identifica domain_id pelo API key
   ↓
4. Valida estrutura do JSON
   ↓
5. Verifica duplicatas (domain_id + report_date)
   ↓
6. Salva em `reports` table com status='pending'
   ↓
7. Dispatch job ProcessReportJob
   ↓
8. Retorna 202 Accepted com report_id
```

**Response:**
```json
{
  "success": true,
  "message": "Report received and queued for processing",
  "data": {
    "report_id": 12345,
    "domain": "SmarterHome.ai",
    "report_date": "2025-10-11",
    "status": "pending"
  }
}
```

#### Validações

**Estrutura obrigatória:**
- `source.domain`
- `source.site_id`
- `metadata.report_date`
- `metadata.generated_at`
- `summary.total_requests`

**Validações de negócio:**
- `report_date` não pode ser futuro
- `report_date` não pode ter > 2 dias de atraso
- `total_requests` >= 0
- `success_rate` entre 0 e 100
- Domain deve estar ativo (`is_active = true`)

**Erros possíveis:**
```json
// 401 Unauthorized
{
  "error": "Invalid or missing API key"
}

// 422 Unprocessable Entity
{
  "error": "Validation failed",
  "details": {
    "summary.total_requests": "This field is required"
  }
}

// 409 Conflict
{
  "error": "Report for this date already exists",
  "existing_report_id": 12344
}

// 429 Too Many Requests
{
  "error": "Rate limit exceeded. Try again in 3600 seconds"
}
```

### 2. Processamento em Background

#### Job: `ProcessReportJob`

```php
<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessReportJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;
    
    public function __construct(
        private int $reportId
    ) {}
    
    public function handle(): void
    {
        $report = Report::findOrFail($this->reportId);
        
        try {
            $report->update(['status' => 'processing']);
            
            $json = $report->raw_json;
            
            // 1. Extract and save summary
            $this->processSummary($report, $json['summary']);
            
            // 2. Extract and save providers
            $this->processProviders($report, $json['providers']);
            
            // 3. Extract and save geographic data
            $this->processGeographic($report, $json['geographic']);
            
            // 4. Extract and save performance data
            $this->processPerformance($report, $json['performance']);
            
            // 5. Extract and save technology metrics
            $this->processTechnologies($report, $json['technology_metrics']);
            
            // 6. Extract and save speed metrics
            $this->processSpeeds($report, $json['speed_metrics']);
            
            $report->update(['status' => 'completed']);
            
        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

**Tempo estimado de processamento:** 5-15 segundos por relatório

### 3. Consulta de Dados

#### Endpoint: `GET /api/admin/dashboard/summary`

**Query Parameters:**
```
domains[]       - Array de domain IDs (opcional, padrão: todos permitidos)
start_date      - Data inicial (opcional, padrão: 30 dias atrás)
end_date        - Data final (opcional, padrão: hoje)
group_by        - Agrupamento: day, week, month (opcional, padrão: day)
state           - Filtrar por estado (opcional)
provider        - Filtrar por provedor (opcional)
technology      - Filtrar por tecnologia (opcional)
```

**Exemplo:**
```http
GET /api/admin/dashboard/summary?domains[]=1&domains[]=2&start_date=2025-10-01&end_date=2025-10-11&group_by=day
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "period": {
      "start": "2025-10-01",
      "end": "2025-10-11",
      "days": 11
    },
    "domains": [
      {
        "id": 1,
        "name": "SmarterHome.ai",
        "slug": "smarterhome-ai"
      },
      {
        "id": 2,
        "name": "BroadbandSearch.net",
        "slug": "broadbandsearch-net"
      }
    ],
    "aggregated_metrics": {
      "total_requests": 15420,
      "avg_success_rate": 87.34,
      "total_domains": 2,
      "total_reports": 22,
      "unique_providers": 245,
      "unique_states": 48
    },
    "daily_breakdown": [
      {
        "date": "2025-10-01",
        "total_requests": 1420,
        "success_rate": 86.5,
        "reports_count": 2
      },
      ...
    ],
    "top_providers": [
      {
        "name": "Earthlink",
        "total_count": 892,
        "avg_speed": 1200,
        "domains_count": 2
      },
      ...
    ],
    "geographic_distribution": [
      {
        "state_code": "CA",
        "state_name": "California",
        "total_requests": 4780,
        "avg_speed": 1424
      },
      ...
    ]
  }
}
```

---

## 🔐 Sistema de Permissões

### Níveis de Acesso

#### 1. **Super Admin**
- Acesso irrestrito a todos os domínios
- Pode gerenciar domínios, admins e permissões
- Atributo: `is_super_admin = true`

#### 2. **Domain Admin**
- Controle total sobre domínios específicos
- Pode conceder acesso a outros admins
- Nível: `access_level = 'admin'`

#### 3. **Write Access**
- Visualização e exportação de dados
- Não pode alterar permissões
- Nível: `access_level = 'write'`

#### 4. **Read Only**
- Apenas visualização
- Não pode exportar
- Nível: `access_level = 'read'`

### Verificação de Permissões

#### UseCase: `CheckDomainAccessUseCase`

```php
<?php

namespace App\Application\UseCases\Admin\Authorization;

use App\Domain\Entities\Admin;
use App\Domain\Exceptions\AuthorizationException;

class CheckDomainAccessUseCase
{
    public function execute(
        Admin $admin,
        int $domainId,
        string $requiredLevel = 'read'
    ): bool {
        // Super admins têm acesso a tudo
        if ($admin->isSuperAdmin()) {
            return true;
        }
        
        // Buscar permissão específica
        $access = AdminDomainAccess::where('admin_id', $admin->getId())
            ->where('domain_id', $domainId)
            ->where('is_active', true)
            ->first();
        
        if (!$access) {
            throw new AuthorizationException(
                "Admin {$admin->getId()} does not have access to domain {$domainId}"
            );
        }
        
        // Verificar se expirou
        if ($access->expires_at && $access->expires_at < now()) {
            throw new AuthorizationException(
                "Access to domain {$domainId} has expired"
            );
        }
        
        // Verificar nível de acesso
        $levels = ['read' => 1, 'write' => 2, 'admin' => 3];
        
        if ($levels[$access->access_level] < $levels[$requiredLevel]) {
            throw new AuthorizationException(
                "Insufficient permissions. Required: {$requiredLevel}, Has: {$access->access_level}"
            );
        }
        
        return true;
    }
    
    public function getAccessibleDomains(Admin $admin): array
    {
        if ($admin->isSuperAdmin()) {
            return Domain::where('is_active', true)->get();
        }
        
        return Domain::whereHas('adminAccess', function ($query) use ($admin) {
            $query->where('admin_id', $admin->getId())
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                });
        })->get();
    }
}
```

### Middleware: `CheckDomainAccess`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckDomainAccess
{
    public function handle(Request $request, Closure $next, string $requiredLevel = 'read')
    {
        $admin = $request->user();
        $domainIds = $request->input('domains', []);
        
        if (empty($domainIds)) {
            // Se não especificou domínios, pega todos acessíveis
            $domainIds = $this->getAccessibleDomains($admin)->pluck('id');
            $request->merge(['domains' => $domainIds]);
        } else {
            // Verifica cada domínio solicitado
            foreach ($domainIds as $domainId) {
                if (!$this->checkAccess($admin, $domainId, $requiredLevel)) {
                    return response()->json([
                        'error' => "Access denied to domain {$domainId}"
                    ], 403);
                }
            }
        }
        
        return $next($request);
    }
}
```

---

## 🌐 API Endpoints

### Autenticação

Todos os endpoints requerem autenticação via **Laravel Sanctum**.

**Header obrigatório:**
```
Authorization: Bearer {token}
```

### Endpoints de Ingestão

#### **POST /api/reports/ingest**
Recebe relatório de um domínio.

**Auth:** API Key do domínio  
**Rate Limit:** 2 requests/dia por domínio

**Request:**
```json
{
  "source": { ... },
  "metadata": { ... },
  "summary": { ... },
  ...
}
```

**Responses:**
- `202 Accepted` - Relatório aceito
- `401 Unauthorized` - API key inválida
- `409 Conflict` - Relatório duplicado
- `422 Unprocessable Entity` - Validação falhou
- `429 Too Many Requests` - Limite excedido

### Endpoints de Consulta (Admin)

#### **GET /api/admin/domains**
Lista domínios acessíveis pelo admin.

**Auth:** Admin token  
**Permissions:** N/A (retorna apenas domínios com acesso)

**Query Params:**
- `is_active` (boolean) - Filtrar por status
- `search` (string) - Buscar por nome/slug

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "SmarterHome.ai",
      "slug": "smarterhome-ai",
      "domain_url": "zip.50g.io",
      "is_active": true,
      "access_level": "admin",
      "last_report_date": "2025-10-11",
      "total_reports": 365
    }
  ]
}
```

#### **GET /api/admin/reports**
Lista relatórios com filtros.

**Auth:** Admin token  
**Permissions:** Read access aos domínios

**Query Params:**
- `domains[]` (array) - IDs dos domínios
- `start_date` (date) - Data inicial
- `end_date` (date) - Data final
- `status` (string) - pending, processing, completed, failed
- `page` (int) - Página
- `per_page` (int) - Itens por página (max: 100)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12345,
      "domain": {
        "id": 1,
        "name": "SmarterHome.ai"
      },
      "report_date": "2025-10-11",
      "status": "completed",
      "summary": {
        "total_requests": 1502,
        "success_rate": 85.15
      },
      "generated_at": "2025-10-11 15:21:57"
    }
  ],
  "pagination": {
    "total": 1250,
    "per_page": 15,
    "current_page": 1,
    "last_page": 84
  }
}
```

#### **GET /api/admin/reports/{id}**
Detalhes de um relatório específico.

**Auth:** Admin token  
**Permissions:** Read access ao domínio

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 12345,
    "domain": {
      "id": 1,
      "name": "SmarterHome.ai",
      "slug": "smarterhome-ai"
    },
    "report_date": "2025-10-11",
    "status": "completed",
    "raw_json": { ... },
    "processed_data": {
      "summary": { ... },
      "providers": [ ... ],
      "geographic": [ ... ],
      "performance": [ ... ]
    }
  }
}
```

#### **GET /api/admin/dashboard/summary**
Dashboard resumido com métricas agregadas.

**Auth:** Admin token  
**Permissions:** Read access aos domínios

**Query Params:**
- `domains[]` (array) - IDs dos domínios (opcional)
- `start_date` (date) - Padrão: 30 dias atrás
- `end_date` (date) - Padrão: hoje
- `group_by` (string) - day, week, month

**Response:** Ver exemplo em "Fluxo de Dados" acima.

#### **GET /api/admin/dashboard/providers**
Top provedores agregados.

**Auth:** Admin token  
**Permissions:** Read access

**Query Params:**
- `domains[]`
- `start_date`
- `end_date`
- `limit` (int) - Top N (padrão: 20)
- `technology` (string) - Filtrar por tecnologia

**Response:**
```json
{
  "success": true,
  "data": {
    "providers": [
      {
        "name": "Earthlink",
        "total_count": 892,
        "avg_speed": 1200,
        "technologies": ["Mobile", "Fiber", "DSL"],
        "states_covered": 45,
        "domains_present": 3
      }
    ]
  }
}
```

#### **GET /api/admin/dashboard/geographic**
Dados geográficos agregados.

**Auth:** Admin token  
**Permissions:** Read access

**Query Params:**
- `domains[]`
- `start_date`
- `end_date`
- `group_by` (string) - state, city

**Response:**
```json
{
  "success": true,
  "data": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "total_requests": 4780,
        "avg_speed": 1424,
        "top_providers": ["Earthlink", "AT&T", "Spectrum"]
      }
    ]
  }
}
```

#### **GET /api/admin/dashboard/trends**
Tendências temporais.

**Auth:** Admin token  
**Permissions:** Read access

**Query Params:**
- `domains[]`
- `start_date`
- `end_date`
- `metric` (string) - requests, success_rate, avg_speed
- `group_by` (string) - day, week, month

**Response:**
```json
{
  "success": true,
  "data": {
    "metric": "success_rate",
    "period": "day",
    "data_points": [
      {
        "date": "2025-10-01",
        "value": 86.5,
        "domains_count": 2,
        "total_requests": 1420
      }
    ],
    "statistics": {
      "avg": 87.34,
      "min": 82.1,
      "max": 92.3,
      "trend": "up" // up, down, stable
    }
  }
}
```

#### **GET /api/admin/dashboard/compare**
Comparação entre domínios.

**Auth:** Admin token  
**Permissions:** Read access aos domínios

**Query Params:**
- `domains[]` (obrigatório, min: 2)
- `start_date`
- `end_date`
- `metrics[]` - Array de métricas a comparar

**Response:**
```json
{
  "success": true,
  "data": {
    "comparison": [
      {
        "domain": {
          "id": 1,
          "name": "SmarterHome.ai"
        },
        "metrics": {
          "total_requests": 15420,
          "avg_success_rate": 87.34,
          "unique_providers": 245,
          "avg_speed": 1424
        }
      },
      {
        "domain": {
          "id": 2,
          "name": "BroadbandSearch.net"
        },
        "metrics": {
          "total_requests": 12890,
          "avg_success_rate": 89.12,
          "unique_providers": 198,
          "avg_speed": 1567
        }
      }
    ],
    "winner_by_metric": {
      "success_rate": "BroadbandSearch.net",
      "total_requests": "SmarterHome.ai",
      "avg_speed": "BroadbandSearch.net"
    }
  }
}
```

#### **POST /api/admin/dashboard/export**
Exporta dados em formato específico.

**Auth:** Admin token  
**Permissions:** Write access aos domínios

**Request:**
```json
{
  "domains": [1, 2],
  "start_date": "2025-10-01",
  "end_date": "2025-10-11",
  "format": "csv", // csv, excel, json, pdf
  "sections": ["summary", "providers", "geographic"],
  "email_to": "admin@example.com" // opcional
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "export_id": "exp_abc123",
    "status": "processing",
    "estimated_time": "2 minutes",
    "download_url": null // será preenchido quando concluído
  }
}
```

### Endpoints de Gerenciamento (Super Admin)

#### **POST /api/admin/domains**
Cria novo domínio.

**Auth:** Super Admin token

**Request:**
```json
{
  "name": "InternetFinder.com",
  "domain_url": "api.internetfinder.com",
  "site_id": "wp-prod-if-001",
  "timezone": "America/New_York"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "InternetFinder.com",
    "slug": "internetfinder-com",
    "api_key": "sk_live_xyz789...",
    "is_active": true
  }
}
```

#### **PUT /api/admin/domains/{id}**
Atualiza domínio.

**Auth:** Super Admin token

#### **DELETE /api/admin/domains/{id}**
Desativa domínio (soft delete).

**Auth:** Super Admin token

#### **POST /api/admin/domains/{id}/access**
Concede acesso a um admin.

**Auth:** Super Admin ou Domain Admin token

**Request:**
```json
{
  "admin_id": 42,
  "access_level": "read",
  "expires_at": "2026-10-11" // opcional
}
```

#### **DELETE /api/admin/domains/{id}/access/{adminId}**
Revoga acesso.

**Auth:** Super Admin ou Domain Admin token

---

## 📊 Dashboards

### 1. **Global Dashboard**

Visão agregada de todos os domínios acessíveis.

**URL:** `/dashboard`

**Seções:**

#### A. **Cards de Resumo**
```
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│ Total Domains   │ Total Reports   │ Total Requests  │ Avg Success %   │
│       12        │      4,380      │    1,845,230    │     87.34%      │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

#### B. **Gráfico: Requisições ao Longo do Tempo**
- Line chart
- Últimos 30 dias
- Por domínio (múltiplas linhas)

#### C. **Tabela: Top Provedores Globais**
| Provider | Total Requests | Avg Speed | States | Domains |
|----------|----------------|-----------|---------|---------|
| Earthlink| 45,892         | 1,200 Mbps| 48      | 8       |
| AT&T     | 39,201         | 1,465 Mbps| 47      | 10      |

#### D. **Mapa: Distribuição Geográfica**
- Heatmap dos EUA
- Cor baseada em volume de requisições
- Tooltip com detalhes do estado

#### E. **Gráfico: Tecnologias**
- Pie chart
- Distribuição agregada de tecnologias

### 2. **Domain-Specific Dashboard**

Análise detalhada de um único domínio.

**URL:** `/dashboard/domains/{slug}`

**Seções:**

#### A. **Header com Info do Domínio**
```
SmarterHome.ai (zip.50g.io)
Status: Active | Last Report: 2 hours ago
Total Reports: 365 | Average Success Rate: 87.34%
```

#### B. **Histórico de Métricas**
- Múltiplos gráficos lado a lado
- Requests, Success Rate, Avg Speed
- Comparação com período anterior

#### C. **Análise de Provedores**
- Top 20 provedores
- Tabela sortable e filterable
- Drill-down por estado/tecnologia

#### D. **Performance por Estado**
- Tabela com todos os estados
- Métricas: requests, speed, providers
- Export para CSV

### 3. **Comparative Dashboard**

Compara N domínios selecionados.

**URL:** `/dashboard/compare?domains=1,2,5`

**Seções:**

#### A. **Seletor de Domínios**
```
[x] SmarterHome.ai
[x] BroadbandSearch.net
[ ] InternetFinder.com
[x] ConnectNow.org

[Compare Selected] [Clear All]
```

#### B. **Comparação Side-by-Side**
```
┌──────────────────┬──────────────┬──────────────┬──────────────┐
│ Metric           │ SmarterHome  │ Broadband    │ ConnectNow   │
├──────────────────┼──────────────┼──────────────┼──────────────┤
│ Total Requests   │ 154,230      │ 128,900      │ 98,450       │
│ Success Rate     │ 87.34% ⭐    │ 89.12% 🏆    │ 84.67%       │
│ Unique Providers │ 245          │ 198          │ 176          │
│ Avg Speed        │ 1,424 Mbps   │ 1,567 Mbps 🏆│ 1,289 Mbps   │
└──────────────────┴──────────────┴──────────────┴──────────────┘
```

#### C. **Gráfico de Tendências Comparativo**
- Múltiplas linhas
- Uma cor por domínio
- Toggle de métricas

#### D. **Venn Diagram: Provedores**
- Interseção de provedores entre domínios
- Exclusive vs Shared

### 4. **Custom Reports**

Builder de relatórios personalizados.

**URL:** `/dashboard/reports/builder`

**Features:**

#### A. **Seleção de Dados**
```
Domains:    [ Multi-select dropdown ]
Date Range: [2025-10-01] to [2025-10-11]
Group By:   ( ) Day  (•) Week  ( ) Month

Metrics:
[x] Total Requests
[x] Success Rate
[ ] Avg Speed
[x] Top Providers
[ ] Geographic Distribution
```

#### B. **Filters**
```
State:      [ All States ▼ ]
Provider:   [ All Providers ▼ ]
Technology: [ All Technologies ▼ ]
```

#### C. **Visualização**
```
Type: [ Table ▼ ]
      - Table
      - Line Chart
      - Bar Chart
      - Pie Chart
      - Heatmap
```

#### D. **Ações**
```
[View Report] [Export to CSV] [Save Template] [Schedule]
```

---

## 🔒 Segurança

### Autenticação

#### 1. **Domain API Keys**
- Geradas automaticamente na criação do domínio
- Formato: `sk_live_{64_char_random}`
- Armazenadas com hash (bcrypt)
- Rate limiting: 2 requests/dia

#### 2. **Admin Tokens (Sanctum)**
- JWT tokens
- Expiração: 24 horas
- Refresh automático
- Revogáveis

### Autorização

#### Hierarquia de Permissões
```
Super Admin
    ├── Domain Admin (Domain A)
    │   ├── Write Access
    │   └── Read Only
    ├── Domain Admin (Domain B)
    └── ...
```

#### Verificação em Cada Request
```php
// 1. Verificar autenticação (Sanctum)
// 2. Verificar se é Super Admin (bypass)
// 3. Verificar acesso ao(s) domínio(s) solicitado(s)
// 4. Verificar nível de acesso necessário
// 5. Verificar expiração do acesso
```

### Validação de Dados

#### JSON Schema Validation
```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["source", "metadata", "summary"],
  "properties": {
    "source": {
      "type": "object",
      "required": ["domain", "site_id"],
      ...
    },
    ...
  }
}
```

#### Sanitização
- Todos os strings são sanitizados
- HTML tags removidos
- SQL injection prevenido (prepared statements)

### Rate Limiting

#### Por Domain (Ingestão)
```php
RateLimiter::for('ingest', function (Request $request) {
    $domain = $request->user(); // domain via API key
    return Limit::perDay(2)->by($domain->id);
});
```

#### Por Admin (Consulta)
```php
RateLimiter::for('api-admin', function (Request $request) {
    $admin = $request->user();
    return Limit::perMinute(60)->by($admin->id);
});
```

### Auditoria

#### Tabela: `audit_logs`
```sql
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    admin_id BIGINT REFERENCES admins(id),
    domain_id BIGINT REFERENCES domains(id),
    action VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50),
    resource_id BIGINT,
    details JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_admin_action (admin_id, action),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_created_at (created_at)
);
```

**Ações auditadas:**
- Report ingestion
- Report access
- Export data
- Grant/revoke access
- Domain creation/update
- Admin login/logout

### Proteções

#### 1. **SQL Injection**
- Eloquent ORM (prepared statements)
- Validação de inputs

#### 2. **XSS**
- Sanitização de outputs
- CSP headers

#### 3. **CSRF**
- Tokens para formulários
- SameSite cookies

#### 4. **DDoS**
- Rate limiting agressivo
- CloudFlare/CDN

#### 5. **Data Leakage**
- Verificação de permissões em cada query
- Soft deletes com verificação

---

## ⚡ Performance

### Otimizações de Database

#### 1. **Índices Estratégicos**
```sql
-- Queries por domínio e data
CREATE INDEX idx_reports_domain_date ON reports(domain_id, report_date DESC);

-- Busca de provedores
CREATE INDEX idx_providers_name ON report_providers(provider_name);

-- Queries geográficas
CREATE INDEX idx_geographic_state ON report_geographic(state_code);

-- Acesso de admins
CREATE INDEX idx_admin_access ON admin_domain_access(admin_id, domain_id, is_active);
```

#### 2. **Particionamento (Futuro)**
```sql
-- Particionar `reports` por ano
CREATE TABLE reports_2025 PARTITION OF reports
    FOR VALUES FROM ('2025-01-01') TO ('2026-01-01');

CREATE TABLE reports_2026 PARTITION OF reports
    FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');
```

#### 3. **Aggregation Tables**
```sql
-- Pré-calcular agregações mensais
CREATE TABLE monthly_aggregations (
    id BIGSERIAL PRIMARY KEY,
    domain_id BIGINT REFERENCES domains(id),
    year INTEGER NOT NULL,
    month INTEGER NOT NULL,
    total_requests BIGINT,
    avg_success_rate DECIMAL(5,2),
    unique_providers INTEGER,
    aggregated_data JSONB,
    created_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(domain_id, year, month)
);
```

### Caching

#### 1. **Redis Cache**

**Dashboard Summary (30 minutos):**
```php
Cache::remember("dashboard_summary_{$adminId}_{$dateRange}", 1800, function () {
    return $this->calculateSummary();
});
```

**Domain List (1 hora):**
```php
Cache::remember("admin_domains_{$adminId}", 3600, function () {
    return $this->getAccessibleDomains();
});
```

**Top Providers (1 hora):**
```php
Cache::remember("top_providers_{$domainIds}_{$dateRange}", 3600, function () {
    return $this->getTopProviders();
});
```

#### 2. **Cache Invalidation**
```php
// Quando novo relatório é processado
Cache::tags(['dashboard', "domain_{$domainId}"])->flush();

// Quando permissão é alterada
Cache::forget("admin_domains_{$adminId}");
```

### Queue Management

#### 1. **Prioridades de Jobs**
```php
// Alta prioridade (relatórios recentes)
ProcessReportJob::dispatch($reportId)->onQueue('high');

// Baixa prioridade (recálculo de agregações)
RecalculateAggregationsJob::dispatch()->onQueue('low');
```

#### 2. **Job Batching**
```php
// Processar múltiplos relatórios em batch
Bus::batch([
    new ProcessReportJob($reportId1),
    new ProcessReportJob($reportId2),
    new ProcessReportJob($reportId3),
])->dispatch();
```

### Database Optimization

#### 1. **EXPLAIN ANALYZE**
Rodar regularmente nas queries mais usadas:
```sql
EXPLAIN ANALYZE
SELECT r.*, rs.total_requests, rs.success_rate
FROM reports r
JOIN report_summaries rs ON r.id = rs.report_id
WHERE r.domain_id IN (1, 2, 5)
  AND r.report_date >= '2025-10-01'
  AND r.report_date <= '2025-10-11'
ORDER BY r.report_date DESC;
```

#### 2. **Connection Pooling**
```php
// config/database.php
'pgsql' => [
    'pool' => [
        'min' => 5,
        'max' => 20,
    ],
],
```

#### 3. **Query Optimization**
```php
// BAD: N+1 queries
$reports = Report::all();
foreach ($reports as $report) {
    echo $report->domain->name;
}

// GOOD: Eager loading
$reports = Report::with('domain')->get();
foreach ($reports as $report) {
    echo $report->domain->name;
}
```

### API Response Optimization

#### 1. **Pagination**
- Sempre paginar resultados
- Max 100 items por página
- Cursor pagination para grandes datasets

#### 2. **Field Selection**
```http
GET /api/admin/reports?fields=id,report_date,status
```

#### 3. **Compression**
- Gzip responses > 1KB
- Brotli para browsers modernos

### Monitoring

#### Métricas a Monitorar
```
- API response time (p50, p95, p99)
- Database query time
- Queue wait time / processing time
- Cache hit ratio
- Error rate
- Report ingestion rate
- Active connections
```

#### Alertas
```
- Response time > 2s
- Error rate > 5%
- Queue delay > 5 minutes
- Disk usage > 80%
- Failed report processing
```

---

## 🎯 Casos de Uso

### Caso 1: Super Admin Cria Novo Domínio Parceiro

**Ator:** Super Admin  
**Objetivo:** Adicionar novo site parceiro ao sistema

**Fluxo:**
1. Super Admin acessa `/admin/domains/create`
2. Preenche formulário:
   - Nome: "InternetFinder.com"
   - URL: "api.internetfinder.com"
   - Timezone: "America/New_York"
3. Sistema gera API key automaticamente
4. Sistema cria registro em `domains`
5. Sistema exibe API key (única visualização)
6. Super Admin envia API key para o parceiro via canal seguro

**Resultado:**
- Novo domínio criado com `is_active = true`
- API key gerada e retornada
- Domínio pronto para enviar relatórios

---

### Caso 2: Domínio Parceiro Envia Relatório Diário

**Ator:** Sistema automatizado do domínio parceiro  
**Objetivo:** Enviar dados diários para agregação

**Fluxo:**
1. Cron job roda às 00:00 no servidor do parceiro
2. Sistema gera JSON do dia anterior
3. POST para `/api/reports/ingest` com API key no header
4. Sistema valida:
   - API key válida? ✓
   - Estrutura JSON correta? ✓
   - Já existe relatório para essa data? ✗
5. Sistema salva em `reports` com `status = 'pending'`
6. Sistema retorna `202 Accepted`
7. Job `ProcessReportJob` processa em background
8. Dados normalizados salvos em tabelas relacionais
9. Status atualizado para `completed`
10. Cache do dashboard invalidado

**Resultado:**
- Relatório armazenado e processado
- Dados disponíveis para visualização
- Dashboard atualizado automaticamente

---

### Caso 3: Domain Admin Concede Acesso a Analista

**Ator:** Domain Admin (tem access_level='admin' para domínio X)  
**Objetivo:** Permitir que analista visualize dados

**Fluxo:**
1. Domain Admin acessa `/admin/domains/5/access`
2. Busca analista por email: "analyst@company.com"
3. Seleciona nível de acesso: "Read Only"
4. Define expiração: 90 dias
5. Clica em "Grant Access"
6. Sistema verifica:
   - Admin tem `access_level='admin'` para domínio 5? ✓
   - Analista existe no sistema? ✓
7. Sistema cria registro em `admin_domain_access`
8. Sistema envia email para analista
9. Sistema registra ação em `audit_logs`

**Resultado:**
- Analista pode visualizar dados do domínio 5
- Acesso expira automaticamente em 90 dias
- Ação auditada

---

### Caso 4: Analista Visualiza Dashboard Global

**Ator:** Analista (tem read access para domínios 1, 2, 5)  
**Objetivo:** Ver métricas agregadas dos domínios acessíveis

**Fluxo:**
1. Analista faz login
2. Sistema identifica domínios acessíveis: [1, 2, 5]
3. Analista acessa `/dashboard`
4. Sistema carrega métricas dos últimos 30 dias
5. Sistema verifica cache:
   - Cache hit? Retorna dados cacheados
   - Cache miss? Query database e cacheia
6. Dashboard renderiza:
   - Cards de resumo
   - Gráfico de tendências
   - Top provedores agregados
   - Mapa geográfico
7. Analista aplica filtro: "Estado = California"
8. Sistema requery com filtro
9. Dashboard atualiza dinamicamente

**Resultado:**
- Analista vê apenas dados dos domínios permitidos
- Métricas agregadas corretamente
- Performance otimizada com cache

---

### Caso 5: Marketing Manager Compara 3 Domínios

**Ator:** Marketing Manager (tem write access para domínios 1, 2, 3, 4)  
**Objetivo:** Comparar performance de 3 sites específicos

**Fluxo:**
1. Manager acessa `/dashboard/compare`
2. Seleciona domínios: [1, 2, 4]
3. Define período: Últimos 7 dias
4. Clica em "Compare"
5. Sistema verifica acesso aos 3 domínios ✓
6. Sistema query dados dos 3 domínios
7. Sistema calcula métricas comparativas:
   - Requests: Domain 1 lidera
   - Success Rate: Domain 2 lidera
   - Avg Speed: Domain 4 lidera
8. Dashboard renderiza:
   - Tabela side-by-side
   - Gráficos de tendências sobrepostos
   - Winners por métrica
9. Manager exporta para PDF
10. Sistema gera PDF e envia por email

**Resultado:**
- Comparação clara entre domínios
- Identificação de winners
- Relatório exportado para compartilhar

---

### Caso 6: Admin Detecta Anomalia

**Ator:** Admin (tem acesso a domínio 3)  
**Objetivo:** Investigar queda súbita de success rate

**Fluxo:**
1. Sistema detecta: success_rate domínio 3 caiu de 89% para 62%
2. Sistema envia alerta por email
3. Admin acessa `/dashboard/domains/domain-3`
4. Admin vê gráfico de tendência com queda acentuada
5. Admin aplica drill-down:
   - Por estado: "California" teve maior impacto
   - Por provedor: "AT&T" com alta taxa de falha
   - Por hora: Pico de falhas entre 14h-18h
6. Admin exporta dados para análise offline
7. Admin adiciona nota ao relatório
8. Admin compartilha findings com equipe

**Resultado:**
- Anomalia identificada e investigada
- Root cause analysis facilitado
- Dados exportados para análise detalhada

---

### Caso 7: Super Admin Revoga Acesso

**Ator:** Super Admin  
**Objetivo:** Remover acesso de ex-funcionário

**Fluxo:**
1. RH notifica que funcionário saiu da empresa
2. Super Admin acessa `/admin/users/42`
3. Vê todos os acessos do admin 42:
   - Domain 1: write access
   - Domain 3: admin access
   - Domain 5: read access
4. Clica em "Revoke All Access"
5. Sistema confirma ação
6. Sistema atualiza `admin_domain_access`:
   - `is_active = false`
7. Sistema invalida tokens ativos
8. Sistema registra revogação em audit log
9. Sistema envia notificação

**Resultado:**
- Acesso imediatamente revogado
- Tokens invalidados
- Ação auditada
- Segurança mantida

---

## 📈 Roadmap

### Fase 1: MVP (Meses 1-2)
- ✅ Modelo de dados
- ✅ API de ingestão
- ✅ Processamento básico
- ✅ Dashboard global simples
- ✅ Sistema de permissões

### Fase 2: Refinamento (Meses 3-4)
- ⏳ Dashboards comparativos
- ⏳ Exportação de dados
- ⏳ Cache e otimizações
- ⏳ Alertas automáticos

### Fase 3: Features Avançadas (Meses 5-6)
- 🔮 Machine learning para detecção de anomalias
- 🔮 Predições de tendências
- 🔮 API pública para parceiros
- 🔮 Webhooks

### Fase 4: Escalabilidade (Meses 7-8)
- 🔮 Particionamento de tabelas
- 🔮 Read replicas
- 🔮 CDN para assets
- 🔮 Microserviços

---

## 🤝 Conclusão

Este sistema foi projetado para:
- ✅ Escalar horizontalmente
- ✅ Manter performance com grandes volumes
- ✅ Garantir segurança e privacidade
- ✅ Fornecer insights valiosos
- ✅ Ser fácil de manter e evoluir

**Arquitetura modular** permite adicionar novos domínios parceiros sem modificar código.  
**Sistema de permissões granular** garante que cada admin vê apenas o que deve.  
**Caching estratégico** mantém dashboards rápidos mesmo com milhões de registros.

---

**Próximos Passos:**
1. Review desta documentação
2. Criar migrations
3. Implementar camada de ingestão
4. Desenvolver processamento
5. Construir API de consulta
6. Criar dashboard frontend

**Data:** 2025-10-11  
**Versão:** 1.0.0  
**Autor:** Equipe de Desenvolvimento

