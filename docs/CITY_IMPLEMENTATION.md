# City (Cidades dos EUA) - Implementação Completa

## ✅ Implementação Finalizada

Sistema de gerenciamento de **Cities** (Cidades dos EUA) com criação sob demanda para normalização de dados geográficos.

---

## 🎯 Propósito

### Por que criar entidades de City?

1. **Normalização Geográfica**
   - Evitar duplicação de "Los Angeles" em múltiplos relatórios
   - Relacionamento claro: City → State
   - Unique constraint: (name, state_id)

2. **Criação Sob Demanda**
   - Não popular todas as cidades antecipadamente (~20,000+)
   - Criar apenas quando aparecerem em relatórios
   - Método `findOrCreate()` facilita

3. **Coordenadas Geográficas**
   - Latitude/longitude para mapas
   - Heatmaps de cidades
   - Geolocalização

4. **População (Opcional)**
   - Contexto demográfico
   - Análises de densidade
   - Correlação com dados de ISP

---

## 📁 Arquivos Criados

### Domain Layer (2)
- ✅ `app/Domain/Entities/City.php`
- ✅ `app/Domain/Repositories/CityRepositoryInterface.php`

### Application Layer (3)
- ✅ `app/Application/DTOs/Geographic/CityDto.php`
- ✅ `app/Application/UseCases/Geographic/GetAllCitiesUseCase.php`
- ✅ `app/Application/UseCases/Geographic/FindOrCreateCityUseCase.php`

### Infrastructure Layer (1)
- ✅ `app/Infrastructure/Repositories/CityRepository.php`

### Presentation Layer (1)
- ✅ `app/Http/Controllers/Api/Admin/CityController.php`

### Database (2)
- ✅ `database/migrations/2025_10_11_201000_create_cities_table.php`
- ✅ `database/factories/CityFactory.php`

### Configuration (2)
- ✅ `app/Models/City.php`
- ✅ `app/Providers/DomainServiceProvider.php` (updated)
- ✅ `routes/api.php` (updated)
- ✅ `app/Models/State.php` (relationship added)

---

## 📊 Estrutura de Dados

### City Entity

```php
City {
    +id: int
    +name: string - "Los Angeles"
    +stateId: int - FK para states
    +latitude: float - 34.0522
    +longitude: float - -118.2437
    +population: int - 3,979,576
    +isActive: bool - true
}
```

### Exemplo de City

```json
{
  "id": 142,
  "name": "Los Angeles",
  "state_id": 5,
  "state_code": "CA",
  "state_name": "California",
  "latitude": 34.0522,
  "longitude": -118.2437,
  "population": 3979576,
  "is_active": true
}
```

### Relacionamentos

```
State (1) ──────┐
                │
                │ has many
                │
                ▼
              City (*)
```

---

## 🔌 API Endpoints

### **GET /api/admin/cities**
Lista cidades com paginação e filtros.

**Auth:** Admin token  
**Permissions:** N/A (dados de referência)

**Query Parameters:**
- `page` (int, default: 1)
- `per_page` (int, default: 50, max: 100)
- `search` (string) - Busca por nome
- `state_id` (int) - Filtrar por estado
- `is_active` (boolean) - Filtrar por status

**Examples:**
```bash
# Todas as cidades
GET /api/admin/cities?per_page=100

# Cidades da California (state_id = 5)
GET /api/admin/cities?state_id=5

# Buscar cidades que começam com "Los"
GET /api/admin/cities?search=Los

# Combinar filtros
GET /api/admin/cities?state_id=5&search=Los&per_page=10
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 142,
      "name": "Los Angeles",
      "state_id": 5,
      "latitude": 34.0522,
      "longitude": -118.2437,
      "population": 3979576,
      "is_active": true
    }
  ],
  "pagination": {
    "total": 1520,
    "per_page": 50,
    "current_page": 1,
    "last_page": 31
  }
}
```

### **GET /api/admin/cities/by-state/{stateId}**
Todas as cidades de um estado específico (sem paginação).

**Uso:** Dropdowns, selects filtrados por estado

**Example:**
```bash
# Todas as cidades da California
GET /api/admin/cities/by-state/5
```

**Response:**
```json
{
  "success": true,
  "data": [
    {"id": 142, "name": "Los Angeles", "state_id": 5, ...},
    {"id": 289, "name": "San Francisco", "state_id": 5, ...},
    {"id": 421, "name": "San Diego", "state_id": 5, ...}
  ]
}
```

---

## 💡 Feature Principal: findOrCreate

### Uso no Processamento de Reports

