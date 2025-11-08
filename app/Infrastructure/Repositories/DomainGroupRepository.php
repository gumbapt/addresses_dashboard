<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\DomainGroup as DomainGroupEntity;
use App\Domain\Repositories\DomainGroupRepositoryInterface;
use App\Models\DomainGroup as DomainGroupModel;
use Illuminate\Support\Str;

class DomainGroupRepository implements DomainGroupRepositoryInterface
{
    public function findById(int $id): ?DomainGroupEntity
    {
        $group = DomainGroupModel::find($id);
        
        if (!$group) {
            return null;
        }
        
        return $this->toEntity($group);
    }

    public function findBySlug(string $slug): ?DomainGroupEntity
    {
        $group = DomainGroupModel::where('slug', $slug)->first();
        
        if (!$group) {
            return null;
        }
        
        return $this->toEntity($group);
    }

    public function findAll(): array
    {
        $groups = DomainGroupModel::all();
        
        return $groups->map(function ($group) {
            return $this->toEntity($group);
        })->toArray();
    }

    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array {
        $query = DomainGroupModel::query();
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
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
            'data' => array_map(fn($group) => $this->toEntity($group), $paginator->items()),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    public function create(
        string $name,
        string $slug,
        ?string $description = null,
        bool $isActive = true,
        ?array $settings = null,
        ?int $maxDomains = null,
        ?int $createdBy = null
    ): DomainGroupEntity {
        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (DomainGroupModel::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        $group = DomainGroupModel::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_active' => $isActive,
            'settings' => $settings,
            'max_domains' => $maxDomains,
            'created_by' => $createdBy,
        ]);
        
        return $this->toEntity($group);
    }

    public function update(
        int $id,
        ?string $name = null,
        ?string $slug = null,
        ?string $description = null,
        ?bool $isActive = null,
        ?array $settings = null,
        ?int $maxDomains = null,
        ?int $updatedBy = null
    ): DomainGroupEntity {
        $group = DomainGroupModel::findOrFail($id);
        
        $updateData = array_filter([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_active' => $isActive,
            'settings' => $settings,
            'max_domains' => $maxDomains,
            'updated_by' => $updatedBy,
        ], fn($value) => !is_null($value));
        
        $group->update($updateData);
        
        return $this->toEntity($group->fresh());
    }

    public function delete(int $id): bool
    {
        $group = DomainGroupModel::find($id);
        
        if (!$group) {
            return false;
        }
        
        return $group->delete();
    }

    public function findActive(): array
    {
        $groups = DomainGroupModel::where('is_active', true)->get();
        
        return $groups->map(function ($group) {
            return $this->toEntity($group);
        })->toArray();
    }

    public function findAllWithDomainsCount(): array
    {
        $groups = DomainGroupModel::withCount('domains')->get();
        
        return $groups->map(function ($group) {
            $entity = $this->toEntity($group);
            
            return [
                'entity' => $entity,
                'domains_count' => $group->domains_count,
            ];
        })->toArray();
    }

    public function hasReachedMaxDomains(int $groupId): bool
    {
        $group = DomainGroupModel::find($groupId);
        
        if (!$group) {
            return false;
        }
        
        return $group->hasReachedMaxDomains();
    }

    public function getDomainsCount(int $groupId): int
    {
        $group = DomainGroupModel::find($groupId);
        
        if (!$group) {
            return 0;
        }
        
        return $group->domains()->count();
    }

    /**
     * Converte Model para Entity
     */
    private function toEntity(DomainGroupModel $group): DomainGroupEntity
    {
        return new DomainGroupEntity(
            id: $group->id,
            name: $group->name,
            slug: $group->slug,
            description: $group->description,
            isActive: $group->is_active,
            settings: $group->settings,
            maxDomains: $group->max_domains,
            createdBy: $group->created_by,
            updatedBy: $group->updated_by,
            createdAt: $group->created_at,
            updatedAt: $group->updated_at,
        );
    }
}

