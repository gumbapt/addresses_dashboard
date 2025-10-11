# ZipCode (CEP dos EUA) - Implementação Completa

## ✅ Implementação Finalizada

Sistema de gerenciamento de **ZIP Codes** (CEPs dos EUA) com criação sob demanda e normalização automática.

---

## 🎯 Propósito

### Por que criar entidades de ZipCode?

Baseado no `newdata.json` que contém:
```json
"top_zip_codes": [
  {"zip_code": 10038, "request_count": 13},
  {"zip_code": "07018", "request_count": 3},
  ...
],
"total_unique_zips": 979
```

**Benefícios:**

1. **Normalização de Dados**
   - 979 ZIP codes únicos por relatório!
   - Evitar duplicação em múltiplos reports
   - FK em vez de strings repetidas

2. **Validação e Consistência**
   - Normalização: int `7018` → string `"07018"`
   - Formato padrão de 5 dígitos
   - Validação de códigos válidos

3. **Geolocalização Precisa**
   - ZIP codes têm coordenadas específicas
   - Mais precisos que cidades para mapas
   - Heatmaps micro-geográficos

4. **Análises Granulares**
   - Métricas por código postal
   - Correlação ZIP ↔ ISP availability
   - Densidade de requests por área

---

## 📁 Arquivos Criados

### Domain Layer (2)
- ✅ `app/Domain/Entities/ZipCode.php`
- ✅ `app/Domain/Repositories/ZipCodeRepositoryInterface.php`

### Application Layer (4)
- ✅ `app/Application/DTOs/Geographic/ZipCodeDto.php`
- ✅ `app/Application/UseCases/Geographic/GetAllZipCodesUseCase.php`
- ✅ `app/Application/UseCases/Geographic/GetZipCodeByCodeUseCase.php`
- ✅ `app/Application/UseCases/Geographic/FindOrCreateZipCodeUseCase.php`

### Infrastructure Layer (1)
- ✅ `app/Infrastructure/Repositories/ZipCodeRepository.php`

### Presentation Layer (1)
- ✅ `app/Http/Controllers/Api/Admin/ZipCodeController.php`

### Helpers (1)
- ✅ `app/Helpers/ZipCodeHelper.php` - Normalização e validação

### Database (2)
- ✅ `database/migrations/2025_10_11_202000_create_zip_codes_table.php`
- ✅ `database/factories/ZipCodeFactory.php`

### Configuration (4)
- ✅ `app/Models/ZipCode.php`
- ✅ `app/Models/State.php` (relationship added)
- ✅ `app/Models/City.php` (relationship added)
- ✅ `app/Providers/DomainServiceProvider.php` (binding)
- ✅ `routes/api.php` (4 rotas)

---

## 📊 Estrutura de Dados

### ZipCode Entity

```php
ZipCode {
    +id: int
    +code: string - "90210", "07018"
    +stateId: int - FK para states
    +cityId: int|null - FK para cities (opcional)
    +latitude: float - 34.0901
    +longitude: float - -118.4065
    +type: string - "Standard", "PO Box", "Unique"
    +population: int - 21,733
    +isActive: bool - true
}
```

### Exemplo de ZipCode

```json
{
  "id": 1425,
  "code": "90210",
  "state_id": 5,
  "state_code": "CA",
  "state_name": "California",
  "city_id": 142,
  "city_name": "Beverly Hills",
  "latitude": 34.0901,
  "longitude": -118.4065,
  "type": "Standard",
  "population": 21733,
  "is_active": true
}
```

### Relacionamentos

```
State (California)
  ├── City (Beverly Hills)
  │     └── ZipCode (90210, 90211, 90212)
  └── ZipCode (standalone ZIPs sem city_id)
```

---

## 🔑 Feature Principal: Normalização Automática

### ZipCodeHelper

```php
use App\Helpers\ZipCodeHelper;

// Normalizar para 5 dígitos
ZipCodeHelper::normalize(7018);      // "07018"
ZipCodeHelper::normalize("07018");   // "07018"
ZipCodeHelper::normalize("7018");    // "07018"

// Validar formato
ZipCodeHelper::isValid("90210");     // true
ZipCodeHelper::isValid("902");       // false
ZipCodeHelper::isValid("90210-1234"); // true (ZIP+4)

// Extrair base de ZIP+4
ZipCodeHelper::getBase("90210-1234"); // "90210"
```

**Por quê importante?**
- JSON contém ZIPs como `int` E `string`
- Leading zeros são perdidos em int (7018 vs "07018")
- Normalização garante consistência

---

## 🔌 API Endpoints

### **GET /api/admin/zip-codes**
Lista ZIP codes com paginação.

