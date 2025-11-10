# 🎨 Frontend - Guia de Implementação de Domain Groups

## 📋 O Que Mudou

### **Antes:**
- Cadastro de Domains direto
- Sem organização hierárquica
- Sem grupos

### **Agora:**
- ✅ **Domain Groups** adicionados (organização hierárquica tipo Google Tag Manager)
- ✅ Domains podem ser associados a Groups
- ✅ Super Admin gerencia Groups e Domains
- ✅ Profiles automáticos por grupo

---

## 🚀 Novos Endpoints

### **Domain Groups (Super Admin apenas para CRUD)**

```http
GET    /api/admin/domain-groups              → Listar grupos
GET    /api/admin/domain-groups/{id}         → Ver grupo específico
POST   /api/admin/domain-groups              → Criar grupo [Super Admin]
PUT    /api/admin/domain-groups/{id}         → Atualizar grupo [Super Admin]
DELETE /api/admin/domain-groups/{id}         → Deletar grupo [Super Admin]
GET    /api/admin/domain-groups/{id}/domains → Listar domínios do grupo
```

### **Domains (Atualizados)**

```http
GET    /api/admin/domains                    → Listar domínios (INALTERADO)
GET    /api/admin/domains/{id}               → Ver domínio (INALTERADO)
POST   /api/admin/domains                    → Criar domínio [ATUALIZADO - aceita domain_group_id]
PUT    /api/admin/domains/{id}               → Atualizar domínio [ATUALIZADO - aceita domain_group_id]
DELETE /api/admin/domains/{id}               → Deletar domínio (agora Super Admin apenas)
```

---

## 🔐 Controle de Acesso

| Ação | Antes | Agora |
|------|-------|-------|
| Listar Groups | - | ✅ Todos admins |
| Criar Group | - | 🔒 Super Admin |
| Editar Group | - | 🔒 Super Admin |
| Deletar Group | - | 🔒 Super Admin |
| Listar Domains | ✅ Todos | ✅ Todos (igual) |
| Criar Domain | ✅ Todos | 🔒 Super Admin |
| Editar Domain | ✅ Todos | 🔒 Super Admin |
| Deletar Domain | ✅ Todos | 🔒 Super Admin |

---

## 📊 Estrutura de Dados

### **DomainGroup:**

```typescript
interface DomainGroup {
  id: number;
  name: string;
  slug: string;
  description?: string;
  is_active: boolean;
  max_domains?: number | null;  // null = ilimitado
  settings?: object;
  created_by?: number;
  updated_by?: number;
  created_at: string;
  updated_at: string;
  
  // Relacionamentos (quando incluídos)
  domains?: Domain[];
  domains_count?: number;
  available_domains?: number | null;
  has_reached_limit?: boolean;
  creator?: {
    id: number;
    name: string;
    email: string;
  };
}
```

### **Domain (Atualizado):**

```typescript
interface Domain {
  id: number;
  domain_group_id?: number | null;  // ← NOVO CAMPO
  name: string;
  slug: string;
  domain_url: string;
  site_id: string;
  api_key: string;
  status: string;
  timezone: string;
  wordpress_version: string;
  plugin_version: string;
  settings: object;
  is_active: boolean;
  
  // Relacionamento (quando incluído)
  domainGroup?: DomainGroup;
}
```

---

## 🎨 Componentes a Criar

### **1. DomainGroupList (Lista de Grupos)**

```tsx
// Exemplo React/Next.js
import { useState, useEffect } from 'react';

export function DomainGroupList() {
  const [groups, setGroups] = useState([]);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    fetchGroups();
  }, []);
  
  const fetchGroups = async () => {
    const response = await fetch('/api/admin/domain-groups', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    const data = await response.json();
    setGroups(data.data);
    setLoading(false);
  };
  
  return (
    <div className="domain-groups-list">
      <h2>Domain Groups</h2>
      {groups.map(group => (
        <div key={group.id} className="group-card">
          <h3>{group.name}</h3>
          <p>{group.description}</p>
          <span>Domains: {group.domains?.length || 0}</span>
          {group.max_domains && (
            <span>Max: {group.max_domains}</span>
          )}
        </div>
      ))}
    </div>
  );
}
```

