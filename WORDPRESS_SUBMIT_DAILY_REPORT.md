# 📤 WordPress - Enviar Daily Report para o Dashboard

## 🎯 Endpoint

```
POST /api/reports/submit-daily
```

**Autenticação:** API Key do domínio  
**Content-Type:** application/json

---

## 🔑 Como Autenticar

Use o **API Key** do seu domínio no header `X-API-Key`:

```php
$api_key = get_option('dashboard_api_key'); // Sua API Key
```

---

## 📡 Request

### **Headers:**
```
Content-Type: application/json
X-API-Key: sua_api_key_aqui
Accept: application/json
```

### **Body (JSON):**
```json
{
  "report_date": "2025-11-10",
  "data": {
    "summary": {
      "total_requests": 150,
      "successful_requests": 120,
      "failed_requests": 30,
      "success_rate": 80.0,
      "providers_found": 45,
      "unique_zip_codes": 25,
      "unique_cities": 20,
      "unique_states": 5
    },
    "providers": [
      {
        "provider_name": "Verizon",
        "total_requests": 50,
        "successful_requests": 45,
        "failed_requests": 5,
        "success_rate": 90.0,
        "technologies": [
          {
            "technology": "Fiber",
            "count": 30,
            "percentage": 60.0
          },
          {
            "technology": "Cable",
            "count": 20,
            "percentage": 40.0
          }
        ]
      }
    ],
    "states": [
      {
        "state_code": "CA",
        "state_name": "California",
        "total_requests": 80,
        "successful_requests": 70,
        "failed_requests": 10,
        "success_rate": 87.5,
        "providers_found": 25,
        "unique_zip_codes": 15
      }
    ],
    "cities": [
      {
        "city_name": "Los Angeles",
        "state_code": "CA",
        "total_requests": 40,
        "successful_requests": 35,
        "failed_requests": 5,
        "success_rate": 87.5,
        "providers_found": 15
      }
    ],
    "zip_codes": [
      {
        "zip_code": "90001",
        "city_name": "Los Angeles",
        "state_code": "CA",
        "total_requests": 10,
        "successful_requests": 9,
        "failed_requests": 1,
        "success_rate": 90.0,
        "providers_found": 5
      }
    ]
  },
  "source": {
    "site_id": "wp-zip-daily-test",
    "site_name": "Zip Daily Test",
    "site_url": "http://zip.50g.io",
    "plugin_version": "1.0.0",
    "wordpress_version": "6.8.3"
  }
}
```

---

## ✅ Response (Sucesso - 201)

```json
{
  "success": true,
  "message": "Daily report submitted successfully",
  "data": {
    "report_id": 123,
    "domain_id": 1,
    "report_date": "2025-11-10",
    "status": "pending",
    "submitted_at": "2025-11-10T10:30:00Z"
  }
}
```

---

## ❌ Responses (Erro)

### **401 - API Key Inválida**
```json
{
  "success": false,
  "message": "Invalid or missing API key"
}
```

### **400 - Data Inválida**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "report_date": ["The report date field is required."],
    "data.summary": ["The summary field is required."]
  }
}
```

### **409 - Report Já Existe**
```json
{
  "success": false,
  "message": "Report for this date already exists",
  "existing_report_id": 122
}
```

---

## 🔧 Exemplo WordPress Plugin

```php
<?php
/**
 * Plugin Name: ISP Report Submitter
 * Description: Envia relatórios diários para o Dashboard
 * Version: 1.0.0
 */

class ISPReportSubmitter {
    
    private $api_url = 'http://seu-servidor.com/api/reports/submit-daily';
    private $api_key;
    
    public function __construct() {
        $this->api_key = get_option('dashboard_api_key');
        
        // Hook para enviar relatório diariamente
        add_action('wp_isp_daily_report', [$this, 'send_daily_report']);
    }
    
    /**
     * Enviar relatório diário
     */
    public function send_daily_report() {
        $report_data = $this->collect_daily_data();
        
        if (!$report_data) {
            error_log('No data to send');
            return;
        }
        
        $response = $this->send_to_dashboard($report_data);
        
        if ($response['success']) {
            update_option('last_report_sent', current_time('mysql'));
            error_log('Report sent successfully: ID ' . $response['data']['report_id']);
        } else {
            error_log('Failed to send report: ' . $response['message']);
        }
    }
    
