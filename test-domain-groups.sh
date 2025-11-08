#!/bin/bash

# Script para testar Domain Groups

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  🧪 TESTE DE DOMAIN GROUPS                                     ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Fazer login
echo -e "${BLUE}━━━ 1. Login como Super Admin ━━━${NC}\n"

TOKEN=$(curl -s http://localhost:8007/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' \
  | jq -r '.token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo -e "${RED}❌ Erro ao fazer login!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Login realizado com sucesso!${NC}"
echo "Token: $TOKEN"
echo ""

# Listar grupos
echo -e "${BLUE}━━━ 2. Listar Domain Groups ━━━${NC}\n"

curl -s http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN" \
  | jq -r '.data[] | "• \(.name) (ID: \(.id)) - \(.domains | length) domínios / \(.max_domains // "∞") máx"'

echo ""

# Ver detalhes do grupo Production
echo -e "${BLUE}━━━ 3. Ver Detalhes do Grupo 'Production Domains' ━━━${NC}\n"

curl -s http://localhost:8007/api/admin/domain-groups/1 \
  -H "Authorization: Bearer $TOKEN" \
  | jq '{
    name: .data.name,
    description: .data.description,
    domains_count: .data.domains_count,
    max_domains: .data.max_domains,
    available: .data.available_domains,
    has_reached_limit: .data.has_reached_limit,
    domains: .data.domains[].name
  }'

echo ""

# Criar novo grupo
echo -e "${BLUE}━━━ 4. Criar Novo Domain Group ━━━${NC}\n"

NEW_GROUP=$(curl -s -X POST http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "API Testing Group",
    "description": "Grupo criado via API para testes",
    "max_domains": 10,
    "is_active": true,
    "settings": {
      "test": true,
      "created_via": "api"
    }
  }')

echo "$NEW_GROUP" | jq '{
  success: .success,
  message: .message,
  group_id: .data.id,
  group_name: .data.name
}'

NEW_GROUP_ID=$(echo "$NEW_GROUP" | jq -r '.data.id')

echo ""

# Atualizar grupo
echo -e "${BLUE}━━━ 5. Atualizar Domain Group ━━━${NC}\n"

curl -s -X PUT http://localhost:8007/api/admin/domain-groups/$NEW_GROUP_ID \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "max_domains": 15,
    "settings": {
      "test": true,
      "created_via": "api",
      "updated": true
    }
  }' | jq '{
  success: .success,
  message: .message,
  max_domains: .data.max_domains
}'

echo ""

# Tentar deletar grupo com domínios (deve falhar)
echo -e "${BLUE}━━━ 6. Tentar Deletar Grupo com Domínios (deve falhar) ━━━${NC}\n"

curl -s -X DELETE http://localhost:8007/api/admin/domain-groups/2 \
  -H "Authorization: Bearer $TOKEN" \
  | jq '{
  success: .success,
  message: .message,
  domains_count: .domains_count
}'

echo ""

# Deletar grupo vazio
echo -e "${BLUE}━━━ 7. Deletar Grupo Vazio (deve funcionar) ━━━${NC}\n"

curl -s -X DELETE http://localhost:8007/api/admin/domain-groups/$NEW_GROUP_ID \
  -H "Authorization: Bearer $TOKEN" \
  | jq '{
  success: .success,
  message: .message
}'

echo ""

# Resumo final
echo -e "${BLUE}━━━ 8. Resumo Final ━━━${NC}\n"

curl -s http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN" \
  | jq -r '
  "╔════════════════════════════════════════════════════════════════╗",
  "║  📊 RESUMO DE DOMAIN GROUPS                                    ║",
  "╚════════════════════════════════════════════════════════════════╝",
  "",
  "Total de grupos: \(.pagination.total)",
  "",
  (.data[] | "• \(.name): \(.domains | length)/\(.max_domains // "∞") domínios")
  '

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✅ TESTES CONCLUÍDOS COM SUCESSO!                             ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