---

### **2. DomainGroupForm (Criar/Editar Grupo)**

```tsx
export function DomainGroupForm({ groupId = null, onSuccess }) {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    is_active: true,
    max_domains: null, // null = ilimitado
    settings: {}
  });
  
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
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(formData)
    });
    
    if (response.ok) {
      onSuccess();
    }
  };
  
  return (
    <form onSubmit={handleSubmit}>
      <input
        type="text"
        placeholder="Group Name"
        value={formData.name}
        onChange={(e) => setFormData({...formData, name: e.target.value})}
        required
      />
      
      <textarea
        placeholder="Description"
        value={formData.description}
        onChange={(e) => setFormData({...formData, description: e.target.value})}
      />
      
      <label>
        <input
          type="checkbox"
          checked={formData.is_active}
          onChange={(e) => setFormData({...formData, is_active: e.target.checked})}
        />
        Active
      </label>
      
      <input
        type="number"
        placeholder="Max Domains (leave empty for unlimited)"
        value={formData.max_domains || ''}
        onChange={(e) => setFormData({
          ...formData, 
          max_domains: e.target.value ? parseInt(e.target.value) : null
        })}
        min="1"
      />
      
      <button type="submit">
        {groupId ? 'Update Group' : 'Create Group'}
      </button>
    </form>
  );
}
```

---

### **3. DomainForm (Atualizado - COM Grupo)**

```tsx
export function DomainForm({ domainId = null, onSuccess }) {
  const [groups, setGroups] = useState([]);
  const [formData, setFormData] = useState({
    domain_group_id: null,  // ← NOVO CAMPO
    name: '',
    domain_url: '',
    site_id: '',
    timezone: 'UTC',
    wordpress_version: '',
    plugin_version: '',
    is_active: true,
    settings: {}
  });
  
  useEffect(() => {
    // Buscar grupos disponíveis
    fetchGroups();
    
    // Se editando, buscar domínio
    if (domainId) {
      fetchDomain(domainId);
    }
  }, [domainId]);
  
  const fetchGroups = async () => {
    const response = await fetch('/api/admin/domain-groups', {
      headers: {
        'Authorization': `Bearer ${token}`,
      }
    });
    const data = await response.json();
    setGroups(data.data);
  };
  
  const fetchDomain = async (id) => {
    const response = await fetch(`/api/admin/domains/${id}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
      }
    });
    const data = await response.json();
    setFormData(data.data);
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const url = domainId 
      ? `/api/admin/domains/${domainId}`
      : '/api/admin/domains';
    
    const method = domainId ? 'PUT' : 'POST';
    
    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(formData)
    });
    
    const result = await response.json();
    
    if (response.ok) {
      onSuccess();
    } else {
      // Tratar erro de limite
      if (result.max_domains) {
        alert(`Group limit reached: ${result.message}`);
      }
    }
  };
  
  return (
    <form onSubmit={handleSubmit}>
      {/* NOVO: Seletor de Grupo */}
      <div className="form-group">
        <label>Domain Group (Optional)</label>
        <select
          value={formData.domain_group_id || ''}
          onChange={(e) => setFormData({
            ...formData, 
            domain_group_id: e.target.value ? parseInt(e.target.value) : null
          })}
        >
          <option value="">No Group</option>
          {groups.map(group => (
            <option key={group.id} value={group.id}>
              {group.name} 
              {group.max_domains && ` (${group.domains?.length || 0}/${group.max_domains})`}
            </option>
          ))}
        </select>
      </div>
      
      {/* Campos existentes */}
      <input
        type="text"
        placeholder="Domain Name"
        value={formData.name}
        onChange={(e) => setFormData({...formData, name: e.target.value})}
        required
      />
      
      <input
        type="url"
        placeholder="Domain URL"
        value={formData.domain_url}
        onChange={(e) => setFormData({...formData, domain_url: e.target.value})}
        required
      />
      
      {/* ... outros campos ... */}
      
      <button type="submit">
        {domainId ? 'Update Domain' : 'Create Domain'}
      </button>
    </form>
  );
}
```

---

## 🔄 Adaptação de Formulários Existentes

### **Mudança 1: Adicionar Campo domain_group_id**

```diff
// Domain Create/Edit Form
const formData = {
+ domain_group_id: null,  // ADICIONAR
  name: '',
  domain_url: '',
  site_id: '',
  ...
};
```

### **Mudança 2: Adicionar Seletor de Grupo**

```tsx
// No seu formulário de domínio, adicione:
<FormField label="Group (Optional)">
  <Select
    value={formData.domain_group_id}
    onChange={(value) => setFormData({...formData, domain_group_id: value})}
    options={[
      { value: null, label: 'No Group' },
      ...groups.map(g => ({
        value: g.id,
        label: `${g.name} ${g.max_domains ? `(${g.domains_count}/${g.max_domains})` : ''}`
      }))
    ]}
  />