**Query Parameters:**
- `page`, `per_page`
- `search` - Busca por código
- `state_id` - Filtrar por estado
- `city_id` - Filtrar por cidade
- `is_active` - Filtrar por status

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1425,
      "code": "90210",
      "state_id": 5,
      "city_id": 142,
      "latitude": 34.0901,
      "longitude": -118.4065,
      "population": 21733,
      "is_active": true
    }
  ],
  "pagination": {...}
}
```

### **GET /api/admin/zip-codes/{code}**
Busca ZIP por código.

**Example:**
```bash
GET /api/admin/zip-codes/90210
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1425,
    "code": "90210",
    "state_code": "CA",
    "city_name": "Beverly Hills",
    ...
  }
}
```

### **GET /api/admin/zip-codes/by-state/{stateId}**
Todos os ZIPs de um estado.

**Example:**
```bash
GET /api/admin/zip-codes/by-state/5  # California
```

### **GET /api/admin/zip-codes/by-city/{cityId}**
Todos os ZIPs de uma cidade.

**Example:**
```bash
GET /api/admin/zip-codes/by-city/142  # Beverly Hills
```

---

## 💡 Uso no Processamento de Reports

### Estratégia: FindOrCreate

```php
class ProcessReportJob
{
    public function processTopZipCodes($report, $zipCodesData)
    {
        foreach ($zipCodesData['top_zip_codes'] as $zipData) {
            // Normalizar código
            $code = \App\Helpers\ZipCodeHelper::normalize($zipData['zip_code']);
            
            // Inferir estado (ou buscar via lookup)
            $stateCode = $this->inferStateFromZip($code);
            $state = $this->stateRepository->findByCode($stateCode);
            
            if ($state) {
                // FindOrCreate ZIP code
                $zipCode = $this->zipCodeRepository->findOrCreate(
                    code: $code,
                    stateId: $state->getId(),
                    cityId: null, // Pode ser populado depois
                    latitude: null,
                    longitude: null
                );
                
                // Salvar métricas do relatório
                ReportZipCode::create([
                    'report_id' => $report->id,
                    'zip_code_id' => $zipCode->getId(),
                    'zip_code' => $code,
                    'request_count' => $zipData['request_count'],
                    'percentage' => $zipData['percentage']
                ]);
            }
        }
    }
}
```

### Tratamento de Tipos Mistos

JSON contém ZIPs como int E string:

```json
{"zip_code": 10038},     // int
{"zip_code": "07018"}    // string (leading zero!)
```

**Solução:**
```php
// Sempre normalizar
$normalized = ZipCodeHelper::normalize($zipData['zip_code']);
// 10038 → "10038"
// 7018 → "07018"
// "07018" → "07018"
```

---

## 🗺️ Estrutura Hierárquica Completa

```
State (51 fixos)
  ├── code: "CA"
  ├── name: "California"
  │
  ├── Cities (dinâmico - sob demanda)
  │   ├── Los Angeles
  │   │   └── ZipCodes
  │   │       ├── 90001
  │   │       ├── 90002
  │   │       └── 90003
  │   │
  │   └── San Francisco
  │       └── ZipCodes
  │           ├── 94102
  │           ├── 94103
  │           └── 94104
  │
  └── ZipCodes (standalone - sem city_id)
      ├── 90xxx
      ├── 91xxx
      └── 92xxx
```

---

## 🔍 Inferência de Estado (Helper)

### Método: `inferStateFromFirstDigit()`

Baseado no primeiro dígito do ZIP:

```php
ZipCodeHelper::inferStateFromFirstDigit('90210'); // 'CA' (9x = West)
ZipCodeHelper::inferStateFromFirstDigit('10001'); // 'NY' (1x = NY/PA)
ZipCodeHelper::inferStateFromFirstDigit('33101'); // 'FL' (3x = Southeast)
```

**Mapeamento aproximado:**
- 0x → Connecticut/Northeast
- 1x → New York/Pennsylvania
- 2x → DC/Maryland/Virginia
- 3x → Southeast (FL, GA, etc)
- 4x → Kentucky area
- 5x → North Central (MN, WI)
- 6x → Missouri area
- 7x → South Central (TX, LA)
- 8x → Mountain (CO, AZ)
- 9x → West Coast (CA, OR, WA)

**⚠️ Nota:** Aproximação! Melhor usar lookup table para precisão.

---

## 📈 Performance

### Índices Criados

```sql
INDEX idx_code (code)                 -- Busca por ZIP
INDEX idx_state_code (state_id, code) -- ZIPs por estado
INDEX idx_city_id (city_id)           -- ZIPs por cidade
UNIQUE (code)                         -- Cada ZIP existe apenas 1x
```

### Queries Otimizadas

```php
// ✅ RÁPIDO: Usa índice unique
ZipCode::where('code', '90210')->first();

