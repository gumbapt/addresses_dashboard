# 🧪 Guia de Testes Feature

## ⚠️ Problema Original
Os testes Feature crashavam com **Signal 11 (Segmentation Fault)** quando executados todos juntos via `php artisan test --testsuite=Feature`.

## ✅ Solução Implementada
Criamos scripts que executam os testes **individualmente** ou em **pequenos grupos**, evitando o problema do Signal 11.

---

## 🚀 Como Executar Testes Feature Agora

### **Método 1: Teste Específico (Recomendado)**
```bash
./test-feature.sh nome_do_teste
```

**Exemplos:**
```bash
# Teste específico de report
./test-feature.sh admin_can_get_specific_report

# Teste de listagem de admins
./test-feature.sh admin_can_list_reports

# Teste de criação de domínio
./test-feature.sh super_admin_can_create_domain
```

### **Método 2: Todos os Testes Feature (Arquivo por Arquivo)**
```bash
./test-feature.sh
```
Executa todos os arquivos de teste Feature sequencialmente com pausa entre cada um.

### **Método 3: Todos os Testes Individuais com Relatório**
```bash
./run-feature-tests.sh
```
Executa cada teste individualmente e mostra um relatório completo no final.

---

## 📋 Lista de Testes Feature Disponíveis

### 🔐 **Admin Tests**
```bash
./test-feature.sh super_admin_can_list_all_admins
./test-feature.sh admin_with_admin_read_can_list_all_admins
./test-feature.sh admin_without_admin_read_cannot_list_admins
./test-feature.sh super_admin_can_create_admin
./test-feature.sh can_paginate_admins_with_custom_per_page
./test-feature.sh can_search_admins_by_name
./test-feature.sh can_search_admins_by_email
./test-feature.sh can_filter_admins_by_active_status
```

### 🌐 **Domain Management Tests**
```bash
./test-feature.sh super_admin_can_list_domains
./test-feature.sh can_paginate_domains
./test-feature.sh can_search_domains_by_name
./test-feature.sh super_admin_can_create_domain
./test-feature.sh super_admin_can_update_domain
./test-feature.sh super_admin_can_delete_domain
./test-feature.sh super_admin_can_regenerate_api_key
./test-feature.sh super_admin_can_get_domain_by_id
```

### 🔑 **Permission Tests**
```bash
./test-feature.sh super_admin_can_list_all_permissions
./test-feature.sh admin_with_role_manage_can_list_all_permissions
./test-feature.sh admin_without_role_manage_cannot_list_permissions
```

### 👥 **Role Tests**
```bash
./test-feature.sh an_admin_can_list_roles
./test-feature.sh an_admin_can_create_a_role
./test-feature.sh an_admin_can_create_a_role_with_permissions
./test-feature.sh admin_cannot_create_role_without_create_permission
./test-feature.sh an_admin_can_update_a_role_when_has_update_permission
./test-feature.sh an_admin_can_delete_a_role_when_has_delete_permission
```

### 📊 **Report Tests** (Localização: `tests/Feature/Report/`)
```bash
# Todos os testes do ReportManagementTest
./test-feature.sh admin_can_list_reports
./test-feature.sh admin_can_filter_reports_by_domain
./test-feature.sh admin_can_filter_reports_by_status
./test-feature.sh admin_can_filter_reports_by_date_range
./test-feature.sh admin_can_paginate_reports
./test-feature.sh admin_can_get_specific_report
./test-feature.sh admin_cannot_get_nonexistent_report
./test-feature.sh admin_can_get_recent_reports

# Todos os testes do ReportSubmissionTest
./test-feature.sh can_submit_valid_report_with_bearer_token
./test-feature.sh can_submit_valid_report_with_api_key_header
./test-feature.sh cannot_submit_report_without_api_key
./test-feature.sh cannot_submit_report_with_invalid_api_key
```

---

## 💡 Dicas de Uso

### **Executar por Arquivo**
```bash
# Executa todos os testes do arquivo AdminsTest.php
docker-compose exec app php artisan test tests/Feature/Admin/AdminsTest.php

# Executa todos os testes do arquivo ReportManagementTest.php
docker-compose exec app php artisan test tests/Feature/ReportManagementTest.php
```

### **Ver Apenas Resultados**
```bash
# Redireciona warnings para focar nos resultados
./test-feature.sh admin_can_get_specific_report 2>/dev/null | grep -E "PASS|FAIL|✓|⨯"
```

### **Executar Múltiplos Testes Específicos**
```bash
# Crie um loop simples
for test in admin_can_get_specific_report admin_can_list_reports; do
    ./test-feature.sh "$test"
done
```

---

## 🔧 Solução de Problemas

### **Se um teste específico falhar:**
1. Execute-o novamente isoladamente:
   ```bash
   ./test-feature.sh nome_do_teste
   ```

2. Verifique os logs de erro completos

3. Teste manualmente via Docker:
   ```bash
   docker-compose exec app php artisan test --filter="nome_do_teste"
   ```

### **Se o Signal 11 ainda ocorrer:**
- ✅ **Solução**: Use os scripts fornecidos (testam individualmente)
- ✅ **Alternativa**: Execute arquivo por arquivo
- ❌ **Evite**: `php artisan test --testsuite=Feature` (causa Signal 11)

---

## 📊 Estrutura dos Scripts

### **test-feature.sh**
- ✅ Rápido e simples
- ✅ Executa testes específicos por nome
- ✅ Ou todos os arquivos Feature sequencialmente

### **run-feature-tests.sh**
- ✅ Executa CADA teste individualmente
- ✅ Mostra relatório detalhado
- ✅ Contabiliza sucessos e falhas

### **run-all-tests.sh**
- ✅ Executa TODOS os testes (Unit + Integration + Feature)
- ✅ Mais completo
- ✅ Melhor para CI/CD

---

## ✅ Exemplos de Uso Real

### **Desenvolvimento Diário**
```bash
# Teste o que você está desenvolvendo
./test-feature.sh admin_can_get_specific_report
```

### **Antes de Commit**
```bash
# Execute todos os testes Feature
./run-feature-tests.sh
```

### **CI/CD Pipeline**
```bash
# Execute todos os testes do projeto
./run-all-tests.sh
```

---

## 🎉 Resultado

**AGORA VOCÊ PODE EXECUTAR TODOS OS TESTES FEATURE!**

- ✅ Individual: `./test-feature.sh nome_teste`
- ✅ Todos: `./run-feature-tests.sh`
- ✅ Sem Signal 11
- ✅ Relatórios claros

---

*Última atualização: Outubro 2024*
*Status: Totalmente Operacional ✅*