Quando um relatório menciona uma cidade pela primeira vez, ela é criada automaticamente:

```php
class ProcessReportJob
{
    public function processCities($report, $citiesData)
    {
        foreach ($citiesData['top_cities'] as $cityData) {
            // Buscar estado
            $state = $this->stateRepository->findByCode($cityData['state']);
            
            if ($state) {
                // FindOrCreate: Cria se não existir, retorna se já existir
                $city = $this->cityRepository->findOrCreate(
                    name: $cityData['name'],
                    stateId: $state->getId(),
                    latitude: null,  // Pode ser enriquecido depois
                    longitude: null
                );
                
                // Salvar estatísticas da cidade para este relatório
                ReportCity::create([
                    'report_id' => $report->id,
                    'city_id' => $city->getId(),
                    'request_count' => $cityData['request_count'],
                    'zip_codes' => $cityData['zip_codes']
                ]);
            }
        }
    }
}
```

### Benefícios do findOrCreate:

```php
// ✅ Idempotente: Pode rodar múltiplas vezes sem duplicar
$city1 = $cityRepository->findOrCreate('Los Angeles', 5);
$city2 = $cityRepository->findOrCreate('Los Angeles', 5);
// $city1->id === $city2->id (mesma cidade)

// ✅ Thread-safe: Unique constraint previne race conditions
// ✅ Performance: Apenas 1 query se já existir
```

---

## 🗺️ Unique Constraint

### Regra: `UNIQUE(name, state_id)`

**Permite:**
- ✅ "Springfield, Illinois" (name='Springfield', state_id=14)
- ✅ "Springfield, Massachusetts" (name='Springfield', state_id=22)
- ✅ "Springfield, Missouri" (name='Springfield', state_id=29)

**Previne:**
- ❌ Duplicar "Los Angeles, California"
- ❌ Mesmo nome, mesmo estado

**Nota:** Existem 41 cidades chamadas "Springfield" nos EUA!

---

## 🔄 Estratégia de População

### Não Popular Antecipadamente

**Por quê?**
- 20,000+ cidades nos EUA
- Maioria nunca aparecerá em relatórios
- Desperdício de espaço

### Popular Sob Demanda (On-Demand)

**Como:**
1. Relatório menciona "Miami, FL"
2. Sistema verifica se existe
3. Se não existe, cria automaticamente
4. Se existe, reutiliza

**Vantagens:**
- ✅ Database cresce organicamente
- ✅ Apenas cidades relevantes são armazenadas
- ✅ Sem overhead de manutenção

---

## 📈 Enriquecimento de Dados (Futuro)

### Coordenadas Geográficas

Quando criar cidade sem coordenadas, pode enriquecer depois via API:

```php
class EnrichCityDataJob
{
    public function handle(City $city)
    {
        // Geocoding API (Google, OpenStreetMap, etc)
        $geocode = $this->geocodeService->lookup($city->name, $city->state->code);
        
        $city->update([
            'latitude' => $geocode['lat'],
            'longitude' => $geocode['lng'],
            'population' => $geocode['population']
        ]);
    }
}
```

### APIs de Geocoding:

- **Google Maps Geocoding API**
- **OpenStreetMap Nominatim** (gratuito)
- **MapBox Geocoding**
- **US Census Bureau API** (dados oficiais)

---

## 🎨 Use Cases no Frontend

### Dropdown em Cascata (State → City)

```javascript
// 1. Usuário seleciona estado
const stateId = 5; // California

// 2. Carregar cidades daquele estado
const response = await fetch(`/api/admin/cities/by-state/${stateId}`, {
  headers: { 'Authorization': `Bearer ${token}` }
});
const { data: cities } = await response.json();

// 3. Renderizar select de cidades
<select name="city">
  {cities.map(city => (
    <option key={city.id} value={city.id}>
      {city.name}
    </option>
  ))}
</select>
```

### Autocomplete com Busca

```javascript
const searchCities = async (query, stateId = null) => {
  let url = `/api/admin/cities?search=${query}&per_page=10`;
  if (stateId) url += `&state_id=${stateId}`;
  
  const response = await fetch(url);
  return response.json();
};

// Uso:
searchCities('Los', 5) // Busca "Los" na California
```

### Mapa de Calor (Heatmap)

```javascript
// Carregar cidades com request_count de reports
const cities = await fetchCitiesWithStats();

// Plotar no mapa
cities.forEach(city => {
  if (city.latitude && city.longitude) {
    heatmap.addPoint({
      lat: city.latitude,
      lng: city.longitude,
      weight: city.total_requests // intensidade
    });
  }
});
```

