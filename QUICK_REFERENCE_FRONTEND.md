# ⚡ Quick Reference - Frontend Domain Groups

## 🎯 Resumo Ultra-Rápido

**O que mudou:** Domains agora podem ter um `domain_group_id` (opcional).

---

## 📡 Endpoints Novos

```
GET    /api/admin/domain-groups              → Listar grupos
POST   /api/admin/domain-groups              → Criar [Super Admin]
PUT    /api/admin/domain-groups/{id}         → Editar [Super Admin]
DELETE /api/admin/domain-groups/{id}         → Deletar [Super Admin]
```

---

## 🔧 Adaptar Form de Domain

### **Adicione:**

```javascript
// 1. State
const [groups, setGroups] = useState([]);
const [formData, setFormData] = useState({
  domain_group_id: null,  // ← NOVO
  name: '',
  domain_url: '',
  ...
});

// 2. Fetch groups
useEffect(() => {
  fetch('/api/admin/domain-groups', {
    headers: { 'Authorization': `Bearer ${token}` }
  })
  .then(r => r.json())
  .then(data => setGroups(data.data));
}, []);

// 3. Campo no form
<select 
  value={formData.domain_group_id || ''} 
  onChange={(e) => setFormData({
    ...formData, 
    domain_group_id: e.target.value ? parseInt(e.target.value) : null
  })}
>
  <option value="">No Group</option>
  {groups.map(g => (
    <option key={g.id} value={g.id}>{g.name}</option>
  ))}
</select>
```

---

## 📊 Response Atualizada

### **Domain agora retorna:**

```json
{
  "id": 1,
  "domain_group_id": 1,          ← NOVO
  "name": "zip.50g.io",
  "domain_url": "http://zip.50g.io",
  ...
}
```

---

## 🎨 UI Sugestão

**Na lista de domains, adicione coluna:**

```tsx
<td>
  {domain.domain_group_id ? (
    <Badge>Group ID: {domain.domain_group_id}</Badge>
  ) : (
    <span className="text-muted">-</span>
  )}
</td>
```

---

## 🔒 Permissões

```javascript
// Apenas Super Admin pode criar/editar
{user.is_super_admin && (
  <button onClick={handleCreate}>Create Group</button>
)}
```

---

## ⚠️ Erros a Tratar

### **Limite de Grupo:**
```json
{
  "success": false,
  "message": "Domain group 'Testing' has reached its maximum domains limit.",
  "max_domains": 10,
  "current_count": 10
}
```

### **Não é Super Admin:**
```json
{
  "success": false,
  "message": "Access denied. Only Super Admins can perform this action.",
  "required_permission": "super_admin"
}
```

---

## ✅ To-Do List Frontend

- [ ] Adicionar `domain_group_id` ao form de Domain (campo opcional)
- [ ] Criar seletor de grupo (dropdown)
- [ ] Criar página de Domain Groups (listar)
- [ ] Criar modal de criar/editar grupo (Super Admin)
- [ ] Esconder botões de criar/editar para não-super-admins
- [ ] Mostrar grupo na lista de domains

**Tempo estimado:** 2-4 horas

---

**Porta API:** http://localhost:8007  
**Auth:** Bearer Token (mesmo de antes)  
**Compatibilidade:** 100% backward compatible

