# 📋 Guia de Implementação WordPress - Dados de Velocidade e Tecnologia

## 🎯 **Objetivo**

Este guia mostra exatamente o que o WordPress precisa adicionar nos reports para que **todos os gráficos funcionem corretamente**, especialmente o gráfico de velocidade por estado.

---

## ❌ **Problema Atual**

### **O Que Está Faltando:**

| Campo | Status Atual | Impacto |
|-------|--------------|---------|
| `geographic.states[].avg_speed` | ❌ **FALTA** | 🔴 Gráfico de velocidade não aparece |
| `summary.avg_speed_mbps` | ❌ Falta | 🟡 Dados agregados sem velocidade |
| `providers.top_providers[].technology` | ❌ Falta | 🟡 Consistência e fallback |

### **O Que Já Está Correto:**

| Campo | Status | Observação |
|-------|--------|------------|
| `technology_metrics.distribution` | ✅ **Já envia** | Gráfico de tecnologia funciona |

---

## ✅ **Solução: 3 Campos para Adicionar**

### **1. `geographic.states[].avg_speed` (🔴 CRÍTICO)**

**Onde:** Em cada item do array `geographic.states`

**Formato Atual:**
```json
{
  "geographic": {
    "states": [
      {
        "code": "TX",
        "name": "Texas",
        "request_count": 13
      }
    ]
  }
}
```

**Formato Corrigido:**
```json
{
  "geographic": {
    "states": [
      {
        "code": "TX",
        "name": "Texas",
        "request_count": 13,
        "avg_speed": 1100.0  // ✅ ADICIONAR ESTE CAMPO
      }
    ]
  }
}
```

**Código PHP:**
```php
foreach ($statesData as $stateCode => $stateInfo) {
    $states[] = [
        'code' => $stateCode,
        'name' => $stateInfo['name'],
        'request_count' => $stateInfo['count'],
        'avg_speed' => $stateInfo['avg_speed'] ?? 0,  // ✅ ADICIONAR
    ];
}
```

**Impacto:** Gráfico de velocidade funciona imediatamente  
**Esforço:** 5 minutos

---

### **2. `summary.avg_speed_mbps` (🟡 IMPORTANTE)**

**Onde:** No objeto `summary`

**Formato Atual:**
```json
{
  "summary": {
    "total_requests": 70,
    "success_rate": 87.14,
    "unique_providers": 83
  }
}
```

**Formato Corrigido:**
```json
{
  "summary": {
    "total_requests": 70,
    "success_rate": 87.14,
    "unique_providers": 83,
    "avg_speed_mbps": 1502.89  // ✅ ADICIONAR ESTE CAMPO
  }
}
```

**Código PHP:**
```php
$summary = [
    'total_requests' => $totalRequests,
    'success_rate' => $successRate,
    'avg_speed_mbps' => $avgSpeed,  // ✅ ADICIONAR
];
```

**Impacto:** Dados agregados têm velocidade média  
**Esforço:** 2 minutos

---

### **3. `providers.top_providers[].technology` (🟡 IMPORTANTE)**

**Onde:** Em cada item do array `providers.top_providers`

**Formato Atual:**
```json
{
  "providers": {
    "top_providers": [
      {
        "name": "HughesNet",
        "total_count": 61
      }
    ]
  }
}
```

**Formato Corrigido:**
```json
{
  "providers": {
    "top_providers": [
      {
        "name": "HughesNet",
        "total_count": 61,
        "technology": "Satellite"  // ✅ ADICIONAR
      }
    ]
  }
}
```

**Código PHP:**
```php
foreach ($providersData as $providerName => $providerInfo) {
    $providers[] = [
        'name' => $providerName,
        'total_count' => $providerInfo['count'],
        'technology' => $providerInfo['technology'] ?? 'Unknown',  // ✅ ADICIONAR
    ];
}
```

**Impacto:** Consistência e fallback para tecnologia  
**Esforço:** 5 minutos

---

## 📊 **Status: Tecnologia**

### **✅ O Que Já Está Funcionando:**

O WordPress **já está enviando** `technology_metrics.distribution` corretamente:

```json
{
  "technology_metrics": {
    "distribution": {
      "Mobile": 882,
      "DSL": 301,
      "Fiber": 220,
      "Satellite": 177,
      "Cable": 169
    }
  }
}
```

**✅ Isso está correto!** O gráfico de tecnologia funciona perfeitamente.

**🟡 Melhoria Opcional:** Adicionar `technology` em `providers.top_providers[]` para consistência (não é crítico).

---

## 📋 **Checklist de Implementação**

### **🔴 CRÍTICO (Faz gráfico funcionar):**

- [ ] **Adicionar `avg_speed` em `geographic.states[]`**
  - Campo: `avg_speed` (float, em Mbps)
  - Exemplo: `1500.0`
  - **Impacto**: Gráfico de velocidade funciona
  - **Esforço**: 5 minutos

### **🟡 IMPORTANTE (Melhora dados):**

- [ ] **Adicionar `avg_speed_mbps` no `summary`**
  - Campo: `avg_speed_mbps` (float, em Mbps)
  - Exemplo: `1502.89`
  - **Impacto**: Dados agregados têm velocidade
  - **Esforço**: 2 minutos

- [ ] **Adicionar `technology` em `providers.top_providers[]`**
  - Campo: `technology` (string)
  - Exemplo: `"Fiber"`, `"Cable"`, `"DSL"`, `"Satellite"`, `"Mobile Wireless"`
  - **Impacto**: Consistência e fallback
  - **Esforço**: 5 minutos

### **✅ JÁ ESTÁ CORRETO:**

- [x] **Enviar `technology_metrics.distribution`**
  - Status: **Já está sendo enviado corretamente!**
  - Impacto: Gráfico de tecnologia funciona

---

## 📝 **Exemplo Completo**

Veja o arquivo `WORDPRESS-SPEED-EXAMPLE.json` para um exemplo completo de como deve ser o JSON final.

---

## 🧪 **Como Testar**

### **1. Enviar Report de Teste:**

```bash
curl -X POST https://dash3.50g.io/api/reports/submit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d @test-report.json
```

### **2. Verificar no Dashboard:**

1. Acesse `/domains/{id}/dashboard`
2. Verifique se o gráfico "Speed by State" aparece
3. Verifique se os estados têm velocidade > 0
4. Verifique se o gráfico "Technology Distribution" aparece

---

## 📊 **Resumo Final**

| Métrica | Status WordPress | Ação Necessária | Prioridade |
|---------|-----------------|-----------------|------------|
| **Technology Distribution** | ✅ Funciona | Nenhuma | ✅ OK |
| **Speed by State** | ❌ Não funciona | Adicionar `avg_speed` em estados | 🔴 CRÍTICO |
| **Providers Technology** | ⚠️ Parcial | Adicionar `technology` em providers | 🟡 IMPORTANTE |

---

**Prioridade**: 🔴 Adicionar `avg_speed` em `geographic.states[]` é **CRÍTICO**  
**Esforço Total**: ~10 minutos  
**Impacto**: Todos os gráficos funcionam corretamente

---

**Documentos Relacionados:**
- `WORDPRESS-SPEED-EXAMPLE.json` - Exemplo JSON completo
- `WORDPRESS-SPEED-DATA-GUIDE.md` - Guia detalhado completo
- `REPORT-SUBMIT-COMPLETE-GUIDE.md` - Guia geral de formato

