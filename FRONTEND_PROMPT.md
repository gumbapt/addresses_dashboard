# 🎨 Frontend Implementation Guide - Domain Groups

## 📋 O Que Você Precisa Implementar

Você está implementando um sistema de **Domain Groups** para organizar domínios. O backend já está 100% pronto e testado.

---

## 🚀 Quick Start - 3 Telas Principais

### **1. Lista de Domain Groups**
- Mostrar todos os grupos
- Botão "Create Group" (apenas Super Admin)
- Cards com contador de domínios

### **2. Formulário de Criar/Editar Grupo**
- Nome do grupo
- Descrição (opcional)
- Limite de domínios (opcional, deixar vazio = ilimitado)
- Toggle "Active"

### **3. Atualizar Formulário de Domain**
- Adicionar dropdown "Select Group (optional)"
- Mostrar badge do grupo na listagem

---

## 📡 APIs Disponíveis

### **Base URL:** `http://localhost:8007/api/admin`

### **Authentication:**
Todas as rotas precisam de:
```
Authorization: Bearer {seu_token_aqui}
```

---

## 📊 1. Domain Groups CRUD

### **GET /domain-groups** - Listar Grupos
```javascript
const fetchGroups = async () => {
  const response = await fetch('/api/admin/domain-groups', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  // Response:
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "name": "Production",
        "slug": "production",
        "description": "Domínios de produção",
        "is_active": true,
        "max_domains": null,  // null = ilimitado
        "domains_count": 5,   // ✅ CONTADOR JÁ VEM NA RESPOSTA
        "available_domains": null,  // null = ilimitado
        "has_reached_limit": false,
        "domains": [
          {"id": 1, "name": "zip.50g.io", "domain_url": "http://zip.50g.io"},
          {"id": 2, "name": "example.com", "domain_url": "https://example.com"}
        ],
        "creator": {
          "id": 1,
          "name": "Admin",
          "email": "admin@dashboard.com"
        }
      }
    ],
    "pagination": {...}
  }
  
  return data.data;
};
```

---

### **POST /domain-groups** - Criar Grupo (Super Admin)
```javascript
const createGroup = async (formData) => {
  const response = await fetch('/api/admin/domain-groups', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      name: formData.name,              // Obrigatório
      description: formData.description, // Opcional
      is_active: true,                   // Default true
      max_domains: formData.maxDomains || null  // null = ilimitado
    })
  });
  
  if (!response.ok) {
    if (response.status === 403) {
      alert('Apenas Super Admins podem criar grupos');
      return;
    }
    throw new Error('Failed to create group');
  }
  
  return await response.json();
};
```

---