// ✅ RÁPIDO: Usa índice state_code
ZipCode::where('state_id', 5)->where('code', 'like', '90%')->get();

// ⚠️ Pode ser lento com muitos registros
ZipCode::all(); // Potencialmente 42,000+ registros
```

---

## 🎨 Use Cases

### 1. Buscar ZIP no Frontend

```javascript
// Autocomplete de ZIP codes
const searchZip = async (query) => {
  const response = await fetch(`/api/admin/zip-codes?search=${query}&per_page=10`);
  return response.json();
};

// Input: "902"
// Output: 90210, 90211, 90212, 90220, etc.
```

### 2. Validar ZIP Code

```javascript
// Verificar se ZIP existe no sistema
const validateZip = async (zipCode) => {
  try {
    const response = await fetch(`/api/admin/zip-codes/${zipCode}`);
    if (response.ok) {
      const { data } = await response.json();
      return {
        valid: true,
        state: data.state_code,
        city: data.city_name
      };
    }
  } catch (e) {
    return { valid: false };
  }
};
```

### 3. Mapa de Calor por ZIP

```javascript
// Plotar requests por ZIP code
const zipData = await fetchZipCodesWithStats();

zipData.forEach(zip => {
  if (zip.latitude && zip.longitude) {
    heatmap.addPoint({
      lat: zip.latitude,
      lng: zip.longitude,
      weight: zip.total_requests,
      tooltip: `ZIP ${zip.code}: ${zip.total_requests} requests`
    });
  }
});
```

---

## 🔄 Enriquecimento de Dados

### Via US Census Bureau API (Gratuito)

```php
class EnrichZipCodeCommand extends Command
{
    public function handle()
    {
        $zipCodes = ZipCode::whereNull('city_id')->get();
        
        foreach ($zipCodes as $zipCode) {
            // Lookup via API
            $data = Http::get("https://api.census.gov/data/2020/dec/pl", [
                'get' => 'NAME,P1_001N',
                'for' => "zip code tabulation area:{$zipCode->code}"
            ])->json();
            
            if ($data) {
                $zipCode->update([
                    'population' => $data[1][1] ?? null,
                    // Outras informações...
                ]);
            }
            
            $this->info("Enriched: {$zipCode->code}");
        }
    }
}
```

### Via Geocoding API

```php
// Google Maps, MapBox, OpenStreetMap
$geocode = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
    'address' => $zipCode->code,
    'key' => env('GOOGLE_MAPS_API_KEY')
])->json();

$zipCode->update([
    'latitude' => $geocode['results'][0]['geometry']['location']['lat'],
    'longitude' => $geocode['results'][0]['geometry']['location']['lng']
]);
```

---

## 🎯 Estrutura da Tabela

### Migration

```sql
CREATE TABLE zip_codes (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    state_id BIGINT REFERENCES states(id) ON DELETE CASCADE,
    city_id BIGINT REFERENCES cities(id) ON DELETE SET NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    type VARCHAR(50),         -- Standard, PO Box, Unique
    population INTEGER,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE(code),
    INDEX idx_code (code),
    INDEX idx_state_code (state_id, code),
    INDEX idx_city_id (city_id)
);
```

### Constraints

- `UNIQUE(code)` - Cada ZIP existe apenas 1x
- `FK state_id` - Todo ZIP pertence a um estado
- `FK city_id NULLABLE` - ZIP pode ou não ter cidade associada
- `CASCADE DELETE` - Se estado deletado, ZIPs também
- `SET NULL` - Se cidade deletada, city_id vira null

---

## 💡 Desafios e Soluções

### 1. **Problema: JSON tem int e string**

```json
{"zip_code": 10038},   // ✅ OK
{"zip_code": 7018},    // ❌ Perde leading zero → 7018
{"zip_code": "07018"}  // ✅ OK - mantém zero
```

**Solução: Normalização**
```php
ZipCodeHelper::normalize(7018);    // "07018"
ZipCodeHelper::normalize("7018");  // "07018"
ZipCodeHelper::normalize("07018"); // "07018"
```

### 2. **Problema: ZIP sem informação de estado**

JSON não informa estado do ZIP diretamente.

**Soluções:**

**A) Lookup Table (Recomendado)**
```php
// Popular tabela com dataset completo
// Download: https://www.unitedstateszipcodes.org/
```

**B) Inferência por Prefixo (Aproximado)**
```php
ZipCodeHelper::inferStateFromFirstDigit('90210'); // CA
```

**C) API Externa**
```php
$stateCode = $this->zipLookupApi->getState('90210');
```

### 3. **Problema: ~42,000 ZIP codes nos EUA**

**Solução: Criação Sob Demanda (On-Demand)**
- Não popular todos antecipadamente
- Criar apenas quando aparecem em reports
- Database cresce organicamente

---

## 🚀 Como Usar

### 1. Executar Migration

```bash
php artisan migrate
```

### 2. (Opcional) Criar ZIPs de Teste

```bash
php artisan tinker

