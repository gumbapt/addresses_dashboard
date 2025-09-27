<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permissões de Admin
        Permission::create([
            'slug' => 'admin-create',
            'name' => 'Criar Administrador',
            'description' => 'Permite criar novos administradores',
            'resource' => 'admin',
            'action' => 'create',
        ]);

        Permission::create([
            'slug' => 'admin-read',
            'name' => 'Visualizar Administrador',
            'description' => 'Permite visualizar informações de administradores',
            'resource' => 'admin',
            'action' => 'read',
        ]);

        Permission::create([
            'slug' => 'admin-update',
            'name' => 'Editar Administrador',
            'description' => 'Permite editar informações de administradores',
            'resource' => 'admin',
            'action' => 'update',
        ]);

        Permission::create([
            'slug' => 'admin-delete',
            'name' => 'Excluir Administrador',
            'description' => 'Permite excluir administradores',
            'resource' => 'admin',
            'action' => 'delete',
        ]);

        Permission::create([
            'slug' => 'admin-manage',
            'name' => 'Gerenciar Administradores',
            'description' => 'Permite gerenciar todos os aspectos dos administradores',
            'resource' => 'admin',
            'action' => 'manage',
        ]);

        Permission::create([
            'slug' => 'user-create',
            'name' => 'Criar Usuário',
            'description' => 'Permite criar novos usuários',
            'resource' => 'user',
            'action' => 'create',
        ]);

        Permission::create([
            'slug' => 'user-read',
            'name' => 'Visualizar Usuário',
            'description' => 'Permite visualizar informações de usuários',
            'resource' => 'user',
            'action' => 'read',
        ]);

        Permission::create([
            'slug' => 'user-update',
            'name' => 'Editar Usuário',
            'description' => 'Permite editar informações de usuários',
            'resource' => 'user',
            'action' => 'update',
        ]);

        Permission::create([
            'slug' => 'user-delete',
            'name' => 'Excluir Usuário',
            'description' => 'Permite excluir usuários',
            'resource' => 'user',
            'action' => 'delete',
        ]);

        // Permissões de Chat
        Permission::create([
            'slug' => 'chat-manage',
            'name' => 'Gerenciar Chat',
            'description' => 'Permite gerenciar chats e mensagens',
            'resource' => 'chat',
            'action' => 'manage',
        ]);

        Permission::create([
            'slug' => 'chat-read',
            'name' => 'Visualizar Chat',
            'description' => 'Permite visualizar chats e mensagens',
            'resource' => 'chat',
            'action' => 'read',
        ]);

        // Permissões de Role
        Permission::create([
            'slug' => 'role-assign',
            'name' => 'Atribuir Roles',
            'description' => 'Permite atribuir roles a usuários e administradores',
            'resource' => 'role',
            'action' => 'assign',
        ]);

        Permission::create([
            'slug' => 'role-manage',
            'name' => 'Gerenciar Roles',
            'description' => 'Permite criar, editar e excluir roles',
            'resource' => 'role',
            'action' => 'manage',
        ]);
    }
}