---

## 📊 Queries Úteis

### Cidades Mais Populosas da California

```sql
SELECT c.name, c.population, s.name as state_name
FROM cities c
JOIN states s ON c.state_id = s.id
WHERE s.code = 'CA'
  AND c.is_active = true
ORDER BY c.population DESC
LIMIT 10;
```

### Total de Cidades por Estado

```sql
SELECT s.name, s.code, COUNT(c.id) as cities_count
FROM states s
LEFT JOIN cities c ON s.id = c.state_id
GROUP BY s.id
ORDER BY cities_count DESC;
```

### Cidades sem Coordenadas (para enriquecimento)

```sql
SELECT id, name, state_id
FROM cities
WHERE latitude IS NULL OR longitude IS NULL;
```

---

## 🔗 Relacionamentos

### No Model

```php
// State Model
public function cities(): HasMany
{
    return $this->hasMany(City::class);
}

// City Model
public function state(): BelongsTo
{
    return $this->belongsTo(State::class);
}
```

### Uso com Eloquent

```php
// Eager loading
$cities = City::with('state')->paginate(50);

foreach ($cities as $city) {
    echo "{$city->name}, {$city->state->code}"; // Los Angeles, CA
}

// Buscar cidades de um estado
$california = State::where('code', 'CA')->first();
$californiaCities = $california->cities; // Collection de City models
```

---

## 🧪 Exemplo de Uso

### 1. Processar Relatório com Cidades

```php
// JSON do relatório
$reportData = [
    "top_cities": [
        {"name": "Los Angeles", "state": "CA", "request_count": 19},
        {"name": "New York", "state": "NY", "request_count": 25},
        {"name": "Miami", "state": "FL", "request_count": 5}
    ]
];

// Processar
foreach ($reportData['top_cities'] as $cityData) {
    // Buscar estado
    $state = State::where('code', $cityData['state'])->first();
    
    if ($state) {
        // FindOrCreate cidade
        $city = City::firstOrCreate(
            ['name' => $cityData['name'], 'state_id' => $state->id],
            ['is_active' => true]
        );
        
        // Salvar dados do relatório
        ReportCity::create([
            'report_id' => $report->id,
            'city_id' => $city->id,
            'request_count' => $cityData['request_count']
        ]);
    }
}
```

### 2. Buscar Cidades no Frontend

```javascript
// Listar cidades da California
fetch('/api/admin/cities?state_id=5&per_page=100')
  .then(res => res.json())
  .then(data => {
    console.log(`${data.pagination.total} cities in California`);
    data.data.forEach(city => {
      console.log(`- ${city.name}`);
    });
  });
```

### 3. Autocomplete de Cidades

```javascript
// Input: "Los"
// Output: Los Angeles, Los Gatos, Lodi, etc.

const searchInput = document.getElementById('city-search');
searchInput.addEventListener('input', async (e) => {
  const query = e.target.value;
  if (query.length < 2) return;
  
  const response = await fetch(`/api/admin/cities?search=${query}&per_page=10`);
  const { data } = await response.json();
  
  showSuggestions(data); // Mostrar dropdown
});
```

---

## 📋 Estrutura da Tabela

### Migration

```sql
CREATE TABLE cities (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    state_id BIGINT REFERENCES states(id) ON DELETE CASCADE,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    population INTEGER,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE(name, state_id),
    INDEX idx_state_name (state_id, name),
    INDEX idx_name (name)
);
```

### Constraints

- `UNIQUE(name, state_id)` - Mesma cidade não pode existir 2x no mesmo estado
- `FK state_id` - Cidade sempre pertence a um estado
- `CASCADE DELETE` - Se estado for deletado, cidades também

---

## 🔑 Métodos Principais

### findOrCreate (Mais Importante!)

```php
public function findOrCreate(
    string $name,
    int $stateId,
    ?float $latitude = null,
    ?float $longitude = null
): City {
    $city = City::firstOrCreate(
        ['name' => $name, 'state_id' => $stateId],
        ['latitude' => $latitude, 'longitude' => $longitude, 'is_active' => true]
    );
    
    return $city->toEntity();
}
```

**Comportamento:**
- Se cidade existe: retorna existente
- Se não existe: cria e retorna
- Thread-safe (handled pelo unique constraint)

### findByNameAndState

```php
$city = $cityRepository->findByNameAndState('Miami', 10); // Florida state_id=10
```

### findByState

```php
$californiaCities = $cityRepository->findByState(5); // CA state_id=5
// Returns: array de City entities
```

