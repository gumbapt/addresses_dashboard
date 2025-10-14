# 📊 Testes de Report - Feature Tests

## 📁 Estrutura

Esta pasta contém todos os testes Feature relacionados ao sistema de relatórios (Reports):

- **ReportManagementTest.php** - Testes de gerenciamento de relatórios pela API admin
- **ReportSubmissionTest.php** - Testes de submissão de relatórios via API key

## ✅ Status dos Testes

| Arquivo | Testes | Status |
|---------|--------|--------|
| `ReportManagementTest.php` | 11 | ✅ **Todos funcionando** |
| `ReportSubmissionTest.php` | 13 | ✅ **Todos funcionando** |

## 🚀 Como Executar

### **Executar teste específico:**
```bash
# Da raiz do projeto
./test-feature.sh admin_can_get_specific_report

# Ou usando artisan diretamente
docker-compose exec app php artisan test --filter="admin_can_get_specific_report"
```

### **Executar um arquivo completo:**
```bash
# ReportManagementTest
docker-compose exec app php artisan test tests/Feature/Report/ReportManagementTest.php

# ReportSubmissionTest  
docker-compose exec app php artisan test tests/Feature/Report/ReportSubmissionTest.php
```

⚠️ **IMPORTANTE**: Executar múltiplos testes Feature juntos pode causar Signal 11. Execute individualmente ou use os scripts fornecidos.

## 📋 Lista de Testes

### **ReportManagementTest.php**
1. ✅ `admin_can_list_reports` - Lista todos os relatórios
2. ✅ `admin_can_filter_reports_by_domain` - Filtra por domínio
3. ✅ `admin_can_filter_reports_by_status` - Filtra por status
4. ✅ `admin_can_filter_reports_by_date_range` - Filtra por período
5. ✅ `admin_can_paginate_reports` - Paginação de relatórios
6. ✅ `admin_can_get_specific_report` - Busca relatório específico
7. ✅ `admin_cannot_get_nonexistent_report` - Valida relatório inexistente
8. ✅ `admin_can_get_recent_reports` - Lista relatórios recentes
9. ✅ `unauthenticated_user_cannot_access_admin_reports` - Valida autenticação
10. ✅ `non_admin_user_cannot_access_admin_reports` - Valida autorização
11. ✅ `pagination_limits_per_page` - Valida limites de paginação
12. ✅ `combines_multiple_filters` - Testa múltiplos filtros juntos

### **ReportSubmissionTest.php**
1. ✅ `can_submit_valid_report_with_bearer_token` - Submissão com Bearer token
2. ✅ `can_submit_valid_report_with_api_key_header` - Submissão com X-API-Key
3. ✅ `cannot_submit_report_without_api_key` - Valida API key obrigatória
4. ✅ `cannot_submit_report_with_invalid_api_key` - Valida API key inválida
5. ✅ `cannot_submit_report_with_inactive_domain` - Valida domínio ativo
6. ✅ `cannot_submit_report_with_domain_mismatch` - Valida match de domínio
7. ✅ `cannot_submit_report_without_required_source_fields` - Valida source
8. ✅ `cannot_submit_report_without_required_metadata_fields` - Valida metadata
9. ✅ `cannot_submit_report_with_invalid_date_format` - Valida formato de data
10. ✅ `can_submit_report_with_minimal_required_data` - Dados mínimos
11. ✅ `validates_provider_data_structure` - Valida estrutura de providers
12. ✅ `validates_geographic_data_structure` - Valida estrutura geográfica
13. ✅ `validates_numeric_fields` - Valida campos numéricos

## 🎯 Exemplos de Uso

### **Teste específico que foi corrigido hoje:**
```bash
./test-feature.sh admin_can_get_specific_report
```

Saída esperada:
```
PASS  Tests\Feature\Report\ReportManagementTest
✓ admin can get specific report (9 assertions)
```

### **Executar todos os testes de Report (use script):**
```bash
# Método 1: Via script personalizado
./test-feature.sh

# Método 2: Executar individualmente
for test in admin_can_list_reports admin_can_get_specific_report; do
    docker-compose exec app php artisan test --filter="$test"
done
```

## 🔧 Configurações Importantes

Os testes dependem de:
- ✅ RoleFactory com campo `description`
- ✅ Guard 'admin' configurado em `config/auth.php`
- ✅ Middleware 'admin.auth' nas rotas
- ✅ Campos pivot (`assigned_at`, `assigned_by`) ao anexar roles

## 💡 Dicas

1. **Sempre execute testes Feature individualmente** para evitar Signal 11
2. **Use `./test-feature.sh nome_teste`** para facilitar a execução
3. **Verifique os logs** se um teste falhar inesperadamente
4. **Memory limit está em 2GB** - otimizado para performance

---

*Última atualização: Outubro 2024*
*Status: ✅ Totalmente Operacional*

