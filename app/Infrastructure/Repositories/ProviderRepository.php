<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Provider as ProviderEntity;
use App\Domain\Repositories\ProviderRepositoryInterface;
use App\Models\Provider as ProviderModel;
use App\Helpers\ProviderHelper;

class ProviderRepository implements ProviderRepositoryInterface
{
    public function findById(int $id): ?ProviderEntity
    {
        $provider = ProviderModel::find($id);
        
        if (!$provider) {
            return null;
        }
        
        return $provider->toEntity();
    }

    public function findByName(string $name): ?ProviderEntity
    {
        $provider = ProviderModel::where('name', $name)->first();
        
        if (!$provider) {
            return null;
        }
        
        return $provider->toEntity();
    }

    public function findBySlug(string $slug): ?ProviderEntity
    {
        $provider = ProviderModel::where('slug', $slug)->first();
        
        if (!$provider) {
            return null;
        }
        
        return $provider->toEntity();
    }

    public function findAll(): array
    {
        $providers = ProviderModel::orderBy('name')->get();
        
        return $providers->map(function ($provider) {
            return $provider->toEntity();
        })->toArray();
    }

    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?string $technology = null,
        ?bool $isActive = null
    ): array {
        $query = ProviderModel::query();
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Filter by technology
        if ($technology) {
            $query->whereJsonContains('technologies', $technology);
        }
        
        // Apply is_active filter
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }
        
        // Execute pagination
        $paginator = $query->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'data' => $paginator->items() ? array_values(array_map(fn($provider) => $provider->toEntity(), $paginator->items())) : [],
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    public function findByTechnology(string $technology): array
    {
        $providers = ProviderModel::whereJsonContains('technologies', $technology)
            ->orderBy('name')
            ->get();
        
        return $providers->map(function ($provider) {
            return $provider->toEntity();
        })->toArray();
    }

    public function create(
        string $name,
        ?string $website = null,
        ?string $logoUrl = null,
        ?string $description = null,
        array $technologies = []
    ): ProviderEntity {
        $normalizedName = ProviderHelper::normalizeName($name);
        $slug = $this->generateUniqueSlug($normalizedName);
        
        $provider = ProviderModel::create([
            'name' => $normalizedName,
            'slug' => $slug,
            'website' => ProviderHelper::normalizeWebsite($website),
            'logo_url' => $logoUrl,
            'description' => $description ?: ProviderHelper::generateDescription($technologies),
            'technologies' => $technologies,
            'is_active' => true,
        ]);
        
        return $provider->toEntity();
    }

    public function findOrCreate(
        string $name,
        array $technologies = [],
        ?string $website = null
    ): ProviderEntity {
        $normalizedName = ProviderHelper::normalizeName($name);
        
        $provider = ProviderModel::firstOrCreate(
            ['name' => $normalizedName],
            [
                'slug' => $this->generateUniqueSlug($normalizedName),
                'website' => ProviderHelper::normalizeWebsite($website),
                'description' => ProviderHelper::generateDescription($technologies),
                'technologies' => $technologies,
                'is_active' => true,
            ]
        );
        
        // Update technologies if provider exists but has new technologies
        if (!$provider->wasRecentlyCreated && !empty($technologies)) {
            $existingTechs = $provider->technologies ?? [];
            $mergedTechs = array_unique(array_merge($existingTechs, $technologies));
            
            if ($mergedTechs !== $existingTechs) {
                $provider->update(['technologies' => $mergedTechs]);
            }
        }
        
        return $provider->toEntity();
    }

    public function update(
        int $id,
        ?string $name = null,
        ?string $website = null,
        ?string $logoUrl = null,
        ?string $description = null,
        ?array $technologies = null,
        ?bool $isActive = null
    ): ProviderEntity {
        $provider = ProviderModel::findOrFail($id);
        
        $updateData = [];
        
        if ($name !== null) {
            $normalizedName = ProviderHelper::normalizeName($name);
            $updateData['name'] = $normalizedName;
            $updateData['slug'] = $this->generateUniqueSlug($normalizedName, $id);
        }
        if ($website !== null) $updateData['website'] = ProviderHelper::normalizeWebsite($website);
        if ($logoUrl !== null) $updateData['logo_url'] = $logoUrl;
        if ($description !== null) $updateData['description'] = $description;
        if ($technologies !== null) $updateData['technologies'] = $technologies;
        if ($isActive !== null) $updateData['is_active'] = $isActive;
        
        $provider->update($updateData);
        
        return $provider->fresh()->toEntity();
    }

    public function delete(int $id): void
    {
        ProviderModel::findOrFail($id)->delete();
    }

    public function addTechnology(int $providerId, string $technology): void
    {
        $provider = ProviderModel::findOrFail($providerId);
        $technologies = $provider->technologies ?? [];
        
        if (!in_array($technology, $technologies)) {
            $technologies[] = $technology;
            $provider->update(['technologies' => $technologies]);
        }
    }

    public function removeTechnology(int $providerId, string $technology): void
    {
        $provider = ProviderModel::findOrFail($providerId);
        $technologies = $provider->technologies ?? [];
        
        $technologies = array_filter($technologies, fn($tech) => $tech !== $technology);
        $provider->update(['technologies' => array_values($technologies)]);
    }

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = ProviderHelper::generateSlug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = ProviderModel::where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}
