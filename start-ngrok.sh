#!/bin/bash

# Script para iniciar ngrok na porta do Docker

PORT=8007
NGROK_PID_FILE="/tmp/ngrok.pid"
NGROK_LOG_FILE="/tmp/ngrok.log"

echo "🚀 Iniciando ngrok na porta $PORT..."

# Verificar se ngrok está instalado
if ! command -v ngrok &> /dev/null; then
    echo "❌ ngrok não está instalado."
    echo "   Instale com: brew install ngrok/ngrok/ngrok"
    exit 1
fi

# Verificar se já está rodando
if [ -f "$NGROK_PID_FILE" ]; then
    OLD_PID=$(cat "$NGROK_PID_FILE")
    if ps -p "$OLD_PID" > /dev/null 2>&1; then
        echo "⚠️  ngrok já está rodando (PID: $OLD_PID)"
        echo "   Para parar: pkill -f 'ngrok http'"
        exit 1
    fi
fi

# Verificar se tem authtoken configurado
if ! ngrok config check &> /dev/null; then
    echo ""
    echo "⚠️  ngrok precisa de autenticação!"
    echo ""
    echo "📝 Passos para configurar:"
    echo "   1. Acesse: https://dashboard.ngrok.com/signup"
    echo "   2. Crie uma conta (grátis)"
    echo "   3. Copie seu authtoken de: https://dashboard.ngrok.com/get-started/your-authtoken"
    echo "   4. Execute: ngrok config add-authtoken SEU_TOKEN_AQUI"
    echo ""
    echo "   Depois execute este script novamente."
    exit 1
fi

# Iniciar ngrok em background
echo "✅ Iniciando túnel ngrok..."
ngrok http $PORT --log=stdout > "$NGROK_LOG_FILE" 2>&1 &
NGROK_PID=$!
echo $NGROK_PID > "$NGROK_PID_FILE"

# Aguardar ngrok iniciar
sleep 3

# Verificar se está rodando
if ps -p $NGROK_PID > /dev/null 2>&1; then
    echo "✅ ngrok iniciado com sucesso! (PID: $NGROK_PID)"
    echo ""
    echo "📊 Informações do túnel:"
    
    # Tentar obter URL do túnel
    sleep 2
    TUNNEL_INFO=$(curl -s http://localhost:4040/api/tunnels 2>/dev/null)
    
    if [ ! -z "$TUNNEL_INFO" ]; then
        PUBLIC_URL=$(echo "$TUNNEL_INFO" | grep -o '"public_url":"[^"]*"' | head -1 | cut -d'"' -f4)
        if [ ! -z "$PUBLIC_URL" ]; then
            echo "   🌐 URL pública: $PUBLIC_URL"
        fi
    fi
    
    echo ""
    echo "📋 Comandos úteis:"
    echo "   Ver interface web: http://localhost:4040"
    echo "   Parar ngrok: pkill -f 'ngrok http'"
    echo "   Ver logs: tail -f $NGROK_LOG_FILE"
    echo ""
else
    echo "❌ Erro ao iniciar ngrok. Verifique os logs:"
    echo "   tail -f $NGROK_LOG_FILE"
    exit 1
fi