### **PUT /domain-groups/{id}** - Atualizar Grupo (Super Admin)
```javascript
const updateGroup = async (groupId, updates) => {
  const response = await fetch(`/api/admin/domain-groups/${groupId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(updates)
  });
  
  return await response.json();
};
```

---

### **DELETE /domain-groups/{id}** - Deletar Grupo (Super Admin)
```javascript
const deleteGroup = async (groupId) => {
  const response = await fetch(`/api/admin/domain-groups/${groupId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const result = await response.json();
  
  if (!response.ok && response.status === 400) {
    // Grupo tem domínios associados
    alert(result.message);
    return false;
  }
  
  return result.success;
};
```

---

## 🔄 2. Batch Operations - IMPORTANTE!

### **POST /domain-groups/{id}/domains** - Adicionar Domínios em Lote

**⚠️ AVISO IMPORTANTE:** 
Se você adicionar domínios que já estão em outros grupos, eles serão **MOVIDOS** automaticamente. A API te avisa sobre isso!

```javascript
const addDomainsToGroup = async (groupId, domainIds) => {
  const response = await fetch(`/api/admin/domain-groups/${groupId}/domains`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      domain_ids: domainIds  // Array de IDs: [1, 2, 3, 4, 5]
    })
  });
  
  const result = await response.json();
  
  if (!response.ok) {
    if (response.status === 400) {
      // Limite do grupo foi excedido
      alert(result.message);
    }
    return null;
  }
  
  // ✅ SUCESSO - Verificar se houve movimentação
  const { data } = result;
  
  // Mostrar aviso se domínios foram movidos
  if (data.domains_moved > 0) {
    const movedNames = data.moved_from.map(d => d.domain_name).join(', ');
    const sourceGroups = [...new Set(data.moved_from.map(d => d.current_group_name))].join(', ');
    
    alert(
      `⚠️ Atenção!\n\n` +
      `${data.domains_moved} domínio(s) foram MOVIDOS de: ${sourceGroups}\n\n` +
      `Domínios: ${movedNames}`
    );
  }
  
  // Mostrar mensagem de sucesso
  toast.success(result.message);
  
  return data;
};

// Response Example:
{
  "success": true,
  "message": "2 domain(s) added, 3 domain(s) moved from other groups to group 'Testing' successfully.",
  "data": {
    "group_id": 2,
    "group_name": "Testing",
    "domains_added": 2,        // Novos domínios
    "domains_moved": 3,        // Domínios movidos de outros grupos
    "moved_from": [            // Detalhes dos domínios movidos
      {
        "domain_id": 1,
        "domain_name": "zip.50g.io",
        "current_group_id": 1,
        "current_group_name": "Production"
      }
    ],
    "total_updated": 5,
    "total_domains": 8,
    "domains": [...]
  }
}
```

---

### **DELETE /domain-groups/{id}/domains** - Remover Domínios em Lote

```javascript
const removeDomainsFromGroup = async (groupId, domainIds) => {
  const response = await fetch(`/api/admin/domain-groups/${groupId}/domains`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      domain_ids: domainIds  // [1, 2, 3]
    })
  });
  
  return await response.json();
};
```

---

## 🎯 3. Atualizar Domain Form

### **Adicionar Campo de Grupo no Form Existente**

```jsx
// No seu formulário de Domain, adicione:

import { useState, useEffect } from 'react';

export function DomainForm({ domainId = null, onSuccess }) {
  const [groups, setGroups] = useState([]);
  const [formData, setFormData] = useState({
    domain_group_id: null,  // ← NOVO CAMPO
    name: '',
    domain_url: '',
    site_id: '',
    // ... seus campos existentes
  });
  
  // Buscar grupos disponíveis
  useEffect(() => {
    fetchGroups();
    if (domainId) {
      fetchDomain(domainId);
    }
  }, [domainId]);
  
  const fetchGroups = async () => {
    const response = await fetch('/api/admin/domain-groups', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await response.json();
    setGroups(data.data);
  };
  
  return (
    <form onSubmit={handleSubmit}>
      
      {/* ========== NOVO: Seletor de Grupo ========== */}
      <div className="form-group">
        <label>Group (Optional)</label>
        <select
          value={formData.domain_group_id || ''}
          onChange={(e) => setFormData({
            ...formData,
            domain_group_id: e.target.value ? parseInt(e.target.value) : null
          })}
        >
          <option value="">No Group</option>
          {groups.map(group => (
            <option 
              key={group.id} 
              value={group.id}
              disabled={group.has_reached_limit}
            >
              {group.name}
              {group.max_domains && ` (${group.domains_count}/${group.max_domains})`}
              {group.has_reached_limit && ' - FULL'}
            </option>
          ))}
        </select>
      </div>
      
      {/* Seus campos existentes */}
      <input name="name" value={formData.name} ... />
      <input name="domain_url" value={formData.domain_url} ... />
      
      <button type="submit">Save Domain</button>
    </form>
  );
}
```

---

## 📋 4. Componentes React - Copy & Paste

### **A. DomainGroupCard** - Card de Grupo

```jsx
export function DomainGroupCard({ group, onEdit, onDelete, canEdit }) {
  const limitInfo = group.max_domains
    ? `${group.domains_count}/${group.max_domains} domains`
    : 'Unlimited';
  
  const isFull = group.has_reached_limit;
  
  return (
    <div className={`group-card ${!group.is_active ? 'inactive' : ''}`}>
      <div className="group-header">
        <h3>📁 {group.name}</h3>
        {!group.is_active && <span className="badge-inactive">Inactive</span>}
        {isFull && <span className="badge-warning">FULL</span>}
      </div>
      
      <p className="group-description">{group.description}</p>
      
      <div className="group-stats">
        <span>{limitInfo}</span>
        {group.max_domains && (
          <div className="progress-bar">
            <div 
              className="progress-fill"
              style={{ width: `${(group.domains_count / group.max_domains) * 100}%` }}
            />
          </div>
        )}
      </div>
      
      <div className="group-domains">
        <h4>Domains:</h4>
        {group.domains?.slice(0, 3).map(domain => (
          <span key={domain.id} className="domain-badge">{domain.name}</span>
        ))}
        {group.domains?.length > 3 && (
          <span>+{group.domains.length - 3} more</span>
        )}
      </div>
      
      {canEdit && (
        <div className="group-actions">
          <button onClick={() => onEdit(group)}>✏️ Edit</button>
          <button onClick={() => onDelete(group.id)} className="btn-danger">
            🗑️ Delete
          </button>
        </div>
      )}
    </div>
  );
}
```

---

### **B. DomainGroupForm** - Formulário de Grupo

```jsx
export function DomainGroupForm({ groupId = null, onSuccess }) {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    is_active: true,
    max_domains: null
  });
  
  useEffect(() => {
    if (groupId) {
      fetchGroup(groupId);
    }
  }, [groupId]);
  
  const fetchGroup = async (id) => {
    const response = await fetch(`/api/admin/domain-groups/${id}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await response.json();
    setFormData(data.data);
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const url = groupId 
      ? `/api/admin/domain-groups/${groupId}`
      : '/api/admin/domain-groups';
    
    const method = groupId ? 'PUT' : 'POST';
    
    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        name: formData.name,
        description: formData.description || null,
        is_active: formData.is_active,
        max_domains: formData.max_domains || null
      })
    });
    
    if (response.ok) {
      toast.success('Group saved successfully!');
      onSuccess();
    } else {
      const error = await response.json();
      toast.error(error.message);
    }
  };
  
  return (
    <form onSubmit={handleSubmit} className="domain-group-form">
      <div className="form-group">
        <label>Group Name *</label>
        <input
          type="text"
          value={formData.name}
          onChange={(e) => setFormData({...formData, name: e.target.value})}
          required
          placeholder="e.g. Production"
        />
      </div>
      
      <div className="form-group">
        <label>Description</label>
        <textarea
          value={formData.description}
          onChange={(e) => setFormData({...formData, description: e.target.value})}
          placeholder="Describe this group..."
        />
      </div>
      
      <div className="form-group">
        <label>Max Domains</label>
        <input
          type="number"
          value={formData.max_domains || ''}
          onChange={(e) => setFormData({
            ...formData,
            max_domains: e.target.value ? parseInt(e.target.value) : null
          })}
          min="1"
          placeholder="Leave empty for unlimited"
        />
        <small>Leave empty for unlimited domains</small>
      </div>
      
      <div className="form-group">
        <label>
          <input
            type="checkbox"
            checked={formData.is_active}
            onChange={(e) => setFormData({...formData, is_active: e.target.checked})}
          />
          Active
        </label>
      </div>
      
      <div className="form-actions">
        <button type="submit" className="btn-primary">
          {groupId ? 'Update Group' : 'Create Group'}
        </button>
      </div>
    </form>
  );
}
```

---

### **C. BatchDomainSelector** - Seleção Múltipla

```jsx
export function BatchDomainSelector({ onAddToGroup }) {
  const [domains, setDomains] = useState([]);
  const [selectedIds, setSelectedIds] = useState([]);
  const [targetGroupId, setTargetGroupId] = useState(null);
  
  const handleAddSelected = async () => {
    if (!targetGroupId || selectedIds.length === 0) {
      alert('Please select a group and at least one domain');
      return;
    }
    
    const result = await addDomainsToGroup(targetGroupId, selectedIds);
    
    if (result) {
      // Limpar seleção
      setSelectedIds([]);
      onAddToGroup();
    }
  };
  
  return (
    <div className="batch-selector">
      <h3>Add Domains to Group</h3>
      
      {/* Lista de domínios com checkboxes */}
      <div className="domain-list">
        {domains.map(domain => (
          <label key={domain.id} className="domain-checkbox">
            <input
              type="checkbox"
              checked={selectedIds.includes(domain.id)}
              onChange={(e) => {
                if (e.target.checked) {
                  setSelectedIds([...selectedIds, domain.id]);
                } else {
                  setSelectedIds(selectedIds.filter(id => id !== domain.id));
                }
              }}
            />
            {domain.name}
            {domain.domainGroup && (
              <span className="current-group">
                (Currently in: {domain.domainGroup.name})
              </span>
            )}
          </label>
        ))}
      </div>
      
      {/* Seletor de grupo de destino */}
      <div className="target-group">
        <label>Move to Group:</label>
        <select onChange={(e) => setTargetGroupId(parseInt(e.target.value))}>
          <option value="">Select Group</option>
          {groups.map(group => (
            <option key={group.id} value={group.id}>
              {group.name}
            </option>
          ))}
        </select>
      </div>
      
      <button 
        onClick={handleAddSelected}
        disabled={selectedIds.length === 0 || !targetGroupId}
        className="btn-primary"
      >
        Add {selectedIds.length} Selected Domain(s)
      </button>
    </div>
  );
}
```

---

## 🎨 5. CSS Sugerido

```css
/* Domain Group Card */
.group-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 16px;
  background: white;
}

