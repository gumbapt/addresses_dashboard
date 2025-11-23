# 📊 Análise: Gráfico de Providers/Domínios por Estado

## ✅ Resposta: SIM, é possível, mas com limitações

## 🔍 Estrutura Atual do Banco de Dados

### Tabelas Relevantes:

```
reports
├── id
├── domain_id (FK → domains)
├── report_date
└── raw_data (JSON)

report_providers
├── id
├── report_id (FK → reports)
├── provider_id (FK → providers)
├── total_count
├── success_rate
└── avg_speed

report_states
├── id
├── report_id (FK → reports)
├── state_id (FK → states)
├── request_count
├── success_rate
└── avg_speed
```

## ⚠️ Limitação Principal

**Não há uma tabela que cruze diretamente `provider + state`.**

As tabelas `report_providers` e `report_states` estão separadas e ambas se relacionam apenas através do `report_id`.

## 💡 Soluções Possíveis

### **Opção 1: Cruzamento através do `report_id` (Aproximação)**

**Como funciona:**
- Ambos `report_providers` e `report_states` têm `report_id`
- Podemos fazer JOIN através do `report_id` para cruzar os dados
- **Problema:** Isso cria um produto cartesiano se um report tiver múltiplos providers e múltiplos estados

**Exemplo de Query:**
```sql
SELECT 
    d.name as domain_name,
    p.name as provider_name,
    s.code as state_code,
    s.name as state_name,
    SUM(rp.total_count) as provider_requests,
    SUM(rs.request_count) as state_requests,
    COUNT(DISTINCT r.id) as report_count
FROM reports r
JOIN domains d ON r.domain_id = d.id
JOIN report_providers rp ON r.id = rp.report_id
JOIN providers p ON rp.provider_id = p.id
JOIN report_states rs ON r.id = rs.report_id
JOIN states s ON rs.state_id = s.id
WHERE r.status = 'processed'
  AND s.id = ? -- Filtrar por estado específico
GROUP BY d.id, d.name, p.id, p.name, s.id, s.code, s.name
ORDER BY provider_requests DESC;
```

**Limitações:**
- ❌ Não mostra a distribuição real de provider por estado
- ❌ Pode inflacionar números (produto cartesiano)
- ⚠️ Apenas uma aproximação, não dados precisos

### **Opção 2: Usar `raw_data` (Mais Preciso, mas Mais Lento)**

**Como funciona:**
- Os dados em `raw_data` podem ter informações mais detalhadas
- Verificar se existe `providers.by_state` no JSON
- Processar o JSON para extrair dados cruzados

**Estrutura esperada no `raw_data`:**
```json
{
  "providers": {
    "top_providers": [...],
    "by_state": {
      "CA": ["AT&T", "Spectrum"],
      "NY": ["Verizon", "Optimum"]
    }
  }
}
```

**Exemplo de Query:**
```sql
-- Buscar reports e processar JSON
SELECT 
    r.id,
    r.domain_id,
    JSON_EXTRACT(r.raw_data, '$.providers.by_state') as providers_by_state
FROM reports r
WHERE r.status = 'processed'
  AND JSON_EXTRACT(r.raw_data, '$.providers.by_state') IS NOT NULL;
```

**Limitações:**
- ⚠️ Performance ruim (parsing de JSON)
- ⚠️ Não está indexado
- ⚠️ Depende da estrutura do JSON estar correta
- ⚠️ Pode não existir em todos os reports

### **Opção 3: Criar Tabela de Cruzamento (Solução Ideal)**

**Nova tabela: `report_state_providers`**
```sql
CREATE TABLE report_state_providers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    report_id BIGINT NOT NULL,
    state_id BIGINT NOT NULL,
    provider_id BIGINT NOT NULL,
    request_count INT NOT NULL,
    success_rate DECIMAL(5,2) DEFAULT 0,
    avg_speed DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    INDEX idx_report_state_provider (report_id, state_id, provider_id),
    INDEX idx_state_provider (state_id, provider_id)
);
```

**Vantagens:**
- ✅ Dados precisos e normalizados
- ✅ Performance excelente (indexado)
- ✅ Queries simples e rápidas
- ✅ Suporta agregações complexas

**Desvantagens:**
- ❌ Requer migração de dados existentes
- ❌ Requer modificação no processamento de reports
- ❌ Precisa processar `raw_data` para popular a tabela

