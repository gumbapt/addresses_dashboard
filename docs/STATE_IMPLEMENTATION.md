# State (Estados dos EUA) - Implementação Completa

## ✅ Implementação Finalizada

Sistema completo de gerenciamento de **States** (Estados dos EUA) como dados de referência para normalização de dados geográficos.

---

## 🎯 Propósito

### Por que criar entidades de State?

1. **Normalização de Dados**
   - Evitar duplicação de "California" em milhares de registros
   - Usar FK (state_id) em vez de strings repetidas
   - Economia de espaço e performance

2. **Validação**
   - Garantir que códigos de estado são válidos (CA, NY, TX)
   - Rejeitar códigos inválidos (XX, ZZ)

3. **Agregações Eficientes**
   - Queries com JOIN são mais rápidas que string matching
   - Índices em FKs vs LIKE em strings

4. **Dados Consistentes**
   - Nome padronizado: "California" (não "california", "CA", "Calif.")
   - Timezone correto por estado
   - Coordenadas geográficas para mapas

---

## 📁 Arquivos Criados

### Domain Layer (2)
- ✅ `app/Domain/Entities/State.php`
- ✅ `app/Domain/Repositories/StateRepositoryInterface.php`

### Application Layer (3)
- ✅ `app/Application/DTOs/Geographic/StateDto.php`
- ✅ `app/Application/UseCases/Geographic/GetAllStatesUseCase.php`
- ✅ `app/Application/UseCases/Geographic/GetStateByCodeUseCase.php`

### Infrastructure Layer (1)
- ✅ `app/Infrastructure/Repositories/StateRepository.php`

### Presentation Layer (1)
- ✅ `app/Http/Controllers/Api/Admin/StateController.php`

### Database (2)
- ✅ `database/migrations/2025_10_11_200000_create_states_table.php`
- ✅ `database/seeders/StateSeeder.php` (51 estados: 50 + DC)

### Configuration (2)
- ✅ `app/Models/State.php`
- ✅ `app/Providers/DomainServiceProvider.php` (updated)
- ✅ `routes/api.php` (updated)

---

## 📊 Estrutura de Dados

### State Entity

```php
State {
    +id: int
    +code: string (2 chars) - CA, NY, TX
    +name: string - California, New York, Texas
    +timezone: string - America/Los_Angeles
    +latitude: float - 36.116203
    +longitude: float - -119.681564
    +isActive: bool - true
}
```

### Exemplo de Estado

```json
{
  "id": 5,
  "code": "CA",
  "name": "California",
  "timezone": "America/Los_Angeles",
  "latitude": 36.116203,
  "longitude": -119.681564,
  "is_active": true
}
```

---

## 🔌 API Endpoints

### **GET /api/admin/states**
Lista estados com paginação.

**Auth:** Admin token  
**Permissions:** N/A (dados de referência públicos para admins)

**Query Parameters:**
- `page` (int, default: 1)
- `per_page` (int, default: 50, max: 100)
- `search` (string) - Busca por name ou code
- `is_active` (boolean) - Filtrar por status

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "AL",
      "name": "Alabama",
      "timezone": "America/Chicago",
      "latitude": 32.806671,
      "longitude": -86.791130,
      "is_active": true
    }
  ],
  "pagination": {
    "total": 51,
    "per_page": 50,
    "current_page": 1,
    "last_page": 2
  }
}
```

### **GET /api/admin/states/all**
Retorna todos os estados ativos (sem paginação).

**Uso:** Dropdowns, selects, autocompletes

**Response:**
```json
{
  "success": true,
  "data": [
    {"id": 1, "code": "AL", "name": "Alabama", ...},
    {"id": 5, "code": "CA", "name": "California", ...},
    ...
  ]
}
```

### **GET /api/admin/states/{code}**
Busca estado por código.

**Params:** `code` - CA, NY, TX (case-insensitive)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "code": "CA",
    "name": "California",
    "timezone": "America/Los_Angeles",
    ...
  }
}
```

---

## 🗺️ Estados Incluídos

Total: **51 entidades** (50 estados + District of Columbia)

### Lista Completa

| Code | Name | Timezone |
|------|------|----------|
| AL | Alabama | America/Chicago |
| AK | Alaska | America/Anchorage |
| AZ | Arizona | America/Phoenix |
| AR | Arkansas | America/Chicago |
| CA | California | America/Los_Angeles |
| CO | Colorado | America/Denver |
| CT | Connecticut | America/New_York |
| DE | Delaware | America/New_York |
| FL | Florida | America/New_York |
| GA | Georgia | America/New_York |
| HI | Hawaii | Pacific/Honolulu |
| ID | Idaho | America/Boise |
| IL | Illinois | America/Chicago |
| IN | Indiana | America/Indiana/Indianapolis |
| IA | Iowa | America/Chicago |
| KS | Kansas | America/Chicago |
| KY | Kentucky | America/New_York |
| LA | Louisiana | America/Chicago |
| ME | Maine | America/New_York |
| MD | Maryland | America/New_York |
| MA | Massachusetts | America/New_York |
| MI | Michigan | America/Detroit |
| MN | Minnesota | America/Chicago |
| MS | Mississippi | America/Chicago |
| MO | Missouri | America/Chicago |
| MT | Montana | America/Denver |
| NE | Nebraska | America/Chicago |
| NV | Nevada | America/Los_Angeles |
| NH | New Hampshire | America/New_York |
| NJ | New Jersey | America/New_York |
| NM | New Mexico | America/Denver |
| NY | New York | America/New_York |
| NC | North Carolina | America/New_York |
| ND | North Dakota | America/Chicago |
| OH | Ohio | America/New_York |
| OK | Oklahoma | America/Chicago |
| OR | Oregon | America/Los_Angeles |
| PA | Pennsylvania | America/New_York |
| RI | Rhode Island | America/New_York |
| SC | South Carolina | America/New_York |
| SD | South Dakota | America/Chicago |
| TN | Tennessee | America/Chicago |
| TX | Texas | America/Chicago |
| UT | Utah | America/Denver |
| VT | Vermont | America/New_York |
| VA | Virginia | America/New_York |
| WA | Washington | America/Los_Angeles |
| WV | West Virginia | America/New_York |
| WI | Wisconsin | America/Chicago |
| WY | Wyoming | America/Denver |
| DC | District of Columbia | America/New_York |

