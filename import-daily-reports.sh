#!/bin/bash

# Script para importar relatórios diários em lote

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  📊 IMPORTADOR DE RELATÓRIOS DIÁRIOS                          ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}\n"

# Verificar se o diretório existe
if [ ! -d "docs/daily_reports" ]; then
    echo -e "${RED}❌ Diretório docs/daily_reports não encontrado${NC}"
    exit 1
fi

# Contar arquivos
FILE_COUNT=$(ls docs/daily_reports/*.json 2>/dev/null | wc -l)
echo -e "${BLUE}📁 Encontrados ${FILE_COUNT} arquivos de relatórios diários${NC}\n"

if [ $FILE_COUNT -eq 0 ]; then
    echo -e "${RED}❌ Nenhum arquivo JSON encontrado${NC}"
    exit 1
fi

# Mostrar arquivos encontrados
echo -e "${YELLOW}📋 Arquivos encontrados:${NC}"
ls docs/daily_reports/*.json | head -10 | while read file; do
    echo "  • $(basename "$file")"
done
if [ $FILE_COUNT -gt 10 ]; then
    echo "  ... e mais $((FILE_COUNT - 10)) arquivos"
fi
echo ""

# Opções de importação
echo -e "${BLUE}━━━ OPÇÕES DE IMPORTAÇÃO ━━━${NC}\n"

echo -e "${YELLOW}1️⃣ Teste (Dry Run) - Ver o que seria importado:${NC}"
echo -e "docker-compose exec app php artisan reports:import-daily --dry-run"
echo ""

echo -e "${YELLOW}2️⃣ Importar todos os relatórios:${NC}"
echo -e "docker-compose exec app php artisan reports:import-daily"
echo ""

echo -e "${YELLOW}3️⃣ Importar com força (sobrescrever existentes):${NC}"
echo -e "docker-compose exec app php artisan reports:import-daily --force"
echo ""

echo -e "${YELLOW}4️⃣ Importar período específico:${NC}"
echo -e "docker-compose exec app php artisan reports:import-daily --date-from=2025-07-01 --date-to=2025-07-31"
echo ""

echo -e "${YELLOW}5️⃣ Importar apenas um mês:${NC}"
echo -e "docker-compose exec app php artisan reports:import-daily --date-from=2025-07-01 --date-to=2025-07-31"
echo ""

# Executar teste por padrão
echo -e "${BLUE}━━━ EXECUTANDO TESTE (DRY RUN) ━━━${NC}\n"

docker-compose exec app php artisan reports:import-daily --dry-run

echo ""
echo -e "${BLUE}━━━ PRÓXIMOS PASSOS ━━━${NC}\n"

echo -e "${GREEN}✅ Para importar realmente:${NC}"
echo -e "docker-compose exec app php artisan reports:import-daily"
echo ""

echo -e "${GREEN}✅ Para verificar relatórios importados:${NC}"
echo -e "docker-compose exec app php artisan tinker --execute=\"echo 'Total de reports: ' . App\Models\Report::count() . PHP_EOL;\""
echo ""

echo -e "${GREEN}✅ Para testar o dashboard com dados reais:${NC}"
echo -e "TOKEN=\$(curl -s http://localhost:8006/api/admin/login -X POST -H \"Content-Type: application/json\" -d '{\"email\":\"admin@dashboard.com\",\"password\":\"password123\"}' | jq -r '.token')"
echo -e "curl -s \"http://localhost:8006/api/admin/reports/domain/1/dashboard\" -H \"Authorization: Bearer \$TOKEN\" | jq '.data.kpis'"
echo ""

echo -e "${CYAN}💡 Dica: Após importar, você terá dados reais de múltiplos dias para testar a agregação!${NC}"

