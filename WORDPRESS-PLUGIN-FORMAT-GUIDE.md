# 📝 Guia de Formato para Plugin WordPress

## 🎯 Endpoint da API
```
POST https://dash3.50g.io/api/reports/submit
Authorization: Bearer {API_KEY}
Content-Type: application/json
```

---

## 🔑 API Key para zip.50g.io
```
5ysoVBU3WLIJSHqXSRA35x0dxZmRQ4qR
```

---

## 📋 Estrutura Completa do JSON

### 1️⃣ CAMPOS OBRIGATÓRIOS

```json
{
  "source": {
    "domain": "zip.50g.io",
    "site_id": "wp-prod-001",
    "site_name": "Zip Code Lookup"
  },
  "metadata": {
    "report_date": "2025-11-12",
    "report_period": {
      "start": "2025-11-12 00:00:00",
      "end": "2025-11-12 23:59:59"
    },
    "generated_at": "2025-11-12 23:59:59",
    "total_processing_time": 120,
    "data_version": "2.0.0"
  },
  "summary": {
    "total_requests": 100,
    "success_rate": 85.5,
    "failed_requests": 15,
    "avg_requests_per_hour": 4.17,
    "unique_providers": 45,
    "unique_states": 15,
    "unique_zip_codes": 75
  }
}
```

---

### 2️⃣ GEOGRAPHIC (Opcional - Use Array de Objetos)

⚠️ **IMPORTANTE**: Estados, cidades e ZIP codes devem ser **arrays de objetos**, não objetos chave-valor!

```json
{
  "geographic": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "request_count": 32,
        "success_rate": 90.5,
        "avg_speed": 1500.0
      },
      {
        "code": "NY",
        "name": "New York",
        "request_count": 14,
        "success_rate": 85.0,
        "avg_speed": 1200.0
      }
    ],
    "top_cities": [
      {
        "name": "New York",
        "request_count": 9,
        "zip_codes": ["10001", "10038"]
      },
      {
        "name": "Los Angeles",
        "request_count": 6,
        "zip_codes": ["90001", "90012"]
      }
    ],
    "top_zip_codes": [
      {
        "zip_code": "10600",
        "request_count": 8,
        "percentage": 7.02
      },
      {
        "zip_code": "10038",
        "request_count": 6,
        "percentage": 5.26
      }
    ]
  }
}
```

#### ❌ ERRADO (não faça isso):
```json
{
  "geographic": {
    "states": {
      "CA": 32,
      "NY": 14
    }
  }
}
```

---

### 3️⃣ PROVIDERS (Opcional - Use Array de Objetos)

```json
{
  "providers": {
    "top_providers": [
      {
        "name": "AT&T",
        "total_count": 86,
        "technology": "Fiber",
        "success_rate": 95.0,
        "avg_speed": 2000.0
      },
      {
        "name": "Spectrum",
        "total_count": 54,
        "technology": "Cable",
        "success_rate": 88.0,
        "avg_speed": 1500.0
      },
      {
        "name": "Verizon",
        "total_count": 42,
        "technology": "Fiber",
        "success_rate": 92.0,
        "avg_speed": 1800.0
      }
    ]
  }
}
```

#### Campos do provider:
- `name` (obrigatório) - Nome do provedor
- `total_count` (obrigatório) - Total de ocorrências
- `technology` (opcional) - Tipo de tecnologia (Fiber, Cable, DSL, etc)
- `success_rate` (opcional) - Taxa de sucesso (0-100)
- `avg_speed` (opcional) - Velocidade média em Mbps

#### ❌ ERRADO (não faça isso):
```json
{
  "providers": {
    "available": {
      "AT&T": 86,
      "Spectrum": 54
    }
  }
}
```

---

### 4️⃣ TECHNOLOGY_METRICS (Opcional - Objeto Chave-Valor ✅)

⚠️ **IMPORTANTE**: Este é o campo CORRETO para enviar distribuição de tecnologias!

✅ **Este campo aceita objeto chave-valor!**

```json
{
  "technology_metrics": {
    "distribution": {
      "Fiber": 560,
      "Cable": 450,
      "DSL": 320,
      "Fixed Wireless": 280,
      "Mobile Wireless": 1416,
      "Satellite": 150
    },
    "by_state": {
      "CA": {
        "Fiber": 200,
        "Cable": 150,
        "DSL": 100
      },
      "NY": {
        "Fiber": 180,
        "Cable": 120,
        "DSL": 80
      }
    },
    "by_provider": {
      "AT&T": {
        "Fiber": 300,
        "DSL": 50
      },
      "Spectrum": {
        "Cable": 250
      }
    }
  }
}
```

