<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProviderTechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $technologies = [
            [
                'name' => 'Fiber',
                'display_name' => 'Fiber Optic',
                'description' => 'High-speed fiber optic internet connections',
                'is_active' => true,
            ],
            [
                'name' => 'Cable',
                'display_name' => 'Cable Internet',
                'description' => 'Broadband internet via coaxial cable infrastructure',
                'is_active' => true,
            ],
            [
                'name' => 'Mobile',
                'display_name' => 'Mobile/Cellular',
                'description' => 'Internet access via mobile/cellular networks (4G/5G)',
                'is_active' => true,
            ],
            [
                'name' => 'DSL',
                'display_name' => 'Digital Subscriber Line',
                'description' => 'Internet over existing telephone lines',
                'is_active' => true,
            ],
            [
                'name' => 'Satellite',
                'display_name' => 'Satellite Internet',
                'description' => 'Internet connectivity via satellite communication',
                'is_active' => true,
            ],
            [
                'name' => 'Wireless',
                'display_name' => 'Fixed Wireless',
                'description' => 'Fixed wireless broadband connections',
                'is_active' => true,
            ],
        ];

        foreach ($technologies as $technology) {
            DB::table('provider_technologies')->updateOrInsert(
                ['name' => $technology['name']],
                array_merge($technology, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ Created 6 provider technologies successfully!');
    }
}
