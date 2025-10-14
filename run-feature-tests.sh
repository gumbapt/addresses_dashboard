#!/bin/bash

echo "🧪 Executando Testes Feature de forma controlada..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Contadores
total_tests=0
passed_tests=0
failed_tests=0

# Função para executar um teste individualmente
run_single_test() {
    local test_name="$1"
    local test_filter="$2"
    
    echo -e "\n${BLUE}▶ Executando: $test_name${NC}"
    
    if docker-compose exec -T app php artisan test --filter="$test_filter" 2>&1 | grep -q "PASS"; then
        echo -e "${GREEN}✅ $test_name - PASSOU${NC}"
        ((passed_tests++))
        return 0
    else
        echo -e "${RED}❌ $test_name - FALHOU${NC}"
        ((failed_tests++))
        return 1
    fi
    ((total_tests++))
}

echo -e "\n${YELLOW}==================== TESTES FEATURE - ADMIN ====================${NC}"

# Testes de Admin
run_single_test "Super admin can list all admins" "super_admin_can_list_all_admins"
run_single_test "Admin with read permission" "admin_with_admin_read_can_list_all_admins"
run_single_test "Admin without read cannot list" "admin_without_admin_read_cannot_list_admins"
run_single_test "Super admin can create admin" "super_admin_can_create_admin"
run_single_test "Admin with create can create" "admin_with_admin_create_can_create_admin"
run_single_test "Admin without create cannot" "admin_without_admin_create_cannot_create_admin"
run_single_test "Pagination tests" "can_paginate_admins_with_custom_per_page"
run_single_test "Search by name" "can_search_admins_by_name"
run_single_test "Search by email" "can_search_admins_by_email"
run_single_test "Filter by active status" "can_filter_admins_by_active_status"

echo -e "\n${YELLOW}==================== TESTES FEATURE - DOMAIN ====================${NC}"

# Testes de Domain
run_single_test "Super admin can list domains" "super_admin_can_list_domains"
run_single_test "Can paginate domains" "can_paginate_domains"
run_single_test "Can search domains by name" "can_search_domains_by_name"
run_single_test "Can filter domains by active" "can_filter_domains_by_active_status"
run_single_test "Super admin can create domain" "super_admin_can_create_domain"
run_single_test "Super admin can update domain" "super_admin_can_update_domain"
run_single_test "Super admin can delete domain" "super_admin_can_delete_domain"
run_single_test "Can regenerate API key" "super_admin_can_regenerate_api_key"
run_single_test "Can get domain by ID" "super_admin_can_get_domain_by_id"

echo -e "\n${YELLOW}==================== TESTES FEATURE - PERMISSION ====================${NC}"

# Testes de Permission
run_single_test "Super admin can list permissions" "super_admin_can_list_all_permissions"
run_single_test "Admin with role manage" "admin_with_role_manage_can_list_all_permissions"
run_single_test "Admin without role manage" "admin_without_role_manage_cannot_list_permissions"
run_single_test "Unauthenticated cannot access" "unauthenticated_user_cannot_access_permissions"

echo -e "\n${YELLOW}==================== TESTES FEATURE - ROLE ====================${NC}"

# Testes de Role
run_single_test "Admin can list roles" "an_admin_can_list_roles"
run_single_test "Admin can create role" "an_admin_can_create_a_role"
run_single_test "Admin can create role with perms" "an_admin_can_create_a_role_with_permissions"
run_single_test "Cannot create without permission" "admin_cannot_create_role_without_create_permission"
run_single_test "Cannot list without permission" "admin_cannot_list_roles_without_read_permission"
run_single_test "Admin can update role" "an_admin_can_update_a_role_when_has_update_permission"
run_single_test "Admin can delete role" "an_admin_can_delete_a_role_when_has_delete_permission"
run_single_test "Can update permissions" "an_admin_can_update_permissions_when_has_manage_permission"

echo -e "\n${YELLOW}==================== TESTES FEATURE - REPORT ====================${NC}"

# Testes de Report
run_single_test "Admin can list reports" "admin_can_list_reports"
run_single_test "Admin can filter by domain" "admin_can_filter_reports_by_domain"
run_single_test "Admin can filter by status" "admin_can_filter_reports_by_status"
run_single_test "Admin can filter by date range" "admin_can_filter_reports_by_date_range"
run_single_test "Admin can paginate reports" "admin_can_paginate_reports"
run_single_test "Admin can get specific report" "admin_can_get_specific_report"
run_single_test "Cannot get nonexistent report" "admin_cannot_get_nonexistent_report"
run_single_test "Can get recent reports" "admin_can_get_recent_reports"

echo -e "\n${YELLOW}==================== TESTES FEATURE - OUTROS ====================${NC}"

# Testes de Submission
run_single_test "Report Submission Tests" "ReportSubmissionTest"

# Resumo
total_tests=$((passed_tests + failed_tests))
echo -e "\n${BLUE}==================== RESUMO ====================${NC}"
echo -e "${GREEN}✅ Testes Passados: $passed_tests${NC}"
echo -e "${RED}❌ Testes Falhados: $failed_tests${NC}"
echo -e "${BLUE}📊 Total Executado: $total_tests${NC}"

if [ $failed_tests -eq 0 ]; then
    echo -e "\n${GREEN}🎉 TODOS OS TESTES FEATURE PASSARAM!${NC}"
    exit 0
else
    echo -e "\n${YELLOW}⚠️  Alguns testes falharam. Revise os logs acima.${NC}"
    exit 1
fi