## 📊 Queries Úteis (Com Dados Atuais)

### 1. Ranking de Providers por Estado (Aproximação)

```sql
SELECT 
    s.code as state_code,
    s.name as state_name,
    p.name as provider_name,
    COUNT(DISTINCT r.id) as report_count,
    SUM(rp.total_count) as total_provider_requests,
    SUM(rs.request_count) as total_state_requests
FROM reports r
JOIN domains d ON r.domain_id = d.id
JOIN report_providers rp ON r.id = rp.report_id
JOIN providers p ON rp.provider_id = p.id
JOIN report_states rs ON r.id = rs.report_id
JOIN states s ON rs.state_id = s.id
WHERE r.status = 'processed'
  AND s.id = ? -- Filtrar por estado
GROUP BY s.id, s.code, s.name, p.id, p.name
ORDER BY total_provider_requests DESC;
```

### 2. Ranking de Domínios por Estado

```sql
SELECT 
    s.code as state_code,
    s.name as state_name,
    d.name as domain_name,
    SUM(rs.request_count) as total_requests,
    AVG(rs.success_rate) as avg_success_rate,
    AVG(rs.avg_speed) as avg_speed,
    COUNT(DISTINCT r.id) as report_count
FROM reports r
JOIN domains d ON r.domain_id = d.id
JOIN report_states rs ON r.id = rs.report_id
JOIN states s ON rs.state_id = s.id
WHERE r.status = 'processed'
  AND s.id = ? -- Filtrar por estado
GROUP BY s.id, s.code, s.name, d.id, d.name
ORDER BY total_requests DESC;
```

### 3. Verificar se `raw_data` tem `providers.by_state`

```sql
SELECT 
    r.id,
    r.domain_id,
    d.name as domain_name,
    JSON_EXTRACT(r.raw_data, '$.providers.by_state') as providers_by_state,
    JSON_EXTRACT(r.raw_data, '$.providers.by_state') IS NOT NULL as has_by_state
FROM reports r
JOIN domains d ON r.domain_id = d.id
WHERE r.status = 'processed'
LIMIT 10;
```

## 🎯 Recomendações

### Para Implementação Imediata:

1. **Usar Opção 1 (Cruzamento via `report_id`)**
   - Implementar endpoint que faz JOIN entre `report_providers` e `report_states`
   - Adicionar aviso na documentação que é uma aproximação
   - Funciona para casos de uso onde precisão absoluta não é crítica

2. **Verificar `raw_data`**
   - Analisar se os reports têm `providers.by_state`
   - Se sim, criar endpoint que processa JSON quando necessário
   - Usar cache para melhorar performance

### Para Solução Ideal (Longo Prazo):

1. **Criar tabela `report_state_providers`**
   - Adicionar migration
   - Modificar `ProcessReportJob` para popular a tabela
   - Criar script de migração para dados existentes

2. **Atualizar estrutura de dados**
   - Garantir que novos reports incluam dados cruzados
   - Validar estrutura do JSON no processamento

## 📝 Exemplo de Endpoint Sugerido

```php
GET /api/admin/reports/global/provider-ranking-by-state?state_id=5&domain_id=1

Response:
{
  "success": true,
  "data": [
    {
      "domain_id": 1,
      "domain_name": "example.com",
      "provider_id": 5,
      "provider_name": "Spectrum",
      "state_id": 5,
      "state_code": "CA",
      "state_name": "California",
      "total_requests": 1500,
      "avg_success_rate": 95.5,
      "avg_speed": 450.2,
      "report_count": 30,
      "note": "Aproximação baseada em cruzamento de report_id"
    }
  ],
  "filters": {
    "state_id": 5,
    "domain_id": 1
  }
}
```

## ✅ Conclusão

**É possível criar o gráfico**, mas com as seguintes considerações:

1. ✅ **Funcional:** Pode ser implementado usando JOIN através de `report_id`
2. ⚠️ **Precisão:** É uma aproximação, não dados 100% precisos
3. 💡 **Melhorias:** Verificar `raw_data` ou criar tabela de cruzamento para dados mais precisos
4. 🚀 **Performance:** Solução atual funciona, mas tabela dedicada seria mais eficiente

**Recomendação:** Implementar a Opção 1 primeiro (rápido) e depois avaliar se precisa da Opção 3 (ideal) baseado no uso real.

