# 📊 Ranking de Estados por Provider - Análise

## ❌ Status Atual: NÃO POSSÍVEL com dados atuais

### **Por quê?**

Os dados estão armazenados em **tabelas separadas** sem cruzamento:

```
report_states
├── report_id
├── state_id
├── request_count      ← Agregado POR ESTADO (todos providers juntos)
└── success_rate

report_providers
├── report_id
├── provider_id
├── total_count        ← Agregado POR PROVIDER (todos estados juntos)
└── success_rate
```

**Não há:** `report_state_providers` (state_id + provider_id)

---

## 🔍 O Que Temos Atualmente

### **1. Dados por Provider (Sem Estado)**
```sql
SELECT 
    p.name as provider_name,
    SUM(rp.total_count) as total_requests
FROM report_providers rp
JOIN providers p ON rp.provider_id = p.id
GROUP BY p.id, p.name;
```
✅ **Funciona** - Ranking de providers globalmente

---

### **2. Dados por Estado (Sem Provider)**
```sql
SELECT 
    s.name as state_name,
    SUM(rs.request_count) as total_requests
FROM report_states rs
JOIN states s ON rs.state_id = s.id
GROUP BY s.id, s.name;
```
✅ **Funciona** - Ranking de estados globalmente

---

### **3. Dados por Provider E Estado**
```sql
SELECT 
    s.name as state_name,
    p.name as provider_name,
    SUM(...) as total_requests  ← NÃO EXISTE
FROM ??? 
```
❌ **NÃO FUNCIONA** - Não há tabela cruzando state + provider

---

## 💡 Soluções Possíveis

### **Opção A: Usar Dados RAW (Aproximação)**

**Problema:** Os dados em `report.raw_data` podem ter informações mais detalhadas, mas:
- Não estão normalizados
- Performance ruim (parsing de JSON)
- Não estão indexados

**Código de exemplo:**
```php
// Buscar nos raw_data de todos os reports
$reports = Report::where('status', 'processed')->get();

$stateProviderData = [];
foreach ($reports as $report) {
    $rawData = $report->raw_data;
    
    // Tentar extrair informações de geographic breakdown
    // MAS: dados não têm provider breakdown por estado
}
```

**Veredito:** ❌ Não viável - dados não existem no formato necessário

---

### **Opção B: Criar Nova Estrutura de Dados (Recomendado)**

**1. Criar Migration:**
```php
// Migration: create_report_state_providers_table.php
Schema::create('report_state_providers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('report_id')->constrained()->onDelete('cascade');
    $table->foreignId('state_id')->constrained()->onDelete('cascade');
    $table->foreignId('provider_id')->constrained()->onDelete('cascade');
    $table->string('technology')->nullable();
    $table->integer('request_count')->default(0);
    $table->decimal('success_rate', 5, 2)->default(0);
    $table->decimal('avg_speed', 8, 2)->default(0);
    $table->timestamps();
    
    $table->index(['report_id', 'state_id', 'provider_id']);
});
```

**2. Atualizar WordPress Plugin para enviar:**
```json
{
  "data": {
    "state_provider_breakdown": [
      {
        "state_code": "CA",
        "provider_name": "Spectrum",
        "technology": "Cable",
        "request_count": 50,
        "success_rate": 88.5,
        "avg_speed": 1200
      },
      {
        "state_code": "CA",
        "provider_name": "AT&T",
        "technology": "Fiber",
        "request_count": 30,
        "success_rate": 92.0,
        "avg_speed": 980
      }
    ]
  }
}
```

**3. Atualizar ReportProcessor:**
```php
private function processStateProviders(int $reportId, array $data): void
{
    foreach ($data as $item) {
        $state = $this->stateRepository->findByCode($item['state_code']);
        $provider = $this->providerRepository->findOrCreate($item['provider_name']);
        
        ReportStateProvider::create([
            'report_id' => $reportId,
            'state_id' => $state->getId(),
            'provider_id' => $provider->getId(),
            'technology' => $item['technology'],
            'request_count' => $item['request_count'],
            'success_rate' => $item['success_rate'],
            'avg_speed' => $item['avg_speed'],
        ]);
    }
}
```

**4. Criar Use Case:**
```php
// GetStateProviderRankingUseCase.php
public function execute(int $stateId, ?int $providerId = null): array
{
    $query = DB::table('report_state_providers as rsp')
        ->join('providers as p', 'rsp.provider_id', '=', 'p.id')
        ->join('states as s', 'rsp.state_id', '=', 's.id')
        ->where('s.id', $stateId);
    
    if ($providerId) {
        $query->where('rsp.provider_id', $providerId);
    }
    
    return $query
        ->select(
            'p.name as provider_name',
            DB::raw('SUM(rsp.request_count) as total_requests'),
            DB::raw('AVG(rsp.success_rate) as avg_success_rate')
        )
        ->groupBy('p.id', 'p.name')
        ->orderBy('total_requests', 'desc')
        ->get();
}
```

**Veredito:** ✅ Viável - **Requer mudanças no WordPress plugin**

---

## 🎯 Recomendação

### **Curto Prazo (Hoje):**
❌ **Não implementar** - Dados não existem

**Alternativa:** Oferecer apenas:
- Ranking de providers (sem breakdown por estado) ✅ **JÁ TEM**
- Ranking de estados (sem breakdown por provider) ✅ Pode criar

---

### **Médio Prazo (Próxima Sprint):**
✅ **Implementar estrutura completa:**

**Passo 1:** Atualizar WordPress plugin para enviar `state_provider_breakdown`
**Passo 2:** Criar migration `report_state_providers`
**Passo 3:** Atualizar `ReportProcessor`
**Passo 4:** Criar `GetStateProviderRankingUseCase`
**Passo 5:** Criar endpoint `/api/admin/reports/state-provider-ranking`

**Tempo estimado:** 4-6 horas

---

## 📊 Rankings Disponíveis HOJE

### **✅ Ranking de Providers (Global)**
```
GET /api/admin/reports/global/provider-ranking?provider_id=5
```
**Retorna:** Top domínios que mais usam aquele provider

---

### **✅ Ranking de Domínios (Global)**
```
GET /api/admin/reports/global/domain-ranking
```
**Retorna:** Top domínios por performance geral

---

### **✅ Ranking de Estados (Pode Criar)**
```
GET /api/admin/reports/global/state-ranking
```
**Retorna:** Top estados por volume de requests (SEM detalhamento de provider)

**Implementação:** ~1 hora (similar ao provider-ranking)

---

## 🚀 Quer que eu implemente?

### **Opção 1: Ranking de Estados (Sem Provider) - Rápido**
- Tempo: 1 hora
- Dados: Já existem
- Exemplo: "Top 10 estados com mais requests"

### **Opção 2: Ranking Estado + Provider - Completo**
- Tempo: 4-6 horas
- Requer: Mudanças no WordPress plugin
- Exemplo: "Top providers em California"

---

## 📋 Resumo

**Pergunta:** "Ranking de estados/regiões por provider é possível?"

**Resposta:** 
- ❌ **NÃO** com dados atuais (sem cruzamento state + provider)
- ✅ **SIM** se atualizar WordPress plugin + backend

**Alternativa hoje:**
- Ranking de providers (sem estado) ✅
- Ranking de estados (sem provider) ⏳ Posso criar em 1h

**Quer que eu implemente o ranking de estados?** 🤔

