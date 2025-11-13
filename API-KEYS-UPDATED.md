# 🔑 API Keys - Database Reset (2025-11-12)

## ✅ Base de Dados Resetada com Sucesso!

A base de dados foi completamente resetada e populada novamente com:
- ✅ **5 domínios** ativos
- ✅ **50 relatórios** (10 por domínio)
- ✅ **55 provedores** únicos
- ✅ **37 estados** cobertos

---

## 🔑 Novas API Keys dos Domínios

⚠️ **ATENÇÃO**: As API keys mudaram após o reset!

| Domínio | Nova API Key |
|---------|-------------|
| **zip.50g.io** | `5ysoVBU3WLIJSHqXSRA35x0dxZmRQ4qR` |
| **fiberfinder.com** | `XJXFEBgGe4RsifOVpjHdS4zJKSF6ZA4h` |
| **smarterhome.ai** | `v7PahspDJewitkwY8RBvkytOxX9WfTOL` |
| **ispfinder.net** | `P0vliznhW7cv8DREBEOZN60u0jpWoTrV` |
| **broadbandcheck.io** | `ZmkKbkV1WFXnBL8IhjTdZ0noAf104ppb` |

---

## 🧪 Testando a Nova API Key

### Comando de Teste (zip.50g.io):

```bash
curl -s -X POST https://dash3.50g.io/api/reports/submit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 5ysoVBU3WLIJSHqXSRA35x0dxZmRQ4qR" \
  -H "Accept: application/json" \
  -d @/home/address3/addresses_dashboard/YOUR-JSON-CORRECTED.json | jq .
```

### Ou use o conversor automático:

```bash
# Converter seu JSON antigo para o novo formato
php /home/address3/addresses_dashboard/convert-report-format.php \
  /sites/zip.50g.io/files/wp-content/uploads/logs/old-format.json \
  /tmp/converted.json

# Enviar para a API
curl -s -X POST https://dash3.50g.io/api/reports/submit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 5ysoVBU3WLIJSHqXSRA35x0dxZmRQ4qR" \
  -d @/tmp/converted.json | jq .
```

---

## 📊 Status dos Relatórios

### Por Domínio:
```
🌐 zip.50g.io          → 10 relatórios (todos processados ✅)
🌐 fiberfinder.com     → 10 relatórios (todos processados ✅)
🌐 smarterhome.ai      → 10 relatórios (todos processados ✅)
🌐 ispfinder.net       → 10 relatórios (todos processados ✅)
🌐 broadbandcheck.io   → 10 relatórios (todos processados ✅)
```

---

## 🔄 Workers PM2

Os workers estão rodando e prontos para processar novos reports:
- ✅ **queue-worker-default** (2 instâncias) - Processa jobs gerais
- ✅ **queue-worker-reports** (1 instância) - Processa relatórios
- ✅ **queue-worker-messages** (1 instância) - Processa mensagens

### Verificar Workers:
```bash
pm2 list
pm2 logs queue-worker-default
```

---

## 📝 Atualizar no WordPress

Se você estava usando a API key antiga no WordPress, **atualize para a nova**:

### API Key Antiga (INVÁLIDA):
```
dmn_live_dzDdDh3xT4seke4kh6HRLfWMfWhL5UsCU5ooJgvJOXagmELWgI4cjheQDg1xt9xh ❌
```

### API Key Nova (VÁLIDA):
```
5ysoVBU3WLIJSHqXSRA35x0dxZmRQ4qR ✅
```

---

## 🎯 Próximos Passos

1. ✅ Atualizar API key no plugin WordPress
2. ✅ Testar envio de relatório com formato correto
3. ✅ Verificar processamento pelos workers

### Verificar Status do Sistema:

```bash
# Ver todos os relatórios
cd /home/address3/addresses_dashboard
php artisan tinker --execute="
  echo 'Total de relatórios: ' . \App\Models\Report::count() . PHP_EOL;
  echo 'Processados: ' . \App\Models\Report::where('status', 'processed')->count() . PHP_EOL;
  echo 'Na fila: ' . \App\Models\Report::where('status', 'pending')->count() . PHP_EOL;
"

# Ver fila de jobs
php artisan queue:failed

# Ver logs dos workers
pm2 logs queue-worker-default --lines 50
```

---

## ⚠️ Importante

- Todos os dados antigos foram **apagados**
- Novas API keys foram geradas
- 50 relatórios de exemplo foram criados e processados
- Workers estão ativos e processando automaticamente

---

**Data do Reset**: 2025-11-12
**Banco de Dados**: MySQL (Produção)
**Total de Reports**: 50
**Total de Domínios**: 5

