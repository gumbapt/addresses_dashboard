# 📊 Guia de Métricas de Velocidade por Estado e Provedor

## 🎯 Objetivo

Este guia explica como as métricas de velocidade por estado e por provedor foram implementadas nos relatórios, permitindo análises detalhadas de performance.

---

## 🏗️ Estrutura de Dados

### **Localização no Relatório**

As métricas de velocidade estão armazenadas em `raw_data['speed_metrics']` de cada relatório:

```json
{
  "speed_metrics": {
    "overall": {
      "avg": 1502.89,
      "max": 219000,
      "min": 10
    },
    "by_state": {
      "CA": {
        "state_code": "CA",
        "avg_speed": 1803.47,
        "request_count": 32
      },
      "NY": {
        "state_code": "NY",
        "avg_speed": 1654.23,
        "request_count": 14
      }
    },
    "by_provider": {
      "Verizon": {
        "provider_name": "Verizon",
        "technology": "Mobile",
        "avg_speed": 1802.68,
        "request_count": 103
      },
      "HughesNet": {
        "provider_name": "HughesNet",
        "technology": "Satellite",
        "avg_speed": 901.73,
        "request_count": 103
      }
    }
  }
}
```

---

## 🔧 Como Funciona

### **1. Velocidades por Estado (`by_state`)**

Estados com foco geográfico (conforme perfil do domínio) recebem velocidades mais altas:

```php
// Estados focados: 1.2x a 1.5x da velocidade base
$isFocused = in_array($state, $profile['state_focus']);
$multiplier = $isFocused ? 1.2 + rand(0, 30) / 100 : 0.7 + rand(0, 50) / 100;
```

**Exemplo:**
- **smarterhome.ai** (foco em CA, NY, TX):
  - CA: 1,803 Mbps
  - NY: 1,654 Mbps
  - Outros estados: 700-1,200 Mbps

### **2. Velocidades por Provedor (`by_provider`)**

As velocidades variam de acordo com a tecnologia do provedor:

| Tecnologia | Multiplicador | Velocidade Típica |
|------------|--------------|-------------------|
| **Fiber** | 2.0x | 2,000-3,000 Mbps |
| **Cable** | 1.5x | 1,500-2,250 Mbps |
| **Mobile** | 1.2x | 1,200-1,800 Mbps |
| **Satellite** | 0.6x | 600-900 Mbps |
| **DSL** | 0.5x | 500-750 Mbps |
| **Unknown** | 1.0x | 1,000-1,500 Mbps |

**Exemplo de Provedor:**
```json
{
  "Verizon": {
    "provider_name": "Verizon",
    "technology": "Mobile",
    "avg_speed": 1802.68,
    "request_count": 103
  }
}
```

---

## 📊 Casos de Uso

### **1. Mapa de Calor de Velocidades por Estado**

```sql
-- Buscar velocidades médias por estado de um domínio
SELECT 
  r.domain_id,
  d.name as domain_name,
  state_code,
  AVG(JSON_EXTRACT(r.raw_data, '$.speed_metrics.by_state.{state_code}.avg_speed')) as avg_speed,
  SUM(JSON_EXTRACT(r.raw_data, '$.speed_metrics.by_state.{state_code}.request_count')) as total_requests
FROM reports r
JOIN domains d ON d.id = r.domain_id
WHERE r.domain_id = 1
GROUP BY r.domain_id, state_code
ORDER BY avg_speed DESC;
```

### **2. Ranking de Provedores por Velocidade**

```php
// PHP - Buscar velocidades por provedor de um domínio
$reports = Report::where('domain_id', $domainId)->get();
$providerSpeeds = [];

foreach ($reports as $report) {
    if (isset($report->raw_data['speed_metrics']['by_provider'])) {
        foreach ($report->raw_data['speed_metrics']['by_provider'] as $provider => $data) {
            if (!isset($providerSpeeds[$provider])) {
                $providerSpeeds[$provider] = [
                    'provider' => $provider,
                    'technology' => $data['technology'],
                    'speeds' => [],
                    'requests' => 0,
                ];
            }
            $providerSpeeds[$provider]['speeds'][] = $data['avg_speed'];
            $providerSpeeds[$provider]['requests'] += $data['request_count'];
        }
    }
}

// Calcular médias
foreach ($providerSpeeds as $provider => &$data) {
    $data['avg_speed'] = round(array_sum($data['speeds']) / count($data['speeds']), 2);
    unset($data['speeds']);
}

// Ordenar por velocidade
usort($providerSpeeds, fn($a, $b) => $b['avg_speed'] <=> $a['avg_speed']);
```

### **3. Comparação de Velocidades entre Domínios**

