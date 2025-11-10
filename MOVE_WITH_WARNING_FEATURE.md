# ⚠️ Opção B Implementada: Mover com Aviso

## ✅ Comportamento Implementado

Quando você adiciona domínios que já estão em outros grupos, o sistema:
1. ✅ **Permite a movimentação**
2. ✅ **Detecta os domínios que serão movidos**
3. ✅ **Retorna informações detalhadas** sobre a origem
4. ✅ **Mensagem clara** diferenciando "added" vs "moved"
5. ✅ **Limite considera apenas domínios novos** (não os movidos)

---

## 📡 Nova Estrutura de Resposta

### **Exemplo 1: Todos Domínios Novos**
```bash
POST /api/admin/domain-groups/1/domains
{"domain_ids": [1, 2, 3]}
```

**Response:**
```json
{
  "success": true,
  "message": "3 domain(s) added to group 'Production' successfully.",
  "data": {
    "group_id": 1,
    "group_name": "Production",
    "domains_added": 3,
    "domains_moved": 0,
    "moved_from": [],
    "total_updated": 3,
    "total_requested": 3,
    "total_domains": 5
  }
}
```

---

### **Exemplo 2: Todos Domínios Movidos**
```bash
POST /api/admin/domain-groups/2/domains
{"domain_ids": [1, 2, 3]}  # Já estão no grupo 1
```

**Response:**
```json
{
  "success": true,
  "message": "3 domain(s) moved from other groups to group 'Testing' successfully.",
  "data": {
    "group_id": 2,
    "group_name": "Testing",
    "domains_added": 0,
    "domains_moved": 3,
    "moved_from": [
      {
        "domain_id": 1,
        "domain_name": "zip.50g.io",
        "current_group_id": 1,
        "current_group_name": "Production"
      },
      {
        "domain_id": 2,
        "domain_name": "example.com",
        "current_group_id": 1,
        "current_group_name": "Production"
      },
      {
        "domain_id": 3,
        "domain_name": "test.com",
        "current_group_id": 1,
        "current_group_name": "Production"
      }
    ],
    "total_updated": 3,
    "total_requested": 3,
    "total_domains": 3
  }
}
```

---

### **Exemplo 3: Mix de Novos e Movidos**
```bash
POST /api/admin/domain-groups/2/domains
{"domain_ids": [1, 2, 3, 4, 5]}
# 1,2,3 estão no grupo 1
# 4,5 não têm grupo (novos)
```

**Response:**
```json
{
  "success": true,
  "message": "2 domain(s) added, 3 domain(s) moved from other groups to group 'Testing' successfully.",
  "data": {
    "group_id": 2,
    "group_name": "Testing",
    "domains_added": 2,
    "domains_moved": 3,
    "moved_from": [
      {
        "domain_id": 1,
        "domain_name": "zip.50g.io",
        "current_group_id": 1,
        "current_group_name": "Production"
      },
      {
        "domain_id": 2,
        "domain_name": "example.com",
        "current_group_id": 1,
        "current_group_name": "Production"
      },
      {
        "domain_id": 3,
        "domain_name": "test.com",
        "current_group_id": 1,
        "current_group_name": "Production"
      }
    ],
    "total_updated": 5,
    "total_requested": 5,
    "total_domains": 5
  }
}
```

---

## 🧮 Lógica de Limite

### **Limite considera APENAS domínios novos:**

**Cenário:**
- Grupo tem limite de **5 domínios**
- Já possui **4 domínios**
- Tenta adicionar: **3 movidos + 2 novos**

**Validação:**
```
currentCount = 4
newCount = 2 (apenas os novos)
totalAfterAdd = 4 + 2 = 6

6 > 5 → ❌ ERRO: "only has 1 available slots"
```

---

### **Domínios movidos não contam:**

**Cenário:**
- Grupo tem limite de **5 domínios**
- Já possui **4 domínios**  
- Tenta adicionar: **3 movidos + 1 novo**

**Validação:**
```
currentCount = 4
newCount = 1 (apenas o novo)
totalAfterAdd = 4 + 1 = 5

5 <= 5 → ✅ SUCCESS
```

---

## 🆕 Novo Método de Repository

```php
/**
 * Get domains that are already in other groups
 * 
 * @param array $domainIds
 * @param int $excludeGroupId Current group to exclude from check
 * @return array [['domain_id' => int, 'domain_name' => string, 'current_group_id' => int, 'current_group_name' => string]]
 */
public function getDomainsInOtherGroups(array $domainIds, int $excludeGroupId): array
```

---

## 🎯 Casos de Uso Frontend

### **1. Mostrar Aviso ao Usuário:**
```tsx
const addDomains = async (groupId, domainIds) => {
  const response = await api.post(`/admin/domain-groups/${groupId}/domains`, {
    domain_ids: domainIds
  });
  
  const { data } = response.data;
  
  // Mostrar aviso se houver domínios movidos
  if (data.domains_moved > 0) {
    const movedNames = data.moved_from.map(d => d.domain_name).join(', ');
    const sourceGroups = [...new Set(data.moved_from.map(d => d.current_group_name))].join(', ');
    
    showWarning(
      `${data.domains_moved} domain(s) were moved from: ${sourceGroups}\n` +
      `Domains: ${movedNames}`
    );
  }
  
  // Mostrar sucesso
  showSuccess(response.data.message);
};
```

