# 🔐 Alteração de Senha - Documentação Frontend

## Endpoint: Alterar Senha do Administrador

Permite que um administrador autenticado altere sua própria senha.

---

## 📋 Informações Gerais

**URL:** `/api/admin/change-password`  
**Método:** `POST`  
**Autenticação:** ✅ Requerida (Bearer Token)  
**Middleware:** `auth:sanctum`, `admin.auth`

---

## 📥 Request

### Headers

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Body (JSON)

```json
{
  "current_password": "string (obrigatório)",
  "new_password": "string (obrigatório, mínimo 8 caracteres)",
  "new_password_confirmation": "string (obrigatório, deve ser igual a new_password)"
}
```

### Campos da Requisição

| Campo | Tipo | Obrigatório | Validação | Descrição |
|-------|------|-------------|-----------|-----------|
| `current_password` | string | ✅ Sim | Mínimo 1 caractere | Senha atual do administrador |
| `new_password` | string | ✅ Sim | Mínimo 8 caracteres | Nova senha desejada |
| `new_password_confirmation` | string | ✅ Sim | Deve ser igual a `new_password` | Confirmação da nova senha |

---

## 📤 Response

### ✅ Sucesso (200 OK)

```json
{
  "success": true,
  "message": "Senha alterada com sucesso",
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "is_active": true,
    "is_super_admin": false,
    "last_login_at": "2025-12-04T21:30:00.000000Z",
    "created_at": "2025-01-15T10:00:00.000000Z",
    "updated_at": "2025-12-04T21:50:00.000000Z"
  }
}
```

### ❌ Erros

#### 401 Unauthorized - Senha Atual Incorreta

```json
{
  "success": false,
  "message": "Current password is incorrect"
}
```

#### 422 Unprocessable Entity - Erros de Validação

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "current_password": [
      "A senha atual é obrigatória"
    ],
    "new_password": [
      "A nova senha deve ter no mínimo 8 caracteres",
      "A confirmação da nova senha não confere"
    ],
    "new_password_confirmation": [
      "The new password confirmation field is required."
    ]
  }
}
```

#### 500 Internal Server Error

```json
{
  "success": false,
  "message": "Erro ao alterar senha",
  "error": "Error message (apenas em modo debug)"
}
```

---

## 💻 Exemplos de Uso

### Vue 3 / Nuxt 3 - Composables

#### Criar Composable: `useChangePassword.js`

```javascript
// composables/useChangePassword.js
import { useAuthStore } from '@/stores/auth'

export const useChangePassword = () => {
  const { $fetch } = useNuxtApp()
  const authStore = useAuthStore()
  
  const changePassword = async (currentPassword, newPassword, newPasswordConfirmation) => {
    try {
      const response = await $fetch('/api/admin/change-password', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json'
        },
        body: {
          current_password: currentPassword,
          new_password: newPassword,
          new_password_confirmation: newPasswordConfirmation
        }
      })
      
      return {
        success: true,
        data: response.data,
        message: response.message
      }
    } catch (error) {
      // Tratamento de erros
      if (error.statusCode === 401) {
        return {
          success: false,
          message: 'Senha atual incorreta',
          error: 'current_password_incorrect'
        }
      }
      
      if (error.statusCode === 422) {
        return {
          success: false,
          message: 'Erros de validação',
          errors: error.data?.errors || {}
        }
      }
      
      return {
        success: false,
        message: 'Erro ao alterar senha. Tente novamente.',
        error: 'unknown_error'
      }
    }
  }
  
  return {
    changePassword
  }
}
```

---

### Componente Vue Completo

```vue
<template>
  <div class="change-password-form">
    <h2>Alterar Senha</h2>
    
    <form @submit.prevent="handleSubmit">
      <!-- Senha Atual -->
      <div class="form-group">
        <label for="current_password">Senha Atual *</label>
        <input
          id="current_password"
          v-model="form.current_password"
          type="password"
          :class="{ 'error': errors.current_password }"
          placeholder="Digite sua senha atual"
        />
        <span v-if="errors.current_password" class="error-message">
          {{ errors.current_password[0] }}
        </span>
      </div>

      <!-- Nova Senha -->
      <div class="form-group">
        <label for="new_password">Nova Senha *</label>
        <input
          id="new_password"
          v-model="form.new_password"
          type="password"
          :class="{ 'error': errors.new_password }"
          placeholder="Mínimo 8 caracteres"
        />
        <span v-if="errors.new_password" class="error-message">
          {{ errors.new_password[0] }}
        </span>
        <small class="hint">Mínimo 8 caracteres</small>
      </div>

      <!-- Confirmação da Nova Senha -->
      <div class="form-group">
        <label for="new_password_confirmation">Confirmar Nova Senha *</label>
        <input
          id="new_password_confirmation"
          v-model="form.new_password_confirmation"
          type="password"
          :class="{ 'error': errors.new_password_confirmation }"
          placeholder="Confirme sua nova senha"
        />
        <span v-if="errors.new_password_confirmation" class="error-message">
          {{ errors.new_password_confirmation[0] }}
        </span>
      </div>

      <!-- Mensagem de Erro Geral -->
      <div v-if="generalError" class="alert alert-error">
        {{ generalError }}
      </div>

      <!-- Mensagem de Sucesso -->
      <div v-if="successMessage" class="alert alert-success">
        {{ successMessage }}
      </div>

      <!-- Botões -->
      <div class="form-actions">
        <button
          type="submit"
          :disabled="loading"
          class="btn btn-primary"
        >
          <span v-if="loading">Alterando...</span>
          <span v-else>Alterar Senha</span>
        </button>
        
        <button
          type="button"
          @click="resetForm"
          class="btn btn-secondary"
          :disabled="loading"
        >
          Cancelar
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useChangePassword } from '@/composables/useChangePassword'