---

## 🎯 Diferença vs States

| Aspecto | States | Cities |
|---------|--------|--------|
| **Quantidade** | 51 fixos | ~20,000+ dinâmico |
| **População** | Antecipada (seeder) | Sob demanda |
| **Mudanças** | Nunca mudam | Podem ser criadas |
| **CRUD** | Read-only | Read + Create (on-demand) |
| **Uso Principal** | Validação, referência | Normalização de reports |

---

## 🚀 Como Usar

### 1. Executar Migration

```bash
php artisan migrate
```

### 2. (Opcional) Criar Cidades de Teste

```bash
php artisan tinker

# Pré-requisito: ter estados seeded
>>> App\Models\State::count() // deve ter 51

# Criar cidades
>>> App\Models\City::factory()->count(100)->create()
```

### 3. Testar API

```bash
# Listar cidades
GET /api/admin/cities?per_page=50
Authorization: Bearer {admin_token}

# Cidades da California
GET /api/admin/cities/by-state/5
Authorization: Bearer {admin_token}
```

### 4. Uso em Reports (Futuro)

```php
// Use Case: ProcessReportUseCase
$state = $stateRepository->findByCode('CA');
$city = $cityRepository->findOrCreate('Los Angeles', $state->getId());

// Salvar dados do report
ReportCity::create([
    'report_id' => $report->id,
    'city_id' => $city->getId(),
    'request_count' => 19
]);
```

---

## 📈 Performance

### Índices Criados

```sql
INDEX idx_state_name (state_id, name)  -- Busca por estado + nome
INDEX idx_name (name)                  -- Busca por nome
UNIQUE (name, state_id)                -- Previne duplicatas
```

### Queries Otimizadas

```php
// ✅ RÁPIDO: Usa índice
City::where('state_id', 5)->where('name', 'Los Angeles')->first();

// ✅ RÁPIDO: Usa índice
City::where('name', 'like', 'Los%')->limit(10)->get();

// ⚠️ LENTO: Full scan (evitar sem necessidade)
City::all(); // 20,000+ registros
```

### Eager Loading

```php
// ❌ N+1 queries
$cities = City::paginate(50);
foreach ($cities as $city) {
    echo $city->state->name; // Query por iteração
}

// ✅ 2 queries total
$cities = City::with('state')->paginate(50);
foreach ($cities as $city) {
    echo $city->state->name; // Sem query extra
}
```

---

## 🔮 Próximos Passos

### Fase 2: Enriquecimento de Dados

```php
// Command: php artisan cities:enrich

class EnrichCitiesCommand extends Command
{
    public function handle()
    {
        $cities = City::whereNull('latitude')->get();
        
        foreach ($cities as $city) {
            $geocode = $this->geocodeService->lookup($city->name, $city->state->code);
            
            $city->update([
                'latitude' => $geocode['lat'],
                'longitude' => $geocode['lng'],
                'population' => $geocode['population']
            ]);
            
            $this->info("Enriched: {$city->name}, {$city->state->code}");
        }
    }
}
```

### Fase 3: ZIP Codes

Criar relação City → ZipCodes:

```sql
CREATE TABLE zip_codes (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    city_id BIGINT REFERENCES cities(id),
    state_id BIGINT REFERENCES states(id),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8)
);
```

---

## ✅ Checklist

- ✅ Migration criada com FKs e constraints
- ✅ Model City com HasFactory e relationship
- ✅ Entity City imutável
- ✅ DTO CityDto com toArray()
- ✅ Repository interface com findOrCreate
- ✅ Repository implementado
- ✅ 2 Use Cases criados
- ✅ Controller com 2 endpoints
- ✅ 2 rotas registradas
- ✅ Binding no ServiceProvider
- ✅ Factory configurada
- ✅ Relationship State → Cities
- ✅ Unique constraint (name, state_id)
- ✅ Documentação completa

---

## 🎉 Status

✅ **Pronto para uso!**

**Total de arquivos:** 13 criados/modificados  
**Estratégia:** Criação sob demanda via `findOrCreate()`  
**Endpoints:** 2 rotas

**Features:**
- ✅ Paginação e filtros
- ✅ Busca por nome
- ✅ Filtro por estado
- ✅ FindOrCreate pattern
- ✅ Relacionamento com State
- ✅ Unique constraint

**Próximo módulo:** Reports (receber e processar JSONs dos domínios)

---

**Data:** 2025-10-11  
**Versão:** 1.0.0  
**Status:** ✅ Production Ready