#### Tecnologias comuns:
- `Fiber` - Fibra ótica
- `Cable` - Cabo coaxial
- `DSL` - Linha telefônica
- `Fixed Wireless` - Wireless fixo
- `Mobile Wireless` - Celular/5G
- `Satellite` - Satélite

---

### 5️⃣ PERFORMANCE (Opcional - Objeto Chave-Valor ✅)

✅ **hourly_distribution aceita objeto chave-valor!**

```json
{
  "performance": {
    "hourly_distribution": {
      "0": 5,
      "1": 3,
      "2": 2,
      "8": 15,
      "12": 20,
      "14": 18,
      "18": 25,
      "23": 8
    },
    "avg_response_time": 0.5,
    "min_response_time": 0.1,
    "max_response_time": 2.5,
    "search_types": {
      "address": 50,
      "zipcode": 30,
      "coordinates": 20
    }
  }
}
```

---

### 6️⃣ SPEED_METRICS (Opcional)

```json
{
  "speed_metrics": {
    "overall": {
      "avg": 1502.89,
      "max": 219000,
      "min": 10,
      "median": 1000
    },
    "by_state": {
      "CA": {
        "avg": 1800,
        "max": 5000,
        "min": 50
      },
      "NY": {
        "avg": 1200,
        "max": 3000,
        "min": 25
      }
    },
    "by_provider": {
      "AT&T": {
        "avg": 2000,
        "max": 5000,
        "min": 100
      },
      "Spectrum": {
        "avg": 1500,
        "max": 3000,
        "min": 75
      }
    }
  }
}
```

---

### 7️⃣ EXCLUSION_METRICS (Opcional - Objeto Chave-Valor ✅)

✅ **by_provider e by_state aceitam objeto chave-valor!**

```json
{
  "exclusion_metrics": {
    "total_exclusions": 40,
    "exclusion_rate": 35.1,
    "by_state": {
      "CA": 15,
      "NY": 10,
      "TX": 15
    },
    "by_provider": {
      "GeoLinks": 22,
      "Viasat": 18,
      "HughesNet": 12
    }
  }
}
```

---

### 8️⃣ HEALTH (Opcional)

```json
{
  "health": {
    "status": "healthy",
    "uptime_percentage": 99.9,
    "avg_cpu_usage": 45.5,
    "avg_memory_usage": 2048.5,
    "disk_usage": 75.2,
    "last_cron_run": "2025-11-12 22:00:00"
  }
}
```

---

## 📊 RESUMO: Qual Formato Usar?

| Campo | Formato |
|-------|---------|
| `geographic.states` | **Array de objetos** `[{code, name, request_count}]` |
| `geographic.top_cities` | **Array de objetos** `[{name, request_count}]` |
| `geographic.top_zip_codes` | **Array de objetos** `[{zip_code, request_count}]` |
| `providers.top_providers` | **Array de objetos** `[{name, total_count}]` |
| `technology_metrics.distribution` | **Objeto chave-valor** `{"Fiber": 560}` ✅ |
| `technology_metrics.by_state` | **Objeto chave-valor** `{"CA": {...}}` ✅ |
| `performance.hourly_distribution` | **Objeto chave-valor** `{"12": 20}` ✅ |
| `exclusion_metrics.by_provider` | **Objeto chave-valor** `{"GeoLinks": 22}` ✅ |

---

## 🔧 Exemplo PHP para WordPress

### Converter Dados do WordPress para Formato da API:

```php
<?php
/**
 * Gerar relatório no formato esperado pela API
 */
function generate_api_report($wordpress_data) {
    // 1. Converter estados de array associativo para array de objetos
    $states = [];
    foreach ($wordpress_data['states'] as $code => $count) {
        $states[] = [
            'code' => $code,
            'name' => get_state_name($code),
            'request_count' => $count
        ];
    }
    
    // 2. Converter cidades
    $cities = [];
    foreach ($wordpress_data['cities'] as $name => $count) {
        $cities[] = [
            'name' => $name,
            'request_count' => $count
        ];
    }
    
    // 3. Converter ZIP codes
    $zip_codes = [];
    $total_requests = $wordpress_data['summary']['total_requests'];
    foreach ($wordpress_data['zip_codes'] as $zip => $count) {
        $zip_codes[] = [
            'zip_code' => $zip,
            'request_count' => $count,
            'percentage' => round(($count / $total_requests) * 100, 2)
        ];
    }
    
    // 4. Converter providers
    $providers = [];
    foreach ($wordpress_data['providers'] as $name => $count) {
        $providers[] = [
            'name' => $name,
            'total_count' => $count
        ];
    }
    
    // 5. Montar relatório final
    return [
        'source' => [
            'domain' => 'zip.50g.io',
            'site_id' => get_option('site_id'),
            'site_name' => get_bloginfo('name')
        ],
        'metadata' => [
            'report_date' => date('Y-m-d'),
            'report_period' => [
                'start' => date('Y-m-d 00:00:00'),
                'end' => date('Y-m-d 23:59:59')
            ],
            'generated_at' => date('Y-m-d H:i:s'),
            'total_processing_time' => $wordpress_data['processing_time'] ?? 0,
            'data_version' => '2.0.0'
        ],
        'summary' => [
            'total_requests' => $wordpress_data['summary']['total_requests'],
            'success_rate' => $wordpress_data['summary']['success_rate'],
            'failed_requests' => $wordpress_data['summary']['failed_requests'],
            'avg_requests_per_hour' => round($wordpress_data['summary']['total_requests'] / 24, 2),
            'unique_providers' => count($wordpress_data['providers']),
            'unique_states' => count($wordpress_data['states']),
            'unique_zip_codes' => count($wordpress_data['zip_codes'])
        ],
        'geographic' => [
            'states' => $states,
            'top_cities' => $cities,
            'top_zip_codes' => $zip_codes
        ],
        'providers' => [
            'top_providers' => $providers
        ],
        'technology_metrics' => [
            'distribution' => $wordpress_data['technologies'] // Mantém como está!
        ],
        'performance' => [
            'hourly_distribution' => $wordpress_data['hourly'] // Mantém como está!
        ],
        'exclusion_metrics' => [
            'by_provider' => $wordpress_data['excluded'] // Mantém como está!
        ]
    ];
}

/**
 * Helper para obter nome do estado
 */
function get_state_name($code) {
    $states = [
        'CA' => 'California',
        'NY' => 'New York',
        'TX' => 'Texas',
        // ... adicionar todos os estados
    ];
    return $states[$code] ?? $code;
}

/**
 * Enviar relatório para a API
 */
function send_report_to_api($report_data) {
    $api_key = '5ysoVBU3WLIJSHqXSRA35x0dxZmRQ4qR';
    $api_url = 'https://dash3.50g.io/api/reports/submit';
    
    $response = wp_remote_post($api_url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
            'Accept' => 'application/json'
        ],
        'body' => json_encode($report_data),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        error_log('API Error: ' . $response->get_error_message());
        return false;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code !== 200) {
        error_log('API Error ' . $status_code . ': ' . json_encode($body));
        return false;
    }
    
    return $body;
}
```

---

## 🧪 Testar Formato

### Validar JSON antes de enviar:
```bash
# No servidor
php /home/address3/addresses_dashboard/convert-report-format.php \
  /path/to/wordpress-json.json \
  /tmp/validated.json

# Testar
curl -s -X POST https://dash3.50g.io/api/reports/submit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 5ysoVBU3WLIJSHqXSRA35x0dxZmRQ4qR" \
  -d @/tmp/validated.json | jq .
```

---

## 🔍 Validação de Erros

### Erros comuns e soluções:

| Erro | Causa | Solução |
|------|-------|---------|
| "State code is required" | States em formato objeto | Usar array de objetos |
| "Provider name is required" | Providers em formato objeto | Usar array de objetos |
| "Report date must be in Y-m-d format" | Data em formato errado | Usar `YYYY-MM-DD` |
| "Domain mismatch" | API key errada ou domain incorreto | Verificar API key e domain |

---

## 📞 Suporte

Arquivos de referência no servidor:
- `/home/address3/addresses_dashboard/REPORT-API-FORMAT.md` - Guia completo
- `/home/address3/addresses_dashboard/REPORT-FORMAT-EXAMPLE.json` - Exemplo válido
- `/home/address3/addresses_dashboard/convert-report-format.php` - Script de conversão

---

**Última atualização**: 2025-11-12
**API Key**: `5ysoVBU3WLIJSHqXSRA35x0dxZmRQ4qR`
**Endpoint**: `https://dash3.50g.io/api/reports/submit`