</FormField>
```

### **Mudança 3: Mostrar Grupo na Lista de Domains**

```tsx
// Na lista de domínios, adicione coluna de grupo:
<Table>
  <thead>
    <tr>
      <th>Name</th>
      <th>URL</th>
      <th>Group</th> {/* ← NOVA COLUNA */}
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    {domains.map(domain => (
      <tr key={domain.id}>
        <td>{domain.name}</td>
        <td>{domain.domain_url}</td>
        <td>
          {domain.domainGroup ? (
            <Badge>{domain.domainGroup.name}</Badge>
          ) : (
            <span className="text-muted">No Group</span>
          )}
        </td>
        <td>{domain.is_active ? 'Active' : 'Inactive'}</td>
        <td>...</td>
      </tr>
    ))}
  </tbody>
</Table>
```

---

## 📡 Exemplos de Requisições

### **1. Listar Domain Groups**

```javascript
// GET /api/admin/domain-groups
const fetchGroups = async () => {
  const response = await fetch('http://localhost:8007/api/admin/domain-groups', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  });
  
  const data = await response.json();
  /*
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "name": "Production",
        "slug": "production",
        "description": "Domínios de produção com dados reais",
        "is_active": true,
        "max_domains": null,
        "domains": [
          {"id": 1, "name": "zip.50g.io"},
          {"id": 2, "name": "fiberfinder.com"}
        ]
      },
      {
        "id": 2,
        "name": "Testing",
        "slug": "testing",
        "description": "Domínios de teste e staging",
        "is_active": true,
        "max_domains": null,
        "domains": [
          {"id": 3, "name": "smarterhome.ai"},
          {"id": 4, "name": "ispfinder.net"},
          {"id": 5, "name": "broadbandcheck.io"}
        ]
      }
    ],
    "pagination": {...}
  }
  */
  
  return data.data;
};
```

---

### **2. Criar Domain Group (Super Admin)**

```javascript
// POST /api/admin/domain-groups
const createGroup = async (groupData) => {
  const response = await fetch('http://localhost:8007/api/admin/domain-groups', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      name: groupData.name,                    // Obrigatório
      description: groupData.description,       // Opcional
      is_active: true,                          // Padrão: true
      max_domains: groupData.max_domains,       // Opcional (null = ilimitado)
      settings: groupData.settings              // Opcional
    })
  });
  
  const result = await response.json();
  
  if (!response.ok) {
    if (response.status === 403) {
      alert('Only Super Admins can create groups');
    } else {
      alert(result.message);
    }
    return null;
  }
  
  return result.data;
};