---

### **2. Confirmação Antes de Mover:**
```tsx
const addDomainsWithConfirmation = async (groupId, domainIds) => {
  // Verificar se algum domínio já está em outro grupo
  const domainsInfo = await api.get(`/admin/domains`, {
    params: { ids: domainIds.join(',') }
  });
  
  const domainsInOtherGroups = domainsInfo.data.filter(
    d => d.domain_group_id && d.domain_group_id !== groupId
  );
  
  if (domainsInOtherGroups.length > 0) {
    const confirmed = await confirmDialog({
      title: 'Move Domains?',
      message: `${domainsInOtherGroups.length} domain(s) will be moved from other groups. Continue?`,
      details: domainsInOtherGroups.map(d => 
        `${d.name} (from ${d.domainGroup?.name})`
      )
    });
    
    if (!confirmed) return;
  }
  
  // Prosseguir com a adição
  await addDomains(groupId, domainIds);
};
```

---

### **3. Log de Movimentação:**
```tsx
const addDomainsWithLog = async (groupId, domainIds) => {
  const response = await api.post(`/admin/domain-groups/${groupId}/domains`, {
    domain_ids: domainIds
  });
  
  const { data } = response.data;
  
  // Registrar movimentação no histórico
  if (data.domains_moved > 0) {
    data.moved_from.forEach(domain => {
      auditLog.create({
        action: 'domain_moved',
        domain_id: domain.domain_id,
        from_group: domain.current_group_name,
        to_group: data.group_name,
        timestamp: new Date()
      });
    });
  }
};
```

---

## 📊 Testes Criados

### **Unit Tests (4 novos):**
```
✅ detects_when_domains_are_already_in_other_groups
✅ all_domains_are_new_when_none_in_other_groups
✅ all_domains_are_moved_when_all_in_other_groups
✅ limit_only_considers_new_domains_not_moved_ones
```

### **Feature Tests (5 novos):**
```
✅ warns_when_moving_domains_from_another_group
✅ distinguishes_between_added_and_moved_domains
✅ moved_domains_do_not_count_against_group_limit
✅ fails_when_new_domains_exceed_limit_even_with_moved_ones
✅ shows_source_group_names_in_moved_from_info
```

---

## 🎉 Estatísticas Finais

### **Testes Totais:**
```
Unit Tests:    63 (59 antigos + 4 novos)
Feature Tests: 30 (25 antigos + 5 novos)
─────────────────────────────────────────
TOTAL:         93 testes ✅ (100% passing)
```

### **Arquivos Modificados:**
```
✅ app/Domain/Repositories/DomainGroupRepositoryInterface.php (+ 1 método)
✅ app/Infrastructure/Repositories/DomainGroupRepository.php (+ 1 método)
✅ app/Application/UseCases/DomainGroup/AddDomainsToGroupUseCase.php (atualizado)
✅ app/Http/Controllers/Api/Admin/DomainGroupController.php (atualizado)
✅ tests/Unit/DomainGroupMoveDomainsTest.php (novo - 4 testes)
✅ tests/Feature/Admin/DomainGroupMoveWarningTest.php (novo - 5 testes)
✅ tests/Feature/Admin/DomainGroupBatchOperationsTest.php (atualizado)
```

---

## ✅ Vantagens da Opção B

1. ✅ **Transparência Total** - Usuário sabe exatamente o que aconteceu
2. ✅ **Histórico Claro** - `moved_from` mostra origem dos domínios
3. ✅ **Flexibilidade** - Permite mover facilmente
4. ✅ **Segurança** - Informação detalhada previne erros
5. ✅ **Auditoria** - Possível rastrear todas as movimentações

---

## 🆚 Comparação com Outras Opções

| Feature | Opção A (Bloquear) | **Opção B (Avisar)** | Opção C (Silencioso) |
|---------|-------------------|----------------------|----------------------|
| Permite mover | ❌ Não | ✅ Sim | ✅ Sim |
| Informa origem | ✅ Sim | ✅ Sim | ❌ Não |
| Requer confirmação extra | ✅ Sim (manual) | ⚠️ Opcional | ❌ Não |
| Simplicidade | Média | Média | Alta |
| Segurança | Alta | Média-Alta | Baixa |
| Flexibilidade | Baixa | Alta | Alta |
| **Recomendação** | Sistemas críticos | ✅ **IMPLEMENTADO** | Não recomendado |

---

## 💡 Próximos Passos (Opcional)

### **Melhorias Futuras:**
1. **Histórico de Movimentações:**
   - Tabela `domain_movements` para audit trail
   - Dashboard de movimentações recentes

2. **Confirmação no Frontend:**
   - Modal de confirmação quando detectar movimentação
   - Preview das mudanças antes de aplicar

3. **Notificações:**
   - Email/Slack quando domínios são movidos
   - Relatório semanal de movimentações

4. **Rollback:**
   - Opção de "desfazer" movimentação
   - Histórico com restore point

---

**Data:** Novembro 10, 2025  
**Versão:** 2.0 (Opção B)  
**Status:** ✅ Implementado e Testado  
**Cobertura:** 93 testes - 100% passando

