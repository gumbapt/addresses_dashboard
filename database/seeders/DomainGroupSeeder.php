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

        // Criar grupos de domínios
        $groups = [
            [
                'name' => 'Production Domains',
                'slug' => 'production-domains',
                'description' => 'Domínios de produção ativos e gerando relatórios reais',
                'is_active' => true,
                'max_domains' => null, // Sem limite
                'settings' => [
                    'environment' => 'production',
                    'monitoring' => true,
                    'alerts_enabled' => true,
                ],
            ],
            [
                'name' => 'Staging Domains',
                'slug' => 'staging-domains',
                'description' => 'Domínios de teste e homologação',
                'is_active' => true,
                'max_domains' => 10,
                'settings' => [
                    'environment' => 'staging',
                    'monitoring' => false,
                    'alerts_enabled' => false,
                ],
            ],
            [
                'name' => 'Development Domains',
                'slug' => 'development-domains',
                'description' => 'Domínios de desenvolvimento e testes locais',
                'is_active' => true,
                'max_domains' => 5,
                'settings' => [
                    'environment' => 'development',
                    'monitoring' => false,
                    'alerts_enabled' => false,
                ],
            ],
            [
                'name' => 'Premium Partners',
                'slug' => 'premium-partners',
                'description' => 'Domínios de parceiros premium com recursos avançados',
                'is_active' => true,
                'max_domains' => 20,
                'settings' => [
                    'tier' => 'premium',
                    'support' => 'priority',
                    'custom_branding' => true,
                ],
            ],
            [
                'name' => 'Trial Domains',
                'slug' => 'trial-domains',
                'description' => 'Domínios em período de teste',
                'is_active' => true,
                'max_domains' => 3,
                'settings' => [
                    'tier' => 'trial',
                    'trial_days' => 30,
                    'trial_started_at' => now()->toDateString(),
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
        $productionGroup = DomainGroup::where('slug', 'production-domains')->first();
        
        if ($productionGroup) {
            // Domínios reais vão para Production
            $realDomains = Domain::whereIn('name', ['zip.50g.io'])->get();
            foreach ($realDomains as $domain) {
                $domain->update(['domain_group_id' => $productionGroup->id]);
                $this->command->info("   → {$domain->name} → Production Domains");
            }

            // Domínios fictícios vão para Staging
            $stagingGroup = DomainGroup::where('slug', 'staging-domains')->first();
            if ($stagingGroup) {
                $fictionalDomains = Domain::whereIn('name', [
                    'smarterhome.ai',
                    'ispfinder.net',
                    'broadbandcheck.io'
                ])->get();
                
                foreach ($fictionalDomains as $domain) {
                    $domain->update(['domain_group_id' => $stagingGroup->id]);
                    $this->command->info("   → {$domain->name} → Staging Domains");
                }
            }
        }
    }
}