// Exemplo de uso:
await createGroup({
  name: 'My New Group',
  description: 'Group for my clients',
  max_domains: 10  // ou null para ilimitado
});
```

---

### **3. Atualizar Domain Group**

```javascript
// PUT /api/admin/domain-groups/{id}
const updateGroup = async (groupId, updates) => {
  const response = await fetch(`http://localhost:8007/api/admin/domain-groups/${groupId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(updates)
  });
  
  return await response.json();
};

// Exemplo:
await updateGroup(1, {
  max_domains: 20,
  settings: { tier: 'premium' }
});
```

---

### **4. Deletar Domain Group**

```javascript
// DELETE /api/admin/domain-groups/{id}
const deleteGroup = async (groupId) => {
  const response = await fetch(`http://localhost:8007/api/admin/domain-groups/${groupId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
    }
  });
  
  const result = await response.json();
  
  if (!response.ok && response.status === 400) {
    // Grupo tem domínios associados
    alert(`Cannot delete: ${result.message}`);
    return false;
  }
  
  return result.success;
};
```

---

### **5. Criar Domain COM Grupo (ATUALIZADO)**

```javascript
// POST /api/admin/domains
const createDomain = async (domainData) => {
  const response = await fetch('http://localhost:8007/api/admin/domains', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      domain_group_id: domainData.groupId,     // ← NOVO CAMPO (opcional)
      name: domainData.name,
      domain_url: domainData.url,
      site_id: domainData.siteId,
      timezone: domainData.timezone || 'UTC',
      wordpress_version: domainData.wpVersion,
      plugin_version: domainData.pluginVersion,
      settings: domainData.settings,
      is_active: true
    })
  });
  
  const result = await response.json();
  
  // Tratar erro de limite de grupo
  if (!response.ok && result.max_domains) {
    alert(`Group limit reached: ${result.message}\nCurrent: ${result.current_count}/${result.max_domains}`);
    return null;
  }
  
  return result.data;
};

// Exemplo de uso:
await createDomain({
  groupId: 1,  // Associar ao grupo Production
  name: 'newdomain.com',
  url: 'https://newdomain.com',
  siteId: 'wp-new-domain'
});
```

---

### **6. Listar Domains COM Grupo (Response Atualizada)**

```javascript
// GET /api/admin/domains
const fetchDomains = async () => {
  const response = await fetch('http://localhost:8007/api/admin/domains', {
    headers: {
      'Authorization': `Bearer ${token}`,
    }
  });
  
  const data = await response.json();
  /*
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "domain_group_id": 1,  // ← NOVO CAMPO
        "name": "zip.50g.io",
        "domain_url": "http://zip.50g.io",
        ...
      }
    ]
  }
  */
  
  // Para buscar info do grupo, você pode:
  // Opção 1: Fazer join no backend (recomendado)
  // Opção 2: Fazer requisição separada
  const domainsWithGroups = await Promise.all(
    data.data.map(async (domain) => {
      if (domain.domain_group_id) {
        const groupResponse = await fetch(
          `http://localhost:8007/api/admin/domain-groups/${domain.domain_group_id}`,
          { headers: { 'Authorization': `Bearer ${token}` }}
        );
        const groupData = await groupResponse.json();
        domain.group = groupData.data;
      }
      return domain;
    })
  );
  
  return domainsWithGroups;
};
```

---

## 🎨 UI Sugerida

### **Página de Domain Groups:**

```
┌─────────────────────────────────────────────────────────┐
│ Domain Groups                           [+ New Group]   │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 📁 Production                             🟢 Active    │
│ Domínios de produção com dados reais                   │
│ Domains: 2 | Max: Unlimited                            │
│ [View] [Edit] [Delete]                                 │
│                                                         │
│ ─────────────────────────────────────────────────────  │
│                                                         │
│ 📁 Testing                                🟢 Active    │
│ Domínios de teste e staging                            │
│ Domains: 3 | Max: Unlimited                            │
│ [View] [Edit] [Delete]                                 │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

### **Formulário de Domain (Atualizado):**

```
┌─────────────────────────────────────────────────────────┐
│ Create New Domain                                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Group (Optional)                                        │
│ [▼ Select Group               ]                         │
│   • No Group                                            │
│   • Production (2/∞)                                    │
│   • Testing (3/∞)                                       │
│                                                         │
│ Domain Name *                                           │
│ [_________________________________]                     │
│                                                         │
│ Domain URL *                                            │
│ [_________________________________]                     │
│                                                         │
│ Site ID                                                 │
│ [_________________________________]                     │
│                                                         │
│ Timezone                                                │
│ [▼ America/New_York            ]                        │
│                                                         │
│ [ ] Active                                              │
│                                                         │
│           [Cancel]  [Create Domain]                     │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🔒 Verificar Permissões no Frontend

```javascript
// Utils para verificar permissões
const isSuperAdmin = (user) => {
  return user?.is_super_admin === true;
};

