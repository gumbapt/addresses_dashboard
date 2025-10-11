<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Domain as DomainEntity;
use App\Domain\Repositories\DomainRepositoryInterface;
use App\Models\Domain as DomainModel;
use Illuminate\Support\Str;

class DomainRepository implements DomainRepositoryInterface
{
    public function findById(int $id): ?DomainEntity
    {
        $domain = DomainModel::find($id);
        
        if (!$domain) {
            return null;
        }
        
        return $domain->toEntity();
    }

    public function findBySlug(string $slug): ?DomainEntity
    {
        $domain = DomainModel::where('slug', $slug)->first();
        
        if (!$domain) {
            return null;
        }
        
        return $domain->toEntity();
    }

    public function findByApiKey(string $apiKey): ?DomainEntity
    {
        $domain = DomainModel::where('api_key', $apiKey)->first();
        
        if (!$domain) {
            return null;
        }
        
        return $domain->toEntity();
    }

    public function findAll(): array
    {
        $domains = DomainModel::all();
        
        return $domains->map(function ($domain) {
            return $domain->toEntity();
        })->toArray();
    }

    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array {
        $query = DomainModel::query();
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('domain_url', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        
        // Apply is_active filter
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }
        
        // Execute pagination
        $paginator = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'data' => $paginator->items() ? array_values(array_map(fn($domain) => $domain->toEntity(), $paginator->items())) : [],
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    public function findAllActive(): array
    {
        $domains = DomainModel::where('is_active', true)->get();
        
        return $domains->map(function ($domain) {
            return $domain->toEntity();
        })->toArray();
    }

    public function create(
        string $name,
        string $domainUrl,
        ?string $siteId = null,
        string $timezone = 'UTC',
        ?string $wordpressVersion = null,
        ?string $pluginVersion = null,
        ?array $settings = null
    ): DomainEntity {
        $slug = Str::slug($name);
        
        // Generate unique API key
        $apiKey = 'sk_live_' . Str::random(64);
        
        $domain = DomainModel::create([
            'name' => $name,
            'slug' => $slug,
            'domain_url' => $domainUrl,
            'site_id' => $siteId ?? '',
            'api_key' => $apiKey,
            'status' => 'active',
            'timezone' => $timezone,
            'wordpress_version' => $wordpressVersion ?? '',
            'plugin_version' => $pluginVersion ?? '',
            'settings' => $settings ?? [],
            'is_active' => true,
        ]);
        
        return $domain->toEntity();
    }

    public function update(
        int $id,
        ?string $name = null,
        ?string $domainUrl = null,
        ?string $siteId = null,
        ?bool $isActive = null,
        ?string $timezone = null,
        ?string $wordpressVersion = null,
        ?string $pluginVersion = null,
        ?array $settings = null
    ): DomainEntity {
        $domain = DomainModel::findOrFail($id);
        
        $updateData = [];
        
        if ($name !== null) {
            $updateData['name'] = $name;
            $updateData['slug'] = Str::slug($name);
        }
        if ($domainUrl !== null) $updateData['domain_url'] = $domainUrl;
        if ($siteId !== null) $updateData['site_id'] = $siteId;
        if ($isActive !== null) $updateData['is_active'] = $isActive;
        if ($timezone !== null) $updateData['timezone'] = $timezone;
        if ($wordpressVersion !== null) $updateData['wordpress_version'] = $wordpressVersion;
        if ($pluginVersion !== null) $updateData['plugin_version'] = $pluginVersion;
        if ($settings !== null) $updateData['settings'] = $settings;
        
        $domain->update($updateData);
        
        return $domain->fresh()->toEntity();
    }

    public function delete(int $id): void
    {
        DomainModel::findOrFail($id)->delete();
    }

    public function activate(int $id): DomainEntity
    {
        $domain = DomainModel::findOrFail($id);
        $domain->update([
            'is_active' => true,
            'status' => 'active'
        ]);
        
        return $domain->fresh()->toEntity();
    }

    public function deactivate(int $id): DomainEntity
    {
        $domain = DomainModel::findOrFail($id);
        $domain->update([
            'is_active' => false,
            'status' => 'inactive'
        ]);
        
        return $domain->fresh()->toEntity();
    }

    public function regenerateApiKey(int $id): DomainEntity
    {
        $domain = DomainModel::findOrFail($id);
        
        $newApiKey = 'sk_live_' . Str::random(64);
        
        $domain->update(['api_key' => $newApiKey]);
        
        return $domain->fresh()->toEntity();
    }

    public function findAccessibleByAdmin(int $adminId): array
    {
        // TODO: Implementar quando criar a tabela admin_domain_access
        // Por enquanto, retorna todos os domínios ativos
        return $this->findAllActive();
    }
}