.group-card.inactive {
  opacity: 0.6;
  background: #f5f5f5;
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.badge-warning {
  background: #ff9800;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.badge-inactive {
  background: #9e9e9e;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

/* Progress Bar */
.progress-bar {
  width: 100%;
  height: 8px;
  background: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
  margin-top: 8px;
}

.progress-fill {
  height: 100%;
  background: #4caf50;
  transition: width 0.3s;
}

.progress-fill.warning {
  background: #ff9800;
}

.progress-fill.danger {
  background: #f44336;
}

/* Domain Badge */
.domain-badge {
  display: inline-block;
  background: #e3f2fd;
  color: #1976d2;
  padding: 4px 12px;
  border-radius: 12px;
  margin: 4px;
  font-size: 12px;
}

/* Batch Selector */
.domain-checkbox {
  display: flex;
  align-items: center;
  padding: 8px;
  border: 1px solid #e0e0e0;
  margin: 4px 0;
  border-radius: 4px;
  cursor: pointer;
}

.domain-checkbox:hover {
  background: #f5f5f5;
}

.current-group {
  margin-left: auto;
  color: #757575;
  font-size: 12px;
}
```

---

## ⚠️ 6. Tratamento de Erros

```javascript
// Erro: Limite excedido
{
  "success": false,
  "message": "Cannot add 5 new domains. Group 'Testing' only has 2 available slots. Current: 8/10"
}

// Erro: Não é Super Admin
{
  "success": false,
  "message": "Access denied. Only Super Admins can perform this action."
}

// Erro: Grupo não encontrado
{
  "success": false,
  "message": "Domain group with ID 999 not found."
}

// Erro: Grupo tem domínios (ao deletar)
{
  "success": false,
  "message": "Cannot delete domain group. It still contains 5 domain(s)."
}
```

**Como tratar:**
```javascript
try {
  const response = await fetch(...);
  const result = await response.json();
  
  if (!response.ok) {
    switch (response.status) {
      case 400:
        toast.error(result.message); // Erro de validação/limite
        break;
      case 403:
        toast.error('You don\'t have permission');
        break;
      case 404:
        toast.error('Group not found');
        break;
      default:
        toast.error('Something went wrong');
    }
    return;
  }
  
  // Sucesso
  toast.success(result.message);
} catch (error) {
  toast.error('Network error');
}
```

---

## 🔐 7. Verificar Permissões

```javascript
// Verificar se é Super Admin
const isSuperAdmin = user?.is_super_admin === true;

// Mostrar/esconder elementos baseado em permissão
{isSuperAdmin && (
  <>
    <button onClick={handleCreateGroup}>Create Group</button>
    <button onClick={handleEditGroup}>Edit</button>
    <button onClick={handleDeleteGroup}>Delete</button>
  </>
)}

// Se não for super admin, apenas visualização
{!isSuperAdmin && (
  <div className="info-message">
    <i>ℹ️ Only Super Admins can manage groups</i>
  </div>
)}
```

---

## ✅ Checklist de Implementação

### **Páginas:**
- [ ] `/admin/domain-groups` - Lista de grupos
- [ ] `/admin/domain-groups/create` - Criar grupo
- [ ] `/admin/domain-groups/:id/edit` - Editar grupo
- [ ] Atualizar `/admin/domains/create` - Adicionar campo de grupo
- [ ] Atualizar `/admin/domains/:id/edit` - Adicionar campo de grupo
- [ ] Atualizar `/admin/domains` (lista) - Mostrar badge de grupo

### **Componentes:**
- [ ] `DomainGroupCard`
- [ ] `DomainGroupForm`
- [ ] `DomainGroupSelect` (dropdown reutilizável)
- [ ] `BatchDomainSelector` (opcional - para operações em lote)

### **Funcionalidades:**
- [ ] Listar grupos
- [ ] Criar grupo (Super Admin)
- [ ] Editar grupo (Super Admin)
- [ ] Deletar grupo (Super Admin)
- [ ] Selecionar grupo ao criar/editar domain
- [ ] Mostrar badge de grupo na lista de domains
- [ ] Tratar aviso de movimentação (quando adicionar domínios)

---

## 🚀 Exemplo Completo - Página de Grupos

```jsx
import { useState, useEffect } from 'react';
import { DomainGroupCard } from './DomainGroupCard';
import { DomainGroupForm } from './DomainGroupForm';

export default function DomainGroupsPage() {
  const [groups, setGroups] = useState([]);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [editingGroup, setEditingGroup] = useState(null);
  const { user } = useAuth();
  
  useEffect(() => {
    loadGroups();
  }, []);
  
  const loadGroups = async () => {
    const response = await fetch('/api/admin/domain-groups', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await response.json();
    setGroups(data.data);
  };
  
  const handleDelete = async (groupId) => {
    if (!confirm('Delete this group?')) return;
    
    const success = await deleteGroup(groupId);
    if (success) {
      toast.success('Group deleted!');
      loadGroups();
    }
  };
  
  return (
    <div className="container">
      <div className="page-header">
        <h1>📁 Domain Groups</h1>
        {user.is_super_admin && (
          <button onClick={() => setShowCreateModal(true)} className="btn-primary">
            + Create Group
          </button>
        )}
      </div>
      
      <div className="groups-grid">
        {groups.map(group => (
          <DomainGroupCard
            key={group.id}
            group={group}
            canEdit={user.is_super_admin}
            onEdit={setEditingGroup}
            onDelete={handleDelete}
          />
        ))}
      </div>
      
      {showCreateModal && (
        <Modal onClose={() => setShowCreateModal(false)}>
          <h2>Create New Group</h2>
          <DomainGroupForm onSuccess={() => {
            setShowCreateModal(false);
            loadGroups();
          }} />
        </Modal>
      )}
      
      {editingGroup && (
        <Modal onClose={() => setEditingGroup(null)}>
          <h2>Edit Group</h2>
          <DomainGroupForm 
            groupId={editingGroup.id} 
            onSuccess={() => {
              setEditingGroup(null);
              loadGroups();
            }} 
          />
        </Modal>
      )}
    </div>
  );
}
```

---

## 📞 Precisa de Ajuda?

**Documentação Completa:**
- `FRONTEND_DOMAIN_GROUPS_GUIDE.md` - Guia técnico detalhado
- `MOVE_WITH_WARNING_FEATURE.md` - Sistema de avisos de movimentação
- `BATCH_OPERATIONS_SUMMARY.md` - Operações em lote

**Endpoint de Teste:**
```bash
# Listar grupos
curl http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer seu_token"
```

---

**Versão:** 2.0  
**Data:** Novembro 10, 2025  
**Backend Status:** ✅ 100% Pronto e Testado (93 testes passando)  
**Tempo Estimado de Implementação:** 4-6 horas

