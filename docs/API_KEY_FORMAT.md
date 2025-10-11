# API Key Format - Domain Authentication

## 🔑 Formato da API Key

### Padrão Atual

```
dmn_live_{64_caracteres_aleatórios}
```

**Exemplo:**
```
dmn_live_8k2Hf9Xp3Qw7Zn4Vm5Bc1Rd6Tg0Lm8h3Jn9Kp2Qr4St6Uv1Wx5Yz7Ab3Cd9Ef2Gh
```

### Prefixo: `dmn_live_`

- `dmn` = **Domain** (identifica que é uma chave de domínio)
- `live` = Ambiente de **produção** (vs `test`)
- `_` = Separador

### Comprimento Total

- Prefixo: 9 caracteres (`dmn_live_`)
- Random: 64 caracteres
- **Total: 73 caracteres**

---

## 🎯 Por que "dmn_live_" e não "sk_live_"?

### Problema com "sk_live_"

O prefixo `sk_live_` é usado pela **Stripe** (serviço de pagamentos) para suas API keys:
- Stripe Secret Keys: `sk_live_...`
- Stripe Public Keys: `pk_live_...`

**Consequências:**
- ❌ GitHub Secret Scanning bloqueia push
- ❌ Confusão com chaves reais da Stripe
- ❌ Falsos positivos em security scans

### Solução: Prefixo Único

`dmn_live_` é específico do nosso sistema:
- ✅ Não conflita com serviços conhecidos
- ✅ GitHub permite push
- ✅ Claro e descritivo (Domain)
- ✅ Mantém padrão de "live" vs "test"

---

## 🔄 Variações Futuras

### Ambientes

#### Production
```
dmn_live_{64_chars}
```

#### Testing/Sandbox
```
dmn_test_{64_chars}
```

### Usos Específicos (Futuro)

#### Admin API Keys
```
adm_live_{64_chars}
```

#### Report Ingestion (Domain)
```
dmn_live_{64_chars}  ← Atual
```

#### Webhook Verification
```
whk_live_{64_chars}
```

---

## 🛡️ Segurança

### Geração

```php
use Illuminate\Support\Str;

$apiKey = 'dmn_live_' . Str::random(64);
```

**Características:**
- Criptograficamente seguro (usa `random_bytes()`)
- 64 caracteres aleatórios (a-zA-Z0-9)
- Único (constraint no banco de dados)

### Armazenamento

**Atual:** Plain text no banco

```sql
api_key VARCHAR(255) UNIQUE NOT NULL
```

**Futuro (Recomendado):** Hash com verificação

```sql
api_key_hash VARCHAR(255) UNIQUE NOT NULL
api_key_prefix VARCHAR(20) -- para identificação rápida
api_key_last_four CHAR(4)  -- para exibição
```

### Validação

```php
// Verificar formato
if (!preg_match('/^dmn_(live|test)_[a-zA-Z0-9]{64}$/', $apiKey)) {
    throw new InvalidApiKeyException();
}

// Buscar domínio
$domain = Domain::where('api_key', $apiKey)->first();
```

### Regeneração

**Quando regenerar:**
- ✅ Suspeita de vazamento
- ✅ Mudança de ownership
- ✅ Rotação periódica (recomendado: 90 dias)
- ✅ Requisição do cliente

**Como regenerar:**
```bash
POST /api/admin/domains/{id}/regenerate-api-key
Authorization: Bearer {super_admin_token}
```

**⚠️ Importante:**
- A chave antiga é invalidada imediatamente
- O cliente deve atualizar sua integração rapidamente
- Notificar o cliente antes de regenerar

---

## 📋 Boas Práticas

### Para Admins

1. ✅ **Nunca compartilhe API keys** em canais inseguros
2. ✅ **Armazene em variáveis de ambiente** no servidor do cliente
3. ✅ **Monitore uso** de cada API key
4. ✅ **Rotacione periodicamente** (trimestral)
5. ✅ **Revogue imediatamente** se suspeitar de vazamento

### Para Parceiros (Domains)

1. ✅ **Armazene em `.env`:**
   ```env
   DASHBOARD_API_KEY=dmn_live_abc123...
   DASHBOARD_API_URL=https://dashboard.com/api
   ```

2. ✅ **Nunca commite** no Git:
   ```gitignore
   .env
   config/secrets.php
   ```

3. ✅ **Use em headers:**
   ```php
   $response = Http::withHeaders([
       'Authorization' => 'Bearer ' . env('DASHBOARD_API_KEY')
   ])->post(env('DASHBOARD_API_URL') . '/reports/ingest', $data);
   ```

4. ✅ **Trate erros de autenticação:**
   ```php
   if ($response->status() === 401) {
       Log::error('Invalid API key, contact support');
       // Notificar equipe
   }
   ```

---

## 🔍 Identificação de API Keys

### No Código
```php
// Verificar se string é uma API key válida
function isValidDomainApiKey(string $key): bool {
    return preg_match('/^dmn_live_[a-zA-Z0-9]{64}$/', $key) === 1;
}

// Extrair prefixo
function getKeyEnvironment(string $key): string {
    if (str_starts_with($key, 'dmn_live_')) return 'production';
    if (str_starts_with($key, 'dmn_test_')) return 'testing';
    return 'unknown';
}
```

### Em Logs (Mascarado)
```php
// Nunca logar chave completa
function maskApiKey(string $key): string {
    $prefix = substr($key, 0, 9);  // "dmn_live_"
    $lastFour = substr($key, -4);   // "Gh8K"
    return $prefix . '****' . $lastFour;
}

// Resultado: dmn_live_****Gh8K
```

---

## 📊 Monitoramento

### Métricas a Rastrear (Futuro)

#### Por API Key
- Total de requests
- Success rate
- Last used timestamp
- Requests por dia/hora
- Errors count

#### Alertas
- API key não usada há > 30 dias
- Taxa de erro > 10%
- Pico anormal de uso
- Tentativas de uso após revogação

---

## 🔮 Roadmap

### Fase 1 (Atual) ✅
- Geração automática de API keys
- Armazenamento em plain text
- Regeneração manual

### Fase 2 (Próximo)
- Hash das API keys
- Armazenamento de prefix e last_four para display
- API key expiration

### Fase 3 (Futuro)
- Rotação automática (90 dias)
- Dual-key support (transição suave)
- Rate limiting por API key
- Webhook signatures

### Fase 4 (Avançado)
- Scoped API keys (read-only, write, admin)
- IP whitelisting
- Audit trail completo
- Revogação remota via webhook

---

## 📝 Referências

- **API Key Best Practices:** [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/API_Security_Cheat_Sheet.html)
- **Secret Management:** [12 Factor App](https://12factor.net/config)
- **GitHub Secret Scanning:** [Docs](https://docs.github.com/en/code-security/secret-scanning)

---

## ✅ Checklist

- ✅ Prefixo único (`dmn_live_`)
- ✅ Comprimento adequado (64 chars random)
- ✅ Geração criptograficamente segura
- ✅ Unique constraint no banco
- ✅ Regeneração funcional
- ✅ Documentação completa
- ✅ Testes validam formato
- ✅ Não conflita com Stripe/outros serviços

---

**Data:** 2025-10-11  
**Formato:** `dmn_live_{64_chars}`  
**Status:** ✅ Implementado e Testado