# Criar ZIP codes de teste
>>> App\Models\ZipCode::factory()->count(100)->create()
```

### 3. Testar API

```bash
# Buscar ZIP específico
GET /api/admin/zip-codes/90210

# ZIPs da California
GET /api/admin/zip-codes/by-state/5

# ZIPs de Los Angeles
GET /api/admin/zip-codes/by-city/142

# Buscar ZIPs que começam com "902"
GET /api/admin/zip-codes?search=902&per_page=20
```

### 4. Uso em Reports

```php
// No ProcessReportJob
foreach ($reportData['top_zip_codes'] as $zipData) {
    $normalizedCode = ZipCodeHelper::normalize($zipData['zip_code']);
    
    // Inferir ou lookup estado
    $stateCode = $this->inferState($normalizedCode);
    $state = $this->stateRepository->findByCode($stateCode);
    
    if ($state) {
        $zipCode = $this->zipCodeRepository->findOrCreate(
            code: $normalizedCode,
            stateId: $state->getId()
        );
        
        ReportZipCode::create([
            'report_id' => $report->id,
            'zip_code_id' => $zipCode->getId(),
            'zip_code' => $normalizedCode,
            'request_count' => $zipData['request_count'],
            'percentage' => $zipData['percentage']
        ]);
    }
}
```

---

## 📋 Tipos de ZIP Codes

### Standard (Padrão)
- Delivery addresses normais
- ~30,000 nos EUA
- Exemplo: 90210, 10001

### PO Box
- Apenas caixas postais
- ~7,000 nos EUA
- Exemplo: 10001 (NY)

### Unique
- Grandes instituições/empresas
- ~5,000 nos EUA
- Exemplo: 20505 (CIA)

---

## 🎯 Estrutura Geográfica COMPLETA

### 4 Níveis Implementados ✅

```
1. States (51 fixos - seeded)
      ├── CA, NY, TX, etc
      │
   2. Cities (dinâmico - sob demanda)
      ├── Los Angeles, New York, etc
      │
   3. ZipCodes (dinâmico - sob demanda)
      ├── 90210, 10001, etc
      │
   4. Reports
      └── Métricas por State/City/ZIP
```

### Relacionamentos

```sql
states (1) ──┬──→ cities (*)
             │
             └──→ zip_codes (*)
                      ↑
cities (1) ───────────┘ (optional FK)
```

---

## ✅ Checklist

- ✅ Migration criada com FKs
- ✅ Model ZipCode com relationships
- ✅ Entity ZipCode imutável
- ✅ DTO ZipCodeDto com toArray()
- ✅ Repository interface completa
- ✅ Repository com findOrCreate
- ✅ ZipCodeHelper com normalização
- ✅ 3 Use Cases criados
- ✅ Controller com 4 endpoints
- ✅ 4 rotas registradas
- ✅ Binding no ServiceProvider
- ✅ Factory configurada
- ✅ Relationships em State e City
- ✅ Unique constraint no code
- ✅ Normalização automática

---

## 🎉 Status

✅ **Pronto para uso!**

**Total de arquivos:** 15 criados/modificados  
**Estratégia:** Criação sob demanda com normalização automática  
**Endpoints:** 4 rotas  
**Helper:** ZipCodeHelper para normalização

**Features:**
- ✅ Normalização automática (int → string com zeros)
- ✅ FindOrCreate pattern
- ✅ Relacionamentos com State e City
- ✅ Paginação e múltiplos filtros
- ✅ Busca por código, estado ou cidade
- ✅ Validação de formato
- ✅ Suporte para coordenadas geográficas

---

## 🔮 Próximo Módulo

**Reports** - Receber, validar e processar JSONs dos domínios!

Agora temos toda a infraestrutura geográfica:
- ✅ Domains (parceiros)
- ✅ States (51 estados)
- ✅ Cities (sob demanda)
- ✅ ZipCodes (sob demanda + normalização)

**Pronto para processar os relatórios!** 🚀

---

**Data:** 2025-10-11  
**Versão:** 1.0.0  
**Status:** ✅ Production Ready