// Mostrar/esconder botões baseado em permissão
{isSuperAdmin(currentUser) && (
  <>
    <button onClick={handleCreateGroup}>Create Group</button>
    <button onClick={handleEditGroup}>Edit Group</button>
    <button onClick={handleCreateDomain}>Create Domain</button>
  </>
)}

// Se não for super admin
{!isSuperAdmin(currentUser) && (
  <p className="text-muted">
    Only Super Admins can create/edit groups and domains
  </p>
)}
```

---

## ⚠️ Tratamento de Erros

### **Erro: Limite de Grupo Atingido**

```javascript
const handleCreateDomain = async (data) => {
  try {
    const response = await fetch('/api/admin/domains', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (response.status === 400 && result.max_domains) {
      // Grupo atingiu limite
      showError(
        `Cannot add domain to group "${result.message}"\n` +
        `Current: ${result.current_count}/${result.max_domains} domains`
      );
      return;
    }
    
    if (!response.ok) {
      showError(result.message);
      return;
    }
    
    showSuccess('Domain created successfully!');
  } catch (error) {
    showError('Network error');
  }
};
```

### **Erro: Não é Super Admin**

```javascript
const handleCreateGroup = async (data) => {
  const response = await fetch('/api/admin/domain-groups', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data)
  });
  
  const result = await response.json();
  
  if (response.status === 403) {
    showError('Access denied. Only Super Admins can create groups.');
    return;
  }
  
  // Sucesso
  showSuccess('Group created!');
};
```

---

## 🎯 Fluxo Completo de Implementação

### **Passo 1: Criar Interfaces TypeScript**

```typescript
// types/domain.ts
export interface DomainGroup {
  id: number;
  name: string;
  slug: string;
  description?: string;
  is_active: boolean;
  max_domains?: number | null;
  settings?: Record<string, any>;
  domains_count?: number;
  available_domains?: number | null;
  has_reached_limit?: boolean;
  domains?: Domain[];
}

export interface Domain {
  id: number;
  domain_group_id?: number | null;  // ← NOVO
  name: string;
  domain_url: string;
  site_id: string;
  api_key: string;
  status: string;
  is_active: boolean;
  domainGroup?: DomainGroup;  // ← NOVO
}
```

---

### **Passo 2: Criar Service/API**

```typescript
// services/domainGroupService.ts
export const domainGroupService = {
  async list(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const response = await api.get(`/admin/domain-groups?${queryString}`);
    return response.data;
  },
  
  async get(id: number) {
    const response = await api.get(`/admin/domain-groups/${id}`);
    return response.data;
  },
  
  async create(data: Partial<DomainGroup>) {
    const response = await api.post('/admin/domain-groups', data);
    return response.data;
  },
  
  async update(id: number, data: Partial<DomainGroup>) {
    const response = await api.put(`/admin/domain-groups/${id}`, data);
    return response.data;
  },
  
  async delete(id: number) {
    const response = await api.delete(`/admin/domain-groups/${id}`);
    return response.data;
  },
};
```

---

### **Passo 3: Atualizar Domain Service**

```typescript
// services/domainService.ts
export const domainService = {
  async create(data: Partial<Domain>) {
    const response = await api.post('/admin/domains', {
      ...data,
      domain_group_id: data.domain_group_id || null,  // ← ADICIONAR
    });
    return response.data;
  },
  
  async update(id: number, data: Partial<Domain>) {
    const response = await api.put(`/admin/domains/${id}`, {
      ...data,
      domain_group_id: data.domain_group_id || null,  // ← ADICIONAR
    });
    return response.data;
  },
};
```

---

### **Passo 4: Criar Componentes**

#### **A. Seletor de Grupo (Reutilizável)**

```tsx
// components/DomainGroupSelect.tsx
interface Props {
  value: number | null;
  onChange: (groupId: number | null) => void;
  showNone?: boolean;
}