---

## 🚀 Como Usar

### 1. Executar Migration

```bash
php artisan migrate
```

### 2. Seed States

```bash
php artisan db:seed --class=StateSeeder
```

**Output:**
```
✅ Created 51 US states successfully!
```

### 3. Testar API

**Listar todos os estados (sem paginação):**
```bash
GET /api/admin/states/all
Authorization: Bearer {admin_token}
```

**Buscar California:**
```bash
GET /api/admin/states/CA
Authorization: Bearer {admin_token}
```

**Buscar estados com paginação:**
```bash
GET /api/admin/states?per_page=10&search=New
Authorization: Bearer {admin_token}
```

---

## 💡 Uso nos Reports

### Quando processar relatórios, use States como referência:

```php
class ProcessReportJob
{
    public function processGeographic($report, $geographicData)
    {
        foreach ($geographicData['states'] as $stateData) {
            // Buscar estado pela sigla
            $state = $this->stateRepository->findByCode($stateData['code']);
            
            if ($state) {
                ReportGeographic::create([
                    'report_id' => $report->id,
                    'state_id' => $state->getId(), // ✅ FK
                    'state_code' => $stateData['code'], // String para compatibilidade
                    'state_name' => $state->getName(), // Nome normalizado
                    'request_count' => $stateData['request_count'],
                    'success_rate' => $stateData['success_rate'],
                    'avg_speed' => $stateData['avg_speed']
                ]);
            }
        }
    }
}
```

### Benefícios:

1. **Normalização:**
   ```sql
   -- ✅ BOM: FK
   SELECT states.name, SUM(rg.request_count)
   FROM report_geographic rg
   JOIN states ON rg.state_id = states.id
   GROUP BY states.id
   
   -- ❌ RUIM: String matching
   SELECT rg.state_name, SUM(rg.request_count)
   FROM report_geographic rg
   GROUP BY rg.state_name  -- duplicatas possíveis
   ```

2. **Validação:**
   ```php
   // ✅ Validar se estado existe
   $state = $stateRepository->findByCode('XX'); // null
   if (!$state) {
       Log::warning("Invalid state code in report: XX");
   }
   ```

3. **Timezone Correto:**
   ```php
   // ✅ Usar timezone do estado
   $state = $stateRepository->findByCode('CA');
   $localTime = Carbon::now($state->getTimezone());
   ```

---

## 🎨 Use Cases no Frontend

### Dropdown de Estados

```javascript
// Carregar lista de estados
const response = await fetch('/api/admin/states/all', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const { data: states } = await response.json();

// Renderizar select
<select name="state">
  {states.map(state => (
    <option key={state.id} value={state.code}>
      {state.name}
    </option>
  ))}
</select>
```

### Autocomplete de Estados

```javascript
const searchStates = async (query) => {
  const response = await fetch(`/api/admin/states?search=${query}&per_page=10`);
  return response.json();
};
```

### Mapa dos EUA

```javascript
// Usar coordenadas para plotar no mapa
states.forEach(state => {
  map.addMarker({
    lat: state.latitude,
    lng: state.longitude,
    title: state.name
  });
});
```

---

## 🔮 Próximos Passos

### Fase 2: Cities (Cidades)

```sql
CREATE TABLE cities (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    state_id BIGINT REFERENCES states(id),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    created_at TIMESTAMP,
    
    INDEX idx_state_id (state_id),
    INDEX idx_name (name)
);
```

**Estratégia:** Criar sob demanda quando aparecer em relatórios

### Fase 3: ZIP Codes (CEPs)

```sql
CREATE TABLE zip_codes (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    city_id BIGINT REFERENCES cities(id),
    state_id BIGINT REFERENCES states(id),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    created_at TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_state_id (state_id)
);
```

**Estratégia:** ~42,000 ZIP codes - popular sob demanda ou via dataset público

---

## ✅ Checklist

- ✅ Migration criada e executável
- ✅ Model State com HasFactory
- ✅ Entity State imutável
- ✅ DTO StateDto com toArray()
- ✅ Repository interface definida
- ✅ Repository implementado
- ✅ 2 Use Cases criados
- ✅ Controller com 3 endpoints
- ✅ 3 rotas registradas
- ✅ Binding no ServiceProvider
- ✅ Seeder com 51 estados (50 + DC)
- ✅ Timezones corretos
- ✅ Coordenadas geográficas
- ✅ Documentação completa

---

## 📚 Referências

- **US States Data:** [Oficial](https://www.census.gov/geo/reference/state-area.html)
- **Timezones:** [IANA Time Zone Database](https://www.iana.org/time-zones)
- **Coordinates:** Centro geográfico de cada estado

---

## 🎉 Status

✅ **Pronto para uso!**

**Total de arquivos:** 13 criados/modificados  
**Estados incluídos:** 51 (50 states + DC)  
**Endpoints:** 3 rotas

**Próximo módulo:** Cities (criar sob demanda nos reports)

---

**Data:** 2025-10-11  
**Versão:** 1.0.0  
**Status:** ✅ Production Ready

