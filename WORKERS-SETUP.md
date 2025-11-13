# 🔧 Laravel Queue Workers Setup com PM2

## ✅ O Que Foi Configurado

Agora você tem **4 workers** rodando permanentemente via PM2 para processar jobs em background:

1. **addresses-dashboard-backend** - Servidor Laravel API
2. **queue-worker-default** (2 instâncias) - Processa jobs gerais em paralelo
3. **queue-worker-reports** (1 instância) - Processa relatórios diários
4. **queue-worker-messages** (1 instância) - Processa mensagens do chat

### 📦 Arquivos Criados

- `ecosystem.config.js` - Configuração PM2 com todos os workers
- `start-workers.sh` - Inicia todos os workers
- `restart-workers.sh` - Reinicia workers após mudanças no código
- `check-queue.sh` - Verifica status da fila e jobs pendentes

---

## 🚀 Como Usar

### Iniciar Workers Pela Primeira Vez

```bash
cd /home/address3/addresses_dashboard
./start-workers.sh
```

Isso vai:
- Parar o backend atual do PM2
- Iniciar o backend + todos os workers
- Salvar a configuração do PM2

### Verificar Status

```bash
pm2 list
# ou
./check-queue.sh
```

### Ver Logs dos Workers

```bash
# Todos os logs
pm2 logs

# Logs de um worker específico
pm2 logs queue-worker-default
pm2 logs queue-worker-reports
pm2 logs queue-worker-messages

# Últimas 50 linhas
pm2 logs --lines 50

# Monitoramento em tempo real
pm2 monit
```

### Reiniciar Workers (após atualizar código)

```bash
./restart-workers.sh
# ou
pm2 restart all
```

### Parar Tudo

```bash
pm2 stop all
```

---

## 📊 Como Funciona com Múltiplos Reports

Quando você receber **vários daily reports de uma vez**:

1. ✅ **Enfileiramento Rápido**: Cada report é salvo na tabela `jobs` em ~10ms
2. ✅ **Processamento Paralelo**: Os 2 workers `queue-worker-default` pegam jobs simultaneamente
3. ✅ **Sem Sobrecarga**: Se houver 10 reports, serão processados 2 por vez
4. ✅ **Retry Automático**: Se um job falhar, tenta novamente até 3 vezes
5. ✅ **Timeout**: Cada job tem 1 hora de timeout (3600s)

### Exemplo Prático

Se você receber 20 daily reports ao mesmo tempo:

```
Queue: [R1, R2, R3, R4, R5, R6, R7, R8, R9, R10, R11, ..., R20]

Worker 1: R1 (processando) → R3 → R5 → R7 → ...
Worker 2: R2 (processando) → R4 → R6 → R8 → ...
```

**Tempo de processamento**: ~5 minutos por report
- Com 1 worker: 20 × 5min = **100 minutos**
- Com 2 workers: 10 × 5min = **50 minutos** ⚡

---

## 🔍 Monitoramento

### Ver Jobs Pendentes

```bash
cd /home/address3/addresses_dashboard
php artisan queue:monitor
```

### Ver Jobs Falhados

```bash
php artisan queue:failed
```

### Reprocessar Jobs Falhados

```bash
# Reprocessar todos
php artisan queue:retry all

# Reprocessar um específico
php artisan queue:retry [JOB_ID]
```

### Limpar Jobs Falhados

```bash
php artisan queue:flush
```

---

## ⚠️ Troubleshooting

### Workers não estão processando jobs

```bash
# 1. Verificar se estão rodando
pm2 list

# 2. Ver logs para erros
pm2 logs queue-worker-default --lines 50

# 3. Reiniciar workers
./restart-workers.sh
```

### Workers ficam travados

Workers reiniciam automaticamente a cada **1 hora** (`max-time=3600`) para evitar memory leaks.

Se precisar reiniciar manualmente:
```bash
pm2 restart queue-worker-default
pm2 restart queue-worker-reports
```

### Memória alta

Cada worker tem limite de **512MB**. Se exceder, reinicia automaticamente.

Para aumentar o limite:
```bash
# Edite ecosystem.config.js
args: 'queue:work database --queue=default --sleep=3 --tries=3 --max-time=3600 --memory=1024'
```

---

## 🎯 Comandos Rápidos

```bash
# Status geral
pm2 list

# Ver logs em tempo real
pm2 logs

# Monitorar CPU/memória
pm2 monit

# Reiniciar tudo
pm2 restart all

# Parar tudo
pm2 stop all

# Iniciar novamente
pm2 start ecosystem.config.js

# Verificar fila
./check-queue.sh
```

---

## 🔄 Auto-Start no Boot

Para garantir que os workers iniciem automaticamente após reboot:

```bash
# Salvar configuração atual
pm2 save

# Configurar startup (já deve estar configurado)
pm2 startup
```

---

## 📈 Ajustando Capacidade

Se precisar processar mais reports simultaneamente, edite `ecosystem.config.js`:

```javascript
// Aumentar para 4 workers
{
  name: 'queue-worker-default',
  instances: 4,  // Era 2, agora 4
  // ...
}
```

Depois:
```bash
pm2 reload ecosystem.config.js
```

---

## ✅ Próximos Passos

1. Execute `./start-workers.sh` para iniciar os workers
2. Teste enviando alguns daily reports
3. Monitore com `pm2 logs queue-worker-default`
4. Verifique se estão sendo processados com `./check-queue.sh`

**Agora seu sistema está pronto para receber vários daily reports simultaneamente! 🎉**

