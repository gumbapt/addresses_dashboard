<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProviderPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Provider permissions
        $permissions = [
            [
                'slug' => 'provider-create',
                'name' => 'Create Provider',
                'description' => 'Allows creating new providers',
                'resource' => 'provider',
                'action' => 'create',
                'route' => 'provider/create',
            ],
            [
                'slug' => 'provider-read',
                'name' => 'View Provider',
                'description' => 'Allows viewing provider information',
                'resource' => 'provider',
                'action' => 'read',
                'route' => 'provider/read',
            ],
            [
                'slug' => 'provider-update',
                'name' => 'Update Provider',
                'description' => 'Allows updating provider information',
                'resource' => 'provider',
                'action' => 'update',
                'route' => 'provider/update',
            ],
            [
                'slug' => 'provider-delete',
                'name' => 'Delete Provider',
                'description' => 'Allows deleting providers',
                'resource' => 'provider',
                'action' => 'delete',
                'route' => 'provider/delete',
            ],
            [
                'slug' => 'provider-manage',
                'name' => 'Manage Provider',
                'description' => 'Allows managing all aspects of providers including technologies',
                'resource' => 'provider',
                'action' => 'manage',
                'route' => 'provider/manage',
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        echo "✅ Provider permissions created successfully!\n";
    }
}
