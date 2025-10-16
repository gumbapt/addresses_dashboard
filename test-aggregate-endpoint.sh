#!/bin/bash

# Script para testar o endpoint de agregação de relatórios

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  📊 TESTE DO ENDPOINT DE AGREGAÇÃO DE RELATÓRIOS              ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}\n"

# 1. Login
echo -e "${BLUE}━━━ PASSO 1: LOGIN DE ADMIN ━━━${NC}\n"

TOKEN=$(curl -s http://localhost:8006/api/admin/login \
    -X POST \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@dashboard.com","password":"password123"}' \
    | jq -r '.token' 2>/dev/null)

if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
    echo -e "${GREEN}✅ Login bem-sucedido${NC}"
    echo -e "${YELLOW}Token: ${TOKEN:0:30}...${NC}\n"
else
    echo -e "${RED}❌ Erro no login${NC}\n"
    exit 1
fi

# 2. Listar domínios disponíveis
echo -e "${BLUE}━━━ PASSO 2: DOMÍNIOS DISPONÍVEIS ━━━${NC}\n"

docker-compose exec -T app php artisan tinker --execute="
\$domains = App\Models\Domain::all();
foreach (\$domains as \$domain) {
    echo \$domain->id . ' | ' . \$domain->name . ' | Reports: ' . App\Models\Report::where('domain_id', \$domain->id)->where('status', 'processed')->count() . PHP_EOL;
}
" 2>/dev/null | tail -10

echo ""

# 3. Testar agregação para Domain ID 1
DOMAIN_ID=1

echo -e "${BLUE}━━━ PASSO 3: AGREGAÇÃO PARA DOMAIN #${DOMAIN_ID} ━━━${NC}\n"

RESPONSE=$(curl -s "http://localhost:8006/api/admin/reports/domain/${DOMAIN_ID}/aggregate" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")

SUCCESS=$(echo "$RESPONSE" | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    echo -e "${GREEN}✅ Agregação bem-sucedida!${NC}\n"
    
    # Resumo do período
    echo -e "${YELLOW}📅 PERÍODO:${NC}"
    echo "$RESPONSE" | jq '.data.period'
    echo ""
    
    # Resumo das estatísticas
    echo -e "${YELLOW}📊 RESUMO ESTATÍSTICO:${NC}"
    echo "$RESPONSE" | jq '.data.summary'
    echo ""
    
    # Top 5 Providers
    echo -e "${YELLOW}📡 TOP 5 PROVEDORES:${NC}"
    echo "$RESPONSE" | jq '.data.providers[:5] | .[] | {name, technology, total_count, report_count}'
    echo ""
    
    # Top 5 Estados
    echo -e "${YELLOW}🗺️  TOP 5 ESTADOS:${NC}"
    echo "$RESPONSE" | jq '.data.geographic.states[:5] | .[] | {code, name, total_requests, report_count}'
    echo ""
    
    # Top 5 Cidades
    echo -e "${YELLOW}🏙️  TOP 5 CIDADES:${NC}"
    echo "$RESPONSE" | jq '.data.geographic.cities[:5] | .[] | {name, total_requests, report_count}'
    echo ""
    
    # Top 5 CEPs
    echo -e "${YELLOW}📮 TOP 5 CEPs:${NC}"
    echo "$RESPONSE" | jq '.data.geographic.zip_codes[:5] | .[] | {code, total_requests, report_count}'
    echo ""
    
    # Trends
    echo -e "${YELLOW}📈 TENDÊNCIAS DIÁRIAS:${NC}"
    echo "$RESPONSE" | jq '.data.trends | .[] | {date, total_requests, success_rate}'
    echo ""
    
    echo -e "${GREEN}✅ TESTE COMPLETO!${NC}\n"
    
    # Comandos úteis
    echo -e "${BLUE}━━━ COMANDOS ÚTEIS ━━━${NC}\n"
    echo -e "${YELLOW}Ver resposta completa:${NC}"
    echo -e "curl -s \"http://localhost:8006/api/admin/reports/domain/${DOMAIN_ID}/aggregate\" \\"
    echo -e "  -H \"Authorization: Bearer \$TOKEN\" | jq '.'"
    echo ""
    
    echo -e "${YELLOW}Salvar em arquivo:${NC}"
    echo -e "curl -s \"http://localhost:8006/api/admin/reports/domain/${DOMAIN_ID}/aggregate\" \\"
    echo -e "  -H \"Authorization: Bearer \$TOKEN\" > domain_${DOMAIN_ID}_stats.json"
    echo ""
    
else
    echo -e "${RED}❌ Erro na agregação${NC}\n"
    echo "$RESPONSE" | jq '.'
    exit 1
fi