    /**
     * Enviar dados para o Dashboard
     */
    private function send_to_dashboard($data) {
        $response = wp_remote_post($this->api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
                'Accept' => 'application/json',
            ],
            'body' => json_encode([
                'report_date' => date('Y-m-d'),
                'data' => $data,
                'source' => [
                    'site_id' => get_option('dashboard_site_id'),
                    'site_name' => get_bloginfo('name'),
                    'site_url' => get_site_url(),
                    'plugin_version' => '1.0.0',
                    'wordpress_version' => get_bloginfo('version'),
                ],
            ]),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        $result = json_decode($body, true);
        
        if ($status_code === 201 && isset($result['success']) && $result['success']) {
            return $result;
        }
        
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Unknown error',
            'status_code' => $status_code,
        ];
    }
    
    /**
     * Coletar dados do dia
     */
    private function collect_daily_data() {
        global $wpdb;
        
        // Aqui você coleta os dados do seu sistema
        // Este é apenas um exemplo
        
        return [
            'summary' => [
                'total_requests' => 150,
                'successful_requests' => 120,
                'failed_requests' => 30,
                'success_rate' => 80.0,
                'providers_found' => 45,
                'unique_zip_codes' => 25,
                'unique_cities' => 20,
                'unique_states' => 5,
            ],
            'providers' => [
                // Seus dados de providers
            ],
            'states' => [
                // Seus dados de estados
            ],
            'cities' => [
                // Seus dados de cidades
            ],
            'zip_codes' => [
                // Seus dados de zip codes
            ],
        ];
    }
}

// Inicializar plugin
new ISPReportSubmitter();

// Agendar envio diário (1x por dia às 23:00)
if (!wp_next_scheduled('wp_isp_daily_report')) {
    wp_schedule_event(strtotime('23:00:00'), 'daily', 'wp_isp_daily_report');
}
```

---

## 🧪 Testar Manualmente

### **Via cURL:**
```bash
curl -X POST http://localhost:8007/api/reports/submit-daily \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sua_api_key_aqui" \
  -d '{
    "report_date": "2025-11-10",
    "data": {
      "summary": {
        "total_requests": 100,
        "successful_requests": 80,
        "failed_requests": 20,
        "success_rate": 80.0,
        "providers_found": 30,
        "unique_zip_codes": 15,
        "unique_cities": 10,
        "unique_states": 3
      },
      "providers": [],
      "states": [],
      "cities": [],
      "zip_codes": []
    },
    "source": {
      "site_id": "test-site",
      "site_name": "Test Site",
      "site_url": "https://test.com",
      "plugin_version": "1.0.0",
      "wordpress_version": "6.8.3"
    }
  }'
```

---

## 📝 Campos Obrigatórios

### **Root Level:**
- `report_date` - Data do relatório (YYYY-MM-DD)
- `data` - Objeto com os dados
- `source` - Informações do site WordPress

### **data.summary:**
- `total_requests` - Total de requisições
- `successful_requests` - Requisições bem-sucedidas
- `failed_requests` - Requisições com falha
- `success_rate` - Taxa de sucesso (%)
- `providers_found` - Providers encontrados
- `unique_zip_codes` - ZIP codes únicos
- `unique_cities` - Cidades únicas
- `unique_states` - Estados únicos

### **data.providers, states, cities, zip_codes:**
Arrays de objetos com as métricas detalhadas (podem estar vazios)

---

## 🔍 Obter API Key

### **Pelo Dashboard Admin:**
```
1. Login no admin: http://localhost:8007/api/admin/login
2. Listar domínios: GET /api/admin/domains
3. Copiar o "api_key" do seu domínio
```

### **Via Tinker:**
```bash
docker-compose exec app php artisan tinker --execute="
\$domain = App\Models\Domain::where('name', 'zip.50g.io')->first();
echo 'API Key: ' . \$domain->api_key . PHP_EOL;
"
```

---

## ⚙️ Configuração no WordPress

### **1. Salvar API Key:**
```php
// No admin do plugin
update_option('dashboard_api_key', 'sua_api_key_aqui');
update_option('dashboard_site_id', 'wp-zip-daily-test');
update_option('dashboard_api_url', 'http://seu-servidor.com/api/reports/submit-daily');
```

### **2. Testar Conexão:**
```php
function test_dashboard_connection() {
    $response = wp_remote_get('http://seu-servidor.com/api/health', [
        'headers' => [
            'X-API-Key' => get_option('dashboard_api_key'),
        ],
    ]);
    
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message();
    }
    
    return wp_remote_retrieve_response_code($response) === 200 
        ? 'Connected!' 
        : 'Failed to connect';
}
```

---

## 📅 Agendar Envios

```php
// Agendar envio diário às 23:00
if (!wp_next_scheduled('wp_isp_daily_report')) {
    $timestamp = strtotime('tomorrow 23:00:00');
    wp_schedule_event($timestamp, 'daily', 'wp_isp_daily_report');
}

