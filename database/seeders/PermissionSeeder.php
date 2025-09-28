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
        // Admin permissions
        Permission::create([
            'slug' => 'admin-create',
            'name' => 'Create Administrator',
            'description' => 'Allows creating new administrators',
            'resource' => 'admin',
            'action' => 'create',
        ]);

        Permission::create([
            'slug' => 'admin-read',
            'name' => 'View Administrator',
            'description' => 'Allows viewing administrator information',
            'resource' => 'admin',
            'action' => 'read',
        ]);

        Permission::create([
            'slug' => 'admin-update',
            'name' => 'Edit Administrator',
            'description' => 'Allows editing administrator information',
            'resource' => 'admin',
            'action' => 'update',
        ]);

        Permission::create([
            'slug' => 'admin-delete',
            'name' => 'Delete Administrator',
            'description' => 'Allows deleting administrators',
            'resource' => 'admin',
            'action' => 'delete',
        ]);

        Permission::create([
            'slug' => 'admin-manage',
            'name' => 'Manage Administrators',
            'description' => 'Allows managing all aspects of administrators',
            'resource' => 'admin',
            'action' => 'manage',
        ]);

        Permission::create([
            'slug' => 'user-create',
            'name' => 'Create User',
            'description' => 'Allows creating new users',
            'resource' => 'user',
            'action' => 'create',
        ]);

        Permission::create([
            'slug' => 'user-read',
            'name' => 'View User',
            'description' => 'Allows viewing user information',
            'resource' => 'user',
            'action' => 'read',
        ]);

        Permission::create([
            'slug' => 'user-update',
            'name' => 'Edit User',
            'description' => 'Allows editing user information',
            'resource' => 'user',
            'action' => 'update',
        ]);

        Permission::create([
            'slug' => 'user-delete',
            'name' => 'Delete User',
            'description' => 'Allows deleting users',
            'resource' => 'user',
            'action' => 'delete',
        ]);

        // Chat permissions
        Permission::create([
            'slug' => 'chat-manage',
            'name' => 'Manage Chat',
            'description' => 'Allows managing chats and messages',
            'resource' => 'chat',
            'action' => 'manage',
        ]);

        Permission::create([
            'slug' => 'chat-read',
            'name' => 'View Chat',
            'description' => 'Allows viewing chats and messages',
            'resource' => 'chat',
            'action' => 'read',
        ]);

        // Role permissions
        Permission::create([
            'slug' => 'role-assign',
            'name' => 'Assign Roles',
            'description' => 'Allows assigning roles to users and administrators',
            'resource' => 'role',
            'action' => 'assign',
        ]);

        Permission::create([
            'slug' => 'role-manage',
            'name' => 'Manage Roles',
            'description' => 'Allows creating, editing and deleting roles',
            'resource' => 'role',
            'action' => 'manage',
        ]);
    }
}
