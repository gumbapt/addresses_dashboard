# 🌐 Configuração do ngrok

## 📋 Resumo

O ngrok permite expor sua aplicação local (porta 8007) para acesso externo via URL pública.

## 🔧 Configuração Inicial

### 1. Criar Conta no ngrok

1. Acesse: https://dashboard.ngrok.com/signup
2. Crie uma conta (grátis)
3. Faça login

### 2. Obter Authtoken

1. Acesse: https://dashboard.ngrok.com/get-started/your-authtoken
2. Copie seu authtoken

### 3. Configurar Authtoken

```bash
ngrok config add-authtoken SEU_TOKEN_AQUI
```

**Exemplo:**
```bash
ngrok config add-authtoken 2abc123def456ghi789jkl012mno345pqr678stu901vwx234
```

## 🚀 Usar o Script

Após configurar o authtoken, execute:

```bash
./start-ngrok.sh
```

O script irá:
- ✅ Verificar se ngrok está instalado
- ✅ Verificar se authtoken está configurado
- ✅ Iniciar túnel na porta 8007
- ✅ Mostrar a URL pública

## 📊 Informações

### Porta do Docker
- **Porta local:** 8007
- **Container:** dashboard_addresses_nginx
- **Mapeamento:** `8007:80`

### Interface Web do ngrok
- **URL:** http://localhost:4040
- **Funcionalidades:**
  - Ver requisições em tempo real
  - Ver URL pública
  - Ver estatísticas
  - Reiniciar túnel

### URLs Geradas

O ngrok gera duas URLs:
- **HTTP:** `http://xxxx-xx-xx-xx-xx.ngrok-free.app`
- **HTTPS:** `https://xxxx-xx-xx-xx-xx.ngrok-free.app` (recomendado)

## 🛠️ Comandos Úteis

### Iniciar ngrok manualmente
```bash
ngrok http 8007
```

### Iniciar em background
```bash
ngrok http 8007 --log=stdout > /tmp/ngrok.log 2>&1 &
```

### Parar ngrok
```bash
pkill -f "ngrok http"
```

### Ver logs
```bash
tail -f /tmp/ngrok.log
```

### Verificar túneis ativos
```bash
curl http://localhost:4040/api/tunnels
```

## ⚠️ Limitações (Plano Grátis)

- **Sessões:** Limitadas (algumas horas)
- **URLs:** Mudam a cada reinício (exceto com domínio personalizado)
- **Tráfego:** Limitado
- **Conexões simultâneas:** Limitadas

## 💡 Dicas

1. **URLs Temporárias:** URLs do plano grátis mudam a cada reinício
2. **Domínio Personalizado:** Plano pago permite domínio fixo
3. **Autenticação:** Pode adicionar autenticação básica no ngrok
4. **Logs:** Use a interface web para debug

## 🔒 Segurança

⚠️ **Importante:** URLs públicas do ngrok são acessíveis por qualquer pessoa que tenha o link.

- Use apenas para desenvolvimento/testes
- Não exponha dados sensíveis
- Considere adicionar autenticação na aplicação
- Use HTTPS quando possível

## 📝 Exemplo de Uso

Após iniciar o ngrok:

```bash
# URL pública gerada
https://abc123.ngrok-free.app

# Acessar API
curl https://abc123.ngrok-free.app/api/admin/reports

# Com autenticação
curl -H "Authorization: Bearer TOKEN" \
     https://abc123.ngrok-free.app/api/admin/reports
```