const { changePassword } = useChangePassword()

const form = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const errors = ref({})
const loading = ref(false)
const generalError = ref('')
const successMessage = ref('')

const validateForm = () => {
  errors.value = {}
  let isValid = true

  if (!form.current_password) {
    errors.value.current_password = ['A senha atual é obrigatória']
    isValid = false
  }

  if (!form.new_password) {
    errors.value.new_password = ['A nova senha é obrigatória']
    isValid = false
  } else if (form.new_password.length < 8) {
    errors.value.new_password = ['A nova senha deve ter no mínimo 8 caracteres']
    isValid = false
  }

  if (!form.new_password_confirmation) {
    errors.value.new_password_confirmation = ['A confirmação da senha é obrigatória']
    isValid = false
  } else if (form.new_password !== form.new_password_confirmation) {
    errors.value.new_password_confirmation = ['As senhas não coincidem']
    isValid = false
  }

  return isValid
}

const handleSubmit = async () => {
  generalError.value = ''
  successMessage.value = ''
  errors.value = {}

  // Validação local
  if (!validateForm()) {
    return
  }

  loading.value = true

  try {
    const result = await changePassword(
      form.current_password,
      form.new_password,
      form.new_password_confirmation
    )

    if (result.success) {
      successMessage.value = result.message || 'Senha alterada com sucesso!'
      
      // Limpar formulário após 2 segundos
      setTimeout(() => {
        resetForm()
        successMessage.value = ''
      }, 2000)
    } else {
      // Tratar erros específicos
      if (result.error === 'current_password_incorrect') {
        generalError.value = 'Senha atual incorreta. Verifique e tente novamente.'
        errors.value.current_password = ['Senha atual incorreta']
      } else if (result.errors) {
        errors.value = result.errors
        generalError.value = 'Por favor, corrija os erros no formulário.'
      } else {
        generalError.value = result.message || 'Erro ao alterar senha. Tente novamente.'
      }
    }
  } catch (error) {
    generalError.value = 'Erro inesperado. Tente novamente.'
    console.error('Error changing password:', error)
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.current_password = ''
  form.new_password = ''
  form.new_password_confirmation = ''
  errors.value = {}
  generalError.value = ''
  successMessage.value = ''
}
</script>

<style scoped>
.change-password-form {
  max-width: 500px;
  margin: 0 auto;
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.form-group input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group input.error {
  border-color: #ef4444;
}

.error-message {
  display: block;
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.hint {
  display: block;
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.alert {
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.alert-error {
  background-color: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
}

.alert-success {
  background-color: #d1fae5;
  color: #065f46;
  border: 1px solid #6ee7b7;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-secondary {
  background-color: #e5e7eb;
  color: #374151;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #d1d5db;
}
</style>
```

---

### React / Next.js - Exemplo com Hooks

```typescript
// hooks/useChangePassword.ts
import { useState } from 'react'
import axios from 'axios'

interface ChangePasswordForm {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

interface ChangePasswordResponse {
  success: boolean
  message: string
  data?: any
  errors?: Record<string, string[]>
}

export const useChangePassword = () => {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const changePassword = async (
    form: ChangePasswordForm,
    token: string
  ): Promise<ChangePasswordResponse> => {
    setLoading(true)
    setError(null)

    try {
      const response = await axios.post(
        '/api/admin/change-password',
        form,
        {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        }
      )

      return {
        success: true,
        message: response.data.message,
        data: response.data.data,
      }
    } catch (err: any) {
      if (err.response?.status === 401) {
        return {
          success: false,
          message: 'Senha atual incorreta',
          errors: {
            current_password: ['Senha atual incorreta'],
          },
        }
      }

      if (err.response?.status === 422) {
        return {
          success: false,
          message: 'Erros de validação',
          errors: err.response.data.errors,
        }
      }

      return {
        success: false,
        message: 'Erro ao alterar senha. Tente novamente.',
      }
    } finally {
      setLoading(false)
    }
  }

  return {
    changePassword,
    loading,
    error,
  }
}
```

---

### JavaScript Puro / Fetch API

```javascript
/**
 * Altera a senha do administrador autenticado
 * 
 * @param {string} token - Token de autenticação
 * @param {string} currentPassword - Senha atual
 * @param {string} newPassword - Nova senha
 * @param {string} newPasswordConfirmation - Confirmação da nova senha
 * @returns {Promise<Object>} Resposta da API
 */
async function changePassword(token, currentPassword, newPassword, newPasswordConfirmation) {
  try {
    const response = await fetch('/api/admin/change-password', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: newPasswordConfirmation
      })
    })

    const data = await response.json()

    if (!response.ok) {
      // Tratamento de erros
      if (response.status === 401) {
        throw new Error('Senha atual incorreta')
      }
      
      if (response.status === 422) {
        const errorMessages = Object.values(data.errors || {}).flat().join(', ')
        throw new Error(errorMessages || 'Erros de validação')
      }
      
      throw new Error(data.message || 'Erro ao alterar senha')
    }

    return {
      success: true,
      message: data.message,
      data: data.data
    }
  } catch (error) {
    return {
      success: false,
      message: error.message || 'Erro ao alterar senha'
    }
  }
}

// Exemplo de uso
changePassword(
  'seu_token_aqui',
  'senha_atual_123',
  'nova_senha_segura_456',
  'nova_senha_segura_456'
).then(result => {
  if (result.success) {
    console.log('Senha alterada com sucesso!', result.data)
    // Mostrar mensagem de sucesso ao usuário
  } else {
    console.error('Erro:', result.message)
    // Mostrar mensagem de erro ao usuário
  }
})
```

---

## 📝 Notas Importantes

### Segurança

1. **Autenticação Obrigatória**: O endpoint requer um token válido de autenticação
2. **Validação de Senha Atual**: A senha atual é sempre verificada antes de permitir a alteração
3. **Confirmação de Senha**: A nova senha deve ser confirmada para evitar erros de digitação
4. **Mínimo de Caracteres**: A nova senha deve ter no mínimo 8 caracteres

### Validações

- ✅ `current_password` é obrigatório
- ✅ `new_password` é obrigatório e deve ter no mínimo 8 caracteres
- ✅ `new_password_confirmation` é obrigatório e deve ser igual a `new_password`
- ✅ A senha atual deve estar correta

### Boas Práticas

1. **Feedback Visual**: Sempre mostre feedback claro ao usuário (sucesso/erro)
2. **Validação Local**: Valide o formulário antes de enviar a requisição
3. **Limpeza**: Limpe os campos após alteração bem-sucedida
4. **Loading State**: Mostre estado de carregamento durante a requisição
5. **Tratamento de Erros**: Trate todos os casos de erro (401, 422, 500)

### Exemplo de Fluxo

```
1. Usuário preenche formulário
   ↓
2. Validação local (senha mínima, confirmação igual)
   ↓
3. Se válido → Envia requisição
   ↓
4. Mostra loading
   ↓
5a. Sucesso → Mostra mensagem → Limpa formulário
5b. Erro → Mostra mensagem de erro específica
```

---

## 🧪 Teste Rápido (cURL)

```bash
# Substitua {token} e {current_password} pelos valores reais
curl -X POST http://localhost:8000/api/admin/change-password \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "current_password": "senha_atual",
    "new_password": "nova_senha_123",
    "new_password_confirmation": "nova_senha_123"
  }'
```

---

## ✅ Checklist de Implementação

- [ ] Criar composable/hook para chamada da API
- [ ] Criar componente de formulário
- [ ] Implementar validação local
- [ ] Implementar tratamento de erros
- [ ] Adicionar feedback visual (sucesso/erro)
- [ ] Adicionar estado de carregamento
- [ ] Limpar formulário após sucesso
- [ ] Testar todos os cenários (sucesso, erro de validação, senha incorreta)

---

**Última atualização:** 04 de Dezembro de 2025  
**Versão da API:** 1.0

