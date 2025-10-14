# 📊 Guia de Submissão de Relatórios

## 🎯 Visão Geral

Este guia explica como submeter relatórios de teste para a API, simulando o comportamento do serviço 50gig.

## 🚀 Métodos de Submissão

### **Método 1: Script Bash (Recomendado)**

O script `submit-test-report.sh` simula uma requisição externa real usando CURL.

#### **Uso Básico:**
```bash
# Submeter usando o domínio do banco de dados
./submit-test-report.sh
```

#### **Uso com Parâmetros:**
```bash
# Submeter especificando domínio e API key manualmente
./submit-test-report.sh zip.50g.io test_fcb4eaac3de8...
```

#### **Funcionalidades:**
- ✅ Lê automaticamente o domínio ativo do banco
- ✅ Faz requisição HTTP real via CURL
- ✅ Mostra resposta formatada com jq
- ✅ Exibe erros de validação de forma clara
- ✅ Retorna exit code apropriado

---

### **Método 2: Comando Artisan**

O comando `report:submit-test` executa a submissão de dentro do container PHP.

#### **Uso Básico:**
```bash
docker-compose exec app php artisan report:submit-test
```

#### **Criar Domínio Automaticamente:**
```bash
docker-compose exec app php artisan report:submit-test --create-domain
```

#### **Especificar Arquivo:**
```bash
docker-compose exec app php artisan report:submit-test --file=docs/newdata.json
```

#### **Especificar Domínio:**
```bash
docker-compose exec app php artisan report:submit-test --domain=zip.50g.io
```

#### **Usar URL Customizada:**
```bash
docker-compose exec app php artisan report:submit-test --url=http://nginx
```

---

## 📋 Pré-requisitos

### **1. Domínio Ativo no Banco de Dados**

Você precisa ter um domínio ativo com API key. Para criar:

```bash
# Via Artisan
docker-compose exec app php artisan tinker

# No tinker:
$domain = App\Models\Domain::create([
    'name' => 'zip.50g.io',
    'slug' => 'zip-50g-io',
    'domain_url' => 'https://zip.50g.io',
    'site_id' => 'wp-prod-zip50gio-001',
    'api_key' => 'test_' . bin2hex(random_bytes(32)),
    'status' => 'active',
    'timezone' => 'America/Los_Angeles',
    'wordpress_version' => '6.8.3',
    'plugin_version' => '2.0.0',
    'settings' => [],
    'is_active' => true,
]);

echo "API Key: " . $domain->api_key;
```

**Ou usar o comando com `--create-domain`:**
```bash
docker-compose exec app php artisan report:submit-test --create-domain
```

### **2. Aplicação Rodando**

Certifique-se de que a aplicação está rodando:

```bash
docker-compose up -d
```

---

## 📄 Formato do JSON

O arquivo `docs/newdata.json` deve conter:

```json
{
  "source": {
    "domain": "zip.50g.io",
    "site_id": "wp-prod-zip50gio-001",
    "site_name": "SmarterHome.ai",
    "wordpress_version": "6.8.3",
    "plugin_version": "2.0.0",
    "timezone": "America/Los_Angeles"
  },
  "metadata": {
    "report_date": "2025-10-11",
    "report_period": {
      "start": "2025-10-11 00:00:00",
      "end": "2025-10-11 23:59:59"
    },
    "generated_at": "2025-10-11 18:54:50",
    "total_processing_time": 0,
    "data_version": "2.0.0"
  },
  "summary": {
    "total_requests": 1502,
    "success_rate": 85.15,
    "failed_requests": 223
  },
  "providers": { ... },
  "geographic": { ... },
  "performance": { ... },
  "speed_metrics": { ... },
  "technology_metrics": { ... },
  "exclusion_metrics": { ... },
  "health": { ... }
}
```

### **Seções Obrigatórias:**
- ✅ `source` - Informações da origem
- ✅ `metadata` - Metadados do relatório
- ✅ `summary` - Resumo estatístico

### **Seções Opcionais:**
- ⚪ `providers` - Dados de provedores
- ⚪ `geographic` - Dados geográficos
- ⚪ `performance` - Métricas de performance
- ⚪ `speed_metrics` - Métricas de velocidade
- ⚪ `technology_metrics` - Métricas de tecnologia
- ⚪ `exclusion_metrics` - Métricas de exclusão
- ⚪ `health` - Status de saúde do sistema

---

## 🔍 Exemplos de Uso

### **Exemplo 1: Submissão Simples**
```bash
./submit-test-report.sh
```

