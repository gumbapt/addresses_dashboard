#!/bin/bash

# Script para submeter o newdata.json para a API simulando o serviço 50gig
# Uso: ./submit-test-report.sh [domain_name] [api_key]

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configurações
JSON_FILE="docs/newdata.json"
API_URL="${API_URL:-http://localhost:8006}"
ENDPOINT="${API_URL}/api/reports/submit"

echo -e "${BLUE}🚀 Submitting Test Report to API...${NC}\n"

# Verificar se o arquivo JSON existe
if [ ! -f "$JSON_FILE" ]; then
    echo -e "${RED}❌ Arquivo não encontrado: $JSON_FILE${NC}"
    exit 1
fi

echo -e "${BLUE}📄 Arquivo: $JSON_FILE${NC}"
echo -e "${BLUE}📦 Tamanho: $(wc -c < "$JSON_FILE") bytes${NC}\n"

# Obter domain e API key
if [ -n "$1" ] && [ -n "$2" ]; then
    # Usar argumentos fornecidos
    DOMAIN_NAME="$1"
    API_KEY="$2"
    echo -e "${BLUE}🌐 Usando domínio fornecido: $DOMAIN_NAME${NC}"
    echo -e "${BLUE}🔑 API Key fornecida${NC}\n"
else
    # Buscar domínio do banco de dados
    echo -e "${YELLOW}🔍 Buscando domínio no banco de dados...${NC}"
    
    DOMAIN_INFO=$(docker-compose exec -T app php artisan tinker --execute="
        \$domain = \App\Models\Domain::where('is_active', true)->first();
        if (\$domain) {
            echo \$domain->name . '|' . \$domain->api_key;
        } else {
            echo 'NO_DOMAIN';
        }
    " 2>/dev/null | tail -1)
    
    if [ "$DOMAIN_INFO" = "NO_DOMAIN" ] || [ -z "$DOMAIN_INFO" ]; then
        echo -e "${RED}❌ Nenhum domínio ativo encontrado no banco de dados${NC}"
        echo -e "${YELLOW}💡 Crie um domínio primeiro ou forneça os parâmetros:${NC}"
        echo -e "${YELLOW}   ./submit-test-report.sh domain_name api_key${NC}"
        exit 1
    fi
    
    DOMAIN_NAME=$(echo "$DOMAIN_INFO" | cut -d'|' -f1)
    API_KEY=$(echo "$DOMAIN_INFO" | cut -d'|' -f2)
    
    echo -e "${GREEN}✅ Domínio encontrado: $DOMAIN_NAME${NC}"
    echo -e "${GREEN}🔑 API Key: ${API_KEY:0:20}...${NC}\n"
fi

# Fazer requisição
echo -e "${BLUE}📡 Endpoint: $ENDPOINT${NC}"
echo -e "${BLUE}⏳ Enviando requisição...${NC}\n"

RESPONSE=$(curl -X POST "$ENDPOINT" \
    -H "X-API-Key: $API_KEY" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d @"$JSON_FILE" \
    -w "\n%{http_code}" \
    -s)

# Separar body e status code
HTTP_BODY=$(echo "$RESPONSE" | sed '$d')
HTTP_CODE=$(echo "$RESPONSE" | tail -1)

echo -e "${BLUE}📊 HTTP Status: $HTTP_CODE${NC}\n"

# Processar resposta
if [ "$HTTP_CODE" -ge 200 ] && [ "$HTTP_CODE" -lt 300 ]; then
    echo -e "${GREEN}✅ Report submitted successfully!${NC}\n"
    echo -e "${BLUE}Response:${NC}"
    echo "$HTTP_BODY" | jq '.' 2>/dev/null || echo "$HTTP_BODY"
    
    # Extrair informações importantes
    REPORT_ID=$(echo "$HTTP_BODY" | jq -r '.data.report_id // empty' 2>/dev/null)
    REPORT_DATE=$(echo "$HTTP_BODY" | jq -r '.data.report_date // empty' 2>/dev/null)
    STATUS=$(echo "$HTTP_BODY" | jq -r '.data.status // empty' 2>/dev/null)
    
    if [ -n "$REPORT_ID" ]; then
        echo -e "\n${GREEN}🎉 Report ID: $REPORT_ID${NC}"
    fi
    
    if [ -n "$REPORT_DATE" ]; then
        echo -e "${GREEN}📅 Report Date: $REPORT_DATE${NC}"
    fi
    
    if [ -n "$STATUS" ]; then
        echo -e "${GREEN}📊 Status: $STATUS${NC}"
    fi
    
    exit 0
else
    echo -e "${RED}❌ Request failed!${NC}\n"
    echo -e "${YELLOW}Response:${NC}"
    echo "$HTTP_BODY" | jq '.' 2>/dev/null || echo "$HTTP_BODY"
    
    # Mostrar erros de validação se existirem
    ERRORS=$(echo "$HTTP_BODY" | jq -r '.errors // empty' 2>/dev/null)
    if [ -n "$ERRORS" ] && [ "$ERRORS" != "null" ]; then
        echo -e "\n${RED}Validation Errors:${NC}"
        echo "$HTTP_BODY" | jq -r '.errors | to_entries[] | "  • \(.key): \(.value | if type == "array" then join(", ") else . end)"' 2>/dev/null
    fi
    
    exit 1
fi