// Hook para executar
add_action('wp_isp_daily_report', function() {
    $submitter = new ISPReportSubmitter();
    $submitter->send_daily_report();
});
```

---

## 🐛 Debug

### **Verificar se o cron está agendado:**
```php
$scheduled = wp_next_scheduled('wp_isp_daily_report');
if ($scheduled) {
    echo 'Next run: ' . date('Y-m-d H:i:s', $scheduled);
} else {
    echo 'Not scheduled';
}
```

### **Testar envio manualmente:**
```php
// No WordPress admin, criar página de teste:
add_action('admin_menu', function() {
    add_menu_page(
        'Test Report', 
        'Test Report', 
        'manage_options', 
        'test-report', 
        function() {
            if (isset($_POST['send_test'])) {
                $submitter = new ISPReportSubmitter();
                $result = $submitter->send_daily_report();
                echo '<pre>' . print_r($result, true) . '</pre>';
            }
            
            echo '<form method="post">';
            echo '<button name="send_test" class="button button-primary">Send Test Report</button>';
            echo '</form>';
        }
    );
});
```

---

## ✅ Checklist de Configuração

- [ ] Instalar plugin no WordPress
- [ ] Configurar API Key (`dashboard_api_key`)
- [ ] Configurar Site ID (`dashboard_site_id`)
- [ ] Configurar URL da API (`dashboard_api_url`)
- [ ] Agendar cron diário (`wp_isp_daily_report`)
- [ ] Testar envio manual
- [ ] Verificar logs de erro
- [ ] Confirmar recebimento no dashboard

---

## 📊 Exemplo Completo de Request

```bash
curl -X POST http://localhost:8007/api/reports/submit-daily \
  -H "Content-Type: application/json" \
  -H "X-API-Key: v8ZJ4Xu0kyMs3WOzov4VqA0TJstJWC9H" \
  -d @- << 'EOF'
{
  "report_date": "2025-11-10",
  "data": {
    "summary": {
      "total_requests": 114,
      "successful_requests": 96,
      "failed_requests": 18,
      "success_rate": 84.21,
      "providers_found": 38,
      "unique_zip_codes": 23,
      "unique_cities": 19,
      "unique_states": 5
    },
    "providers": [
      {
        "provider_name": "AT&T",
        "total_requests": 30,
        "successful_requests": 28,
        "failed_requests": 2,
        "success_rate": 93.33,
        "technologies": [
          {"technology": "Fiber", "count": 20, "percentage": 66.67},
          {"technology": "DSL", "count": 10, "percentage": 33.33}
        ]
      }
    ],
    "states": [
      {
        "state_code": "CA",
        "state_name": "California",
        "total_requests": 50,
        "successful_requests": 45,
        "failed_requests": 5,
        "success_rate": 90.0,
        "providers_found": 20,
        "unique_zip_codes": 12
      }
    ],
    "cities": [
      {
        "city_name": "Los Angeles",
        "state_code": "CA",
        "total_requests": 25,
        "successful_requests": 23,
        "failed_requests": 2,
        "success_rate": 92.0,
        "providers_found": 12
      }
    ],
    "zip_codes": [
      {
        "zip_code": "90001",
        "city_name": "Los Angeles",
        "state_code": "CA",
        "total_requests": 5,
        "successful_requests": 5,
        "failed_requests": 0,
        "success_rate": 100.0,
        "providers_found": 3
      }
    ]
  },
  "source": {
    "site_id": "wp-zip-daily-test",
    "site_name": "Zip 50G",
    "site_url": "http://zip.50g.io",
    "plugin_version": "1.0.0",
    "wordpress_version": "6.8.3"
  }
}
EOF
```

---

## 🎯 Resumo para o Plugin

**Endpoint:** `POST /api/reports/submit-daily`  
**Auth:** Header `X-API-Key: sua_api_key`  
**Body:** JSON com `report_date`, `data` e `source`  
**Frequência:** 1x por dia (sugestão: 23:00)  
**Response:** 201 = sucesso, 401 = auth erro, 400 = validação, 409 = duplicado

---

**Versão:** 1.0  
**Data:** Novembro 10, 2025  
**Status:** ✅ Endpoint pronto e testado