export function DomainGroupSelect({ value, onChange, showNone = true }: Props) {
  const [groups, setGroups] = useState<DomainGroup[]>([]);
  
  useEffect(() => {
    fetchGroups();
  }, []);
  
  const fetchGroups = async () => {
    const data = await domainGroupService.list();
    setGroups(data);
  };
  
  return (
    <select value={value || ''} onChange={(e) => onChange(e.target.value ? parseInt(e.target.value) : null)}>
      {showNone && <option value="">No Group</option>}
      {groups.map(group => (
        <option key={group.id} value={group.id}>
          {group.name}
          {group.max_domains && ` (${group.domains_count || 0}/${group.max_domains})`}
          {group.has_reached_limit && ' - FULL'}
        </option>
      ))}
    </select>
  );
}
```

#### **B. Lista de Grupos**

```tsx
// pages/DomainGroups.tsx
export function DomainGroupsPage() {
  const [groups, setGroups] = useState([]);
  const { user } = useAuth();
  
  const handleDelete = async (groupId) => {
    if (!confirm('Delete this group?')) return;
    
    const result = await domainGroupService.delete(groupId);
    if (result.success) {
      fetchGroups(); // Recarregar
    }
  };
  
  return (
    <div>
      <h1>Domain Groups</h1>
      
      {user.is_super_admin && (
        <button onClick={() => setShowCreateModal(true)}>
          + Create Group
        </button>
      )}
      
      {groups.map(group => (
        <DomainGroupCard
          key={group.id}
          group={group}
          onEdit={user.is_super_admin ? handleEdit : undefined}
          onDelete={user.is_super_admin ? handleDelete : undefined}
        />
      ))}
    </div>
  );
}
```

---

### **Passo 5: Atualizar Formulário de Domain Existente**

```diff
// components/DomainForm.tsx (ANTES)
const DomainForm = () => {
  const [formData, setFormData] = useState({
    name: '',
    domain_url: '',
    site_id: '',
    ...
  });
  
  return (
    <form>
      <input name="name" ... />
      <input name="domain_url" ... />
      ...
    </form>
  );
};

// components/DomainForm.tsx (DEPOIS)
const DomainForm = () => {
  const [formData, setFormData] = useState({
+   domain_group_id: null,  // ADICIONAR
    name: '',
    domain_url: '',
    site_id: '',
    ...
  });
  
  return (
    <form>
+     {/* ADICIONAR: Seletor de Grupo */}
+     <FormField label="Group (Optional)">
+       <DomainGroupSelect
+         value={formData.domain_group_id}
+         onChange={(id) => setFormData({...formData, domain_group_id: id})}
+       />
+     </FormField>
      
      <input name="name" ... />
      <input name="domain_url" ... />
      ...
    </form>
  );
};
```

---

## 📊 Estados da UI

### **Carregando Grupos:**

```tsx
{loading && <Spinner />}
{!loading && groups.length === 0 && (
  <EmptyState message="No groups yet. Create your first group!" />
)}
```

### **Grupo com Limite Atingido:**

```tsx
{group.has_reached_limit && (
  <Badge variant="warning">
    FULL ({group.domains_count}/{group.max_domains})
  </Badge>
)}
```

### **Mostrar Badge de Grupo em Domain:**

```tsx
// Na lista de domains
{domain.domainGroup ? (
  <Badge variant="primary">
    📁 {domain.domainGroup.name}
  </Badge>
) : (
  <span className="text-muted">No group</span>
)}
```

---

## 🔍 Busca e Filtros

### **Buscar Grupos:**

```javascript
const searchGroups = async (searchTerm) => {
  const params = new URLSearchParams({
    search: searchTerm,
    per_page: '20'
  });
  
  const response = await fetch(`/api/admin/domain-groups?${params}`);
  return await response.json();
};
```

### **Filtrar por Status:**

```javascript
const filterGroups = async (isActive) => {
  const params = new URLSearchParams({
    is_active: isActive.toString()
  });
  
  const response = await fetch(`/api/admin/domain-groups?${params}`);
  return await response.json();
};
```

---

## ✅ Checklist de Implementação Frontend

### **Domain Groups:**
- [ ] Criar página de listagem de grupos
- [ ] Criar formulário de criação de grupo (Super Admin)
- [ ] Criar formulário de edição de grupo (Super Admin)
- [ ] Implementar deleção de grupo (Super Admin)
- [ ] Adicionar busca e filtros
- [ ] Mostrar domínios de cada grupo
- [ ] Mostrar indicador de limite (X/Y domains)

### **Domains (Atualizar Existente):**
- [ ] Adicionar campo `domain_group_id` no formulário
- [ ] Adicionar seletor de grupo (dropdown)
- [ ] Mostrar grupo na lista de domínios
- [ ] Tratar erro de limite de grupo
- [ ] Mostrar badge de grupo
- [ ] Filtrar domínios por grupo

### **Permissões:**
- [ ] Esconder botões de criar/editar/deletar grupo para não-super-admins
- [ ] Esconder botões de criar/editar domain para não-super-admins
- [ ] Mostrar mensagem apropriada quando sem permissão

---

## 🎯 Exemplo Completo (React)

```tsx
// pages/DomainGroups/index.tsx
import { useState, useEffect } from 'react';
import { DomainGroupCard } from './DomainGroupCard';
import { DomainGroupForm } from './DomainGroupForm';
import { useAuth } from '@/hooks/useAuth';
import { domainGroupService } from '@/services/domainGroupService';

