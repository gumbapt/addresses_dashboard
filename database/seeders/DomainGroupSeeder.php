<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DomainGroup;
use App\Models\Domain;
use App\Models\Admin;
use Illuminate\Support\Str;

class DomainGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar super admin para ser o criador
        $superAdmin = Admin::where('is_super_admin', true)->first();
        
        if (!$superAdmin) {
            $this->command->warn('⚠️  Nenhum Super Admin encontrado. Criando grupos sem created_by.');
        }

        // Criar grupos de domínios (simples como Google Tag Manager)
        $groups = [
            [
                'name' => 'Production',
                'slug' => 'production',
                'description' => 'Domínios de produção com dados reais',
                'is_active' => true,
                'max_domains' => null, // Sem limite
                'settings' => [
                    'environment' => 'production',
                ],
            ],
            [
                'name' => 'Testing',
                'slug' => 'testing',
                'description' => 'Domínios de teste e staging',
                'is_active' => true,
                'max_domains' => null, // Sem limite
                'settings' => [
                    'environment' => 'testing',
                ],
            ],
        ];

        foreach ($groups as $groupData) {
            if ($superAdmin) {
                $groupData['created_by'] = $superAdmin->id;
            }
            
            $group = DomainGroup::create($groupData);
            
            $this->command->info("✅ Grupo criado: {$group->name}");
        }

        // Associar domínios existentes aos grupos
        $this->associateExistingDomains();
    }

    /**
     * Associa domínios existentes aos grupos
     */
    private function associateExistingDomains(): void
    {
        $productionGroup = DomainGroup::where('slug', 'production')->first();
        $testingGroup = DomainGroup::where('slug', 'testing')->first();
        
        if ($productionGroup) {
            // Grupo 1 (Production): zip.50g.io + fiberfinder.com
            $productionDomains = Domain::whereIn('name', [
                'zip.50g.io',
                'fiberfinder.com'
            ])->get();
            
            foreach ($productionDomains as $domain) {
                $domain->update(['domain_group_id' => $productionGroup->id]);
                $this->command->info("   → {$domain->name} → Production");
            }
        }

        if ($testingGroup) {
            // Grupo 2 (Testing): os 3 domínios de teste
            $testingDomains = Domain::whereIn('name', [
                'smarterhome.ai',
                'ispfinder.net',
                'broadbandcheck.io'
            ])->get();
            
            foreach ($testingDomains as $domain) {
                $domain->update(['domain_group_id' => $testingGroup->id]);
                $this->command->info("   → {$domain->name} → Testing");
            }
        }
    }
}
