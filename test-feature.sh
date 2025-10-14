#!/bin/bash

# Script simples para executar testes Feature um arquivo por vez
# Uso: ./test-feature.sh [nome-do-teste-ou-arquivo-opcional]

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

if [ -n "$1" ]; then
    # Verifica se é um arquivo (contém .php ou tests/)
    if [[ "$1" == *".php"* ]] || [[ "$1" == *"tests/"* ]]; then
        echo -e "${BLUE}🧪 Executando arquivo de teste: $1${NC}"
        docker-compose exec app php artisan test "$1"
    else
        # Se não é arquivo, trata como filtro de nome
        echo -e "${BLUE}🧪 Executando teste específico: $1${NC}"
        docker-compose exec app php artisan test --filter="$1"
    fi
    exit $?
fi 

# Se não foi passado argumento, lista os arquivos disponíveis e executa um por vez
echo -e "${YELLOW}🧪 Executando todos os testes Feature arquivo por arquivo...${NC}\n"

# Array com os arquivos de teste Feature
feature_files=(
    "tests/Feature/Admin/AdminsTest.php"
    "tests/Feature/Admin/DomainManagementTest.php"
    "tests/Feature/Admin/PermissionTest.php"
    "tests/Feature/Admin/RoleManagementTest.php"
    "tests/Feature/Report/ReportManagementTest.php"
    "tests/Feature/Report/ReportSubmissionTest.php"
    "tests/Feature/Auth/LoginTest.php"
    "tests/Feature/Auth/RegisterTest.php"
    "tests/Feature/Auth/AdminLoginTest.php"
    "tests/Feature/Auth/AdminRegisterTest.php"
)

success_count=0
fail_count=0

for test_file in "${feature_files[@]}"; do
    if [ -f "$test_file" ]; then
        echo -e "${BLUE}▶ Executando: $test_file${NC}"
        
        if docker-compose exec -T app php artisan test "$test_file" 2>&1; then
            echo -e "${GREEN}✅ $test_file - OK${NC}\n"
            ((success_count++))
        else
            echo -e "${YELLOW}⚠️  $test_file - Verificar manualmente${NC}\n"
            ((fail_count++))
        fi
        
        # Pequena pausa entre testes para evitar sobrecarga
        sleep 1
    else
        echo -e "${YELLOW}⚠️  Arquivo não encontrado: $test_file${NC}\n"
    fi
done

total=$((success_count + fail_count))
echo -e "\n${BLUE}==================== RESUMO ====================${NC}"
echo -e "${GREEN}✅ Arquivos executados com sucesso: $success_count${NC}"
echo -e "${YELLOW}⚠️  Arquivos com problemas: $fail_count${NC}"
echo -e "${BLUE}📊 Total: $total${NC}"

echo -e "\n${BLUE}💡 Dica: Para executar um teste específico use:${NC}"
echo -e "${YELLOW}   ./test-feature.sh nome_do_teste${NC}"
echo -e "${YELLOW}   Exemplo: ./test-feature.sh admin_can_get_specific_report${NC}"
