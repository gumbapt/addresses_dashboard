#!/bin/bash

# Script para testar o endpoint de relatórios diários

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  📊 TESTE DO ENDPOINT DE RELATÓRIOS DIÁRIOS                  ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}\n"

# Verificar se o arquivo existe
if [ ! -f "docs/daily_reports/2025-06-27.json" ]; then
    echo -e "${RED}❌ Arquivo de exemplo não encontrado: docs/daily_reports/2025-06-27.json${NC}"
    exit 1
fi

echo -e "${BLUE}━━━ PASSO 1: PREPARANDO DADOS ━━━${NC}\n"

# Ler o arquivo JSON
DAILY_DATA=$(cat docs/daily_reports/2025-06-27.json)

echo -e "${GREEN}✅ Arquivo JSON carregado${NC}"
echo -e "${YELLOW}📄 Tamanho: $(echo "$DAILY_DATA" | wc -c) bytes${NC}"
echo -e "${YELLOW}📅 Data do relatório: $(echo "$DAILY_DATA" | jq -r '.data.date')${NC}"
echo -e "${YELLOW}📊 Total de requests: $(echo "$DAILY_DATA" | jq -r '.data.summary.total_requests')${NC}"
echo ""

# Obter API key do domínio
echo -e "${BLUE}━━━ PASSO 2: OBTENDO API KEY ━━━${NC}\n"

API_KEY=$(docker-compose exec -T app php artisan tinker --execute="echo App\Models\Domain::where('name', 'zip.50g.io')->first()->api_key;" 2>/dev/null | tr -d '\r\n')

if [ -z "$API_KEY" ] || [ "$API_KEY" = "null" ]; then
    echo -e "${RED}❌ API Key não encontrada para o domínio zip.50g.io${NC}"
    exit 1
fi

echo -e "${GREEN}✅ API Key obtida: ${API_KEY:0:20}...${NC}"
echo ""

# Testar endpoint
echo -e "${BLUE}━━━ PASSO 3: TESTANDO ENDPOINT ━━━${NC}\n"

echo -e "${YELLOW}📡 Enviando relatório diário para: http://localhost:8006/api/reports/submit-daily${NC}"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X POST \
    -H "Content-Type: application/json" \
    -H "X-API-KEY: $API_KEY" \
    -d "$DAILY_DATA" \
    "http://localhost:8006/api/reports/submit-daily")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
RESPONSE_BODY=$(echo "$RESPONSE" | head -n -1)

echo -e "${YELLOW}📊 Status HTTP: $HTTP_CODE${NC}"
echo ""

if [ "$HTTP_CODE" = "201" ]; then
    echo -e "${GREEN}✅ Relatório diário enviado com sucesso!${NC}"
    echo ""
    echo -e "${YELLOW}📋 Resposta:${NC}"
    echo "$RESPONSE_BODY" | jq '.'
    echo ""
    
    # Extrair ID do relatório
    REPORT_ID=$(echo "$RESPONSE_BODY" | jq -r '.data.id')
    REPORT_DATE=$(echo "$RESPONSE_BODY" | jq -r '.data.report_date')
    
    echo -e "${BLUE}━━━ PASSO 4: VERIFICANDO PROCESSAMENTO ━━━${NC}\n"
    
    # Processar jobs
    echo -e "${YELLOW}⚙️ Processando jobs...${NC}"
    docker-compose exec app php artisan queue:work --stop-when-empty > /dev/null 2>&1
    
    # Verificar status do relatório
    echo -e "${YELLOW}🔍 Verificando status do relatório #$REPORT_ID...${NC}"
    
    REPORT_STATUS=$(docker-compose exec -T app php artisan tinker --execute="echo App\Models\Report::find($REPORT_ID)->status;" 2>/dev/null | tr -d '\r\n')
    
    echo -e "${GREEN}✅ Status do relatório: $REPORT_STATUS${NC}"
    echo ""
    
    # Testar dashboard com novo relatório
    echo -e "${BLUE}━━━ PASSO 5: TESTANDO DASHBOARD ━━━${NC}\n"
    
    TOKEN=$(curl -s http://localhost:8006/api/admin/login -X POST -H "Content-Type: application/json" -d '{"email":"admin@dashboard.com","password":"password123"}' | jq -r '.token')
    
    echo -e "${YELLOW}📊 Testando dashboard com novo relatório...${NC}"
    
    DASHBOARD_RESPONSE=$(curl -s "http://localhost:8006/api/admin/reports/domain/1/dashboard" -H "Authorization: Bearer $TOKEN")
    
    if echo "$DASHBOARD_RESPONSE" | jq -e '.success' > /dev/null; then
        TOTAL_REPORTS=$(echo "$DASHBOARD_RESPONSE" | jq -r '.data.period.total_reports')
        TOTAL_REQUESTS=$(echo "$DASHBOARD_RESPONSE" | jq -r '.data.kpis.total_requests')
        
        echo -e "${GREEN}✅ Dashboard atualizado!${NC}"
        echo -e "${YELLOW}📊 Total de relatórios: $TOTAL_REPORTS${NC}"
        echo -e "${YELLOW}📊 Total de requests: $TOTAL_REQUESTS${NC}"
    else
        echo -e "${RED}❌ Erro ao acessar dashboard${NC}"
    fi
    
else
    echo -e "${RED}❌ Erro ao enviar relatório diário${NC}"
    echo ""
    echo -e "${YELLOW}📋 Resposta:${NC}"
    echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"
fi

echo ""
echo -e "${BLUE}━━━ COMANDOS ÚTEIS ━━━${NC}\n"

echo -e "${YELLOW}📤 Enviar outro relatório diário:${NC}"
echo -e "curl -X POST \\"
echo -e "  -H \"Content-Type: application/json\" \\"
echo -e "  -H \"X-API-KEY: \$API_KEY\" \\"
echo -e "  -d @docs/daily_reports/2025-06-28.json \\"
echo -e "  http://localhost:8006/api/reports/submit-daily"
echo ""

echo -e "${YELLOW}📊 Ver relatório específico:${NC}"
echo -e "TOKEN=\$(curl -s http://localhost:8006/api/admin/login -X POST -H \"Content-Type: application/json\" -d '{\"email\":\"admin@dashboard.com\",\"password\":\"password123\"}' | jq -r '.token')"
echo -e "curl -s \"http://localhost:8006/api/admin/reports/$REPORT_ID\" -H \"Authorization: Bearer \$TOKEN\" | jq '.'"
echo ""

echo -e "${YELLOW}📈 Ver dashboard completo:${NC}"
echo -e "curl -s \"http://localhost:8006/api/admin/reports/domain/1/dashboard\" -H \"Authorization: Bearer \$TOKEN\" | jq '.data.kpis'"
echo ""

echo -e "${CYAN}💡 Dica: Use diferentes arquivos de docs/daily_reports/ para testar múltiplos dias!${NC}"