**Saída esperada:**
```
🚀 Submitting Test Report to API...

📄 Arquivo: docs/newdata.json
📦 Tamanho: 36561 bytes

🔍 Buscando domínio no banco de dados...
✅ Domínio encontrado: zip.50g.io
🔑 API Key: test_fcb4eaac3de8316...

📡 Endpoint: http://localhost:8006/api/reports/submit
⏳ Enviando requisição...

📊 HTTP Status: 200

✅ Report submitted successfully!

Response:
{
  "success": true,
  "message": "Report received and queued for processing",
  "data": {
    "report_id": 1,
    "report_date": "2025-10-11",
    "status": "pending"
  }
}

🎉 Report ID: 1
📅 Report Date: 2025-10-11
📊 Status: pending
```

### **Exemplo 2: Criar Domínio Via Artisan**
```bash
# Criar domínio automaticamente do JSON
docker-compose exec app php artisan report:submit-test --create-domain
```

### **Exemplo 3: Verificar Domínios Existentes**
```bash
docker-compose exec app php artisan tinker --execute="
    App\Models\Domain::where('is_active', true)
        ->get(['name', 'api_key'])
        ->each(fn(\$d) => print \$d->name . ': ' . \$d->api_key . PHP_EOL);
"
```

---

## 🔧 Solução de Problemas

### **Erro: Connection refused**
```bash
# Verificar se a aplicação está rodando
docker-compose ps

# Se não estiver, iniciar
docker-compose up -d

# Verificar logs
docker-compose logs -f app
```

### **Erro: Invalid API key**
```bash
# Listar domínios com API keys
docker-compose exec app php artisan tinker --execute="
    App\Models\Domain::all()->each(function(\$d) {
        echo \$d->name . ' => ' . \$d->api_key . PHP_EOL;
    });
"
```

### **Erro: Domain mismatch**
O domínio no JSON (`source.domain`) deve corresponder ao domínio autenticado.

```json
// Certifique-se de que matches:
{
  "source": {
    "domain": "zip.50g.io"  // ← Deve ser igual ao domínio do banco
  }
}
```

### **Erro: Validation errors**
Verifique se todas as seções obrigatórias estão presentes:
- `source.domain`
- `source.site_id`
- `source.site_name`
- `metadata.report_date`
- `metadata.report_period.start`
- `metadata.report_period.end`
- `metadata.generated_at`
- `metadata.data_version`
- `summary`

---

## 📊 Testando o Endpoint

### **Verificar Rota:**
```bash
docker-compose exec app php artisan route:list | grep reports
```

Deve mostrar:
```
POST   api/reports/submit ................... reports.submit
GET    api/admin/reports .................... admin.reports.index
GET    api/admin/reports/recent ............. admin.reports.recent
GET    api/admin/reports/{id} ............... admin.reports.show
```

### **Testar Manualmente com CURL:**
```bash
curl -X POST http://localhost:8006/api/reports/submit \
  -H "X-API-Key: SUA_API_KEY_AQUI" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d @docs/newdata.json \
  | jq '.'
```

---

## 🎯 Fluxo Completo

1. **Preparar Ambiente:**
   ```bash
   docker-compose up -d
   ```

2. **Criar Domínio (se necessário):**
   ```bash
   docker-compose exec app php artisan report:submit-test --create-domain
   ```

3. **Submeter Relatório:**
   ```bash
   ./submit-test-report.sh
   ```

4. **Verificar Relatório Criado:**
   ```bash
   docker-compose exec app php artisan tinker --execute="
       App\Models\Report::latest()->first();
   "
   ```

5. **Ver Logs do Job (se usar queue):**
   ```bash
   docker-compose logs -f app | grep ProcessReportJob
   ```

---

## 💡 Dicas

- Use `jq` para formatar JSON: `./submit-test-report.sh | jq '.'`
- Salve o response: `./submit-test-report.sh > response.json`
- Teste com diferentes arquivos: modificar `JSON_FILE` no script
- Para debug, adicione `-v` ao curl para ver headers

---

## 📝 Arquivos Relacionados

- **Script**: `submit-test-report.sh` - Submissão via CURL
- **Comando**: `app/Console/Commands/SubmitTestReport.php` - Comando Artisan  
- **Controller**: `app/Http/Controllers/Api/ReportController.php` - API endpoint
- **Request**: `app/Http/Requests/SubmitReportRequest.php` - Validação
- **Dados**: `docs/newdata.json` - JSON de exemplo

---

*Última atualização: Outubro 2024*
*Status: ✅ Operacional*