```php
// Comparar velocidade média por estado entre domínios
$domains = Domain::all();
$comparison = [];

foreach ($domains as $domain) {
    $reports = $domain->reports()->where('status', 'processed')->get();
    $stateSpeed = [];
    
    foreach ($reports as $report) {
        if (isset($report->raw_data['speed_metrics']['by_state'])) {
            foreach ($report->raw_data['speed_metrics']['by_state'] as $state => $data) {
                if (!isset($stateSpeed[$state])) {
                    $stateSpeed[$state] = [];
                }
                $stateSpeed[$state][] = $data['avg_speed'];
            }
        }
    }
    
    foreach ($stateSpeed as $state => &$speeds) {
        $speeds = round(array_sum($speeds) / count($speeds), 2);
    }
    
    $comparison[$domain->name] = $stateSpeed;
}
```

---

## 🎨 Visualizações Sugeridas

### **1. Mapa de Calor Geográfico**

Criar um mapa dos EUA com cores indicando velocidade média por estado:

```javascript
// Exemplo de dados para mapa de calor
{
  "CA": { "speed": 1803, "color": "#00ff00" }, // Verde (rápido)
  "NY": { "speed": 1654, "color": "#88ff00" },
  "TX": { "speed": 1421, "color": "#ffff00" }, // Amarelo (médio)
  "FL": { "speed": 892, "color": "#ff8800" },   // Laranja (lento)
  "AL": { "speed": 612, "color": "#ff0000" }    // Vermelho (muito lento)
}
```

### **2. Gráfico de Barras - Top Provedores por Velocidade**

```
HughesNet (Satellite)         ████████░░░░░░░░░░░░  901 Mbps
Verizon (Mobile)              ████████████████░░░░  1802 Mbps
Xfinity (Cable)               ██████████████████░░  2145 Mbps
Google Fiber (Fiber)          ████████████████████  3004 Mbps
```

### **3. Comparação Multi-Domínio**

Gráfico comparando velocidade média por tecnologia entre domínios:

```
                    Fiber    Cable    Mobile   Satellite   DSL
smarterhome.ai     █████     ████      ███       ██        █
broadbandcheck.io  ████      █████     ███       ██        █
zip.50g.io         ███       ███       ████      ██        █
ispfinder.net      ██        ██        █████     ███       ██
```

---

## 🔍 Queries Úteis

### **Verificar Relatório com Velocidades**

```bash
docker-compose exec app php artisan tinker --execute="
\$report = App\Models\Report::where('domain_id', 2)->first();
if (isset(\$report->raw_data['speed_metrics']['by_state'])) {
    foreach (array_slice(\$report->raw_data['speed_metrics']['by_state'], 0, 5, true) as \$state => \$data) {
        echo \$state . ': ' . \$data['avg_speed'] . ' Mbps' . PHP_EOL;
    }
}
"
```

### **Listar Top 10 Estados por Velocidade (Global)**

```php
$allReports = Report::where('status', 'processed')->get();
$globalStateSpeeds = [];

foreach ($allReports as $report) {
    if (isset($report->raw_data['speed_metrics']['by_state'])) {
        foreach ($report->raw_data['speed_metrics']['by_state'] as $state => $data) {
            if (!isset($globalStateSpeeds[$state])) {
                $globalStateSpeeds[$state] = [];
            }
            $globalStateSpeeds[$state][] = $data['avg_speed'];
        }
    }
}

$avgStateSpeeds = [];
foreach ($globalStateSpeeds as $state => $speeds) {
    $avgStateSpeeds[$state] = round(array_sum($speeds) / count($speeds), 2);
}

arsort($avgStateSpeeds);
$top10 = array_slice($avgStateSpeeds, 0, 10, true);
```

---

## 🎯 Próximos Passos

### **Implementar Endpoints**

1. **GET `/api/admin/reports/speeds/by-state`**
   - Retorna velocidades médias por estado (global ou por domínio)

2. **GET `/api/admin/reports/speeds/by-provider`**
   - Retorna velocidades médias por provedor (global ou por domínio)

3. **GET `/api/admin/reports/speeds/comparison`**
   - Compara velocidades entre domínios

### **Dashboard de Velocidades**

Criar seção no dashboard com:
- Mapa de calor de velocidades por estado
- Ranking de provedores por velocidade
- Comparação de tecnologias
- Trends de velocidade ao longo do tempo

---

## ✅ Status Atual

**Dados Disponíveis:**
- ✅ Velocidades por estado em todos os relatórios
- ✅ Velocidades por provedor em todos os relatórios
- ✅ Variação por perfil de domínio
- ✅ Variação por tecnologia

**Próximo:**
- ⬜ Endpoints de API
- ⬜ Visualizações no dashboard
- ⬜ Análises comparativas

---

## 📚 Referências

- [MULTI_DOMAIN_README.md](./MULTI_DOMAIN_README.md) - Guia multi-domínio
- [DOMAIN_PROFILES.md](./DOMAIN_PROFILES.md) - Perfis dos domínios
- [DASHBOARD_COMPLETO.md](./DASHBOARD_COMPLETO.md) - Dashboard atual

---

🎉 **Velocidades por estado e provedor implementadas com sucesso!**