export default function DomainGroupsPage() {
  const [groups, setGroups] = useState([]);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const { user } = useAuth();
  
  useEffect(() => {
    loadGroups();
  }, []);
  
  const loadGroups = async () => {
    const data = await domainGroupService.list();
    setGroups(data);
  };
  
  const handleCreate = async (formData) => {
    await domainGroupService.create(formData);
    setShowCreateModal(false);
    loadGroups();
  };
  
  return (
    <div className="container">
      <div className="header">
        <h1>Domain Groups</h1>
        {user.is_super_admin && (
          <button onClick={() => setShowCreateModal(true)}>
            + New Group
          </button>
        )}
      </div>
      
      <div className="groups-grid">
        {groups.map(group => (
          <DomainGroupCard
            key={group.id}
            group={group}
            canEdit={user.is_super_admin}
            onUpdate={loadGroups}
          />
        ))}
      </div>
      
      {showCreateModal && (
        <Modal onClose={() => setShowCreateModal(false)}>
          <DomainGroupForm onSuccess={handleCreate} />
        </Modal>
      )}
    </div>
  );
}
```

---

## 📝 Notas Importantes

1. **Backward Compatibility:**
   - ✅ `domain_group_id` é **opcional** (pode ser null)
   - ✅ Domínios sem grupo continuam funcionando
   - ✅ Formulários existentes funcionam (apenas não enviam group_id)

2. **Super Admin:**
   - ✅ Verificar `user.is_super_admin` no frontend
   - ✅ Esconder botões de criar/editar se não for super admin
   - ✅ API vai retornar 403 se tentar sem permissão

3. **Limite de Domínios:**
   - ✅ Mostrar contador visual (X/Y domains)
   - ✅ Desabilitar grupo no select se atingiu limite
   - ✅ Tratar erro 400 ao tentar adicionar em grupo cheio

4. **URLs:**
   - ✅ Usar porta **8007** (local Docker)
   - ✅ Ou porta apropriada do servidor de produção

---

## 🚀 Resumo das Mudanças

| Item | Ação | Prioridade |
|------|------|------------|
| **Nova página: Domain Groups** | Criar | Alta |
| **Campo domain_group_id em Domain Form** | Adicionar | Alta |
| **Seletor de grupo** | Criar componente | Alta |
| **Validação de Super Admin** | Adicionar checks | Alta |
| **Badge de grupo na lista** | Adicionar | Média |
| **Tratamento de erro de limite** | Implementar | Média |
| **Busca e filtros de grupos** | Implementar | Baixa |

---

**Data:** Novembro 8, 2025  
**Versão da API:** 1.0  
**Status:** ✅ Backend Pronto - Aguardando Frontend  
**Porta Local:** 8007  
**Porta Servidor:** Verificar configuração

