#!/bin/bash
# Script de Deploy Automático do Backend Laravel
# Uso: bash deploy.sh

set -e  # Exit on any error

echo "🚀 Deploy do Backend Laravel"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Navigate to project directory
cd /home/address3/addresses_dashboard

echo "📥 1. Atualizando código do Git..."
git pull origin main || git pull origin master

if [ $? -ne 0 ]; then
    echo "❌ Erro ao fazer git pull"
    exit 1
fi
echo "✅ Código atualizado"
echo ""

echo "📦 2. Instalando dependências (se necessário)..."
if git diff HEAD@{1} HEAD --name-only | grep -q "composer.json\|composer.lock"; then
    composer install --no-dev --optimize-autoloader
    echo "✅ Dependências atualizadas"
else
    echo "⏭️  Sem mudanças em composer.json, pulando instalação"
fi
echo ""

echo "🔄 3. Executando migrations (se houver)..."
php artisan migrate --force
echo "✅ Migrations executadas"
echo ""

echo "🧹 4. Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches limpos"
echo ""

echo "⚡ 5. Otimizando aplicação..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Otimização concluída"
echo ""

echo "🔄 6. Reiniciando PM2 backend..."
pm2 restart addresses-dashboard-backend
echo "✅ Backend reiniciado"
echo ""

echo "🔄 7. Reiniciando workers..."
pm2 restart queue-worker-default
pm2 restart queue-worker-reports
pm2 restart queue-worker-messages
echo "✅ Workers reiniciados"
echo ""

echo "⏳ Aguardando 3 segundos..."
sleep 3
echo ""

echo "📊 8. Verificando status..."
pm2 list | grep -E "(addresses-dashboard|queue-worker)"
echo ""

echo "🧪 9. Testando backend..."
if curl -s http://127.0.0.1:8006/api/health > /dev/null 2>&1; then
    echo "✅ Backend está respondendo na porta 8006!"
else
    echo "⚠️  Backend pode estar iniciando, verificando..."
    sleep 2
    if curl -s http://127.0.0.1:8006 > /dev/null 2>&1; then
        echo "✅ Backend respondendo!"
    else
        echo "❌ Backend não está respondendo!"
        echo "   Ver logs: pm2 logs addresses-dashboard-backend"
    fi
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 Deploy do Backend concluído!"
echo ""
echo "📝 Comandos úteis:"
echo "   • Ver logs backend:     pm2 logs addresses-dashboard-backend"
echo "   • Ver logs workers:     pm2 logs queue-worker-default"
echo "   • Verificar fila:       ./check-queue.sh"
echo "   • Reiniciar workers:    ./restart-workers.sh"
echo "   • Status geral:         pm2 list"
echo ""
echo "🌐 API: https://dash3.50g.io/api"
echo ""


