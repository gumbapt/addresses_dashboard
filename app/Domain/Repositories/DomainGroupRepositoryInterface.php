<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\DomainGroup;

interface DomainGroupRepositoryInterface
{
    /**
     * Find domain group by ID
     */
    public function findById(int $id): ?DomainGroup;

    /**
     * Find domain group by slug
     */
    public function findBySlug(string $slug): ?DomainGroup;

    /**
     * Get all domain groups
     */
    public function findAll(): array;

    /**
     * Get paginated domain groups
     */
    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array;

    /**
     * Create new domain group
     */
    public function create(
        string $name,
        string $slug,
        ?string $description = null,
        bool $isActive = true,
        ?array $settings = null,
        ?int $maxDomains = null,
        ?int $createdBy = null
    ): DomainGroup;

    /**
     * Update domain group
     */
    public function update(
        int $id,
        ?string $name = null,
        ?string $slug = null,
        ?string $description = null,
        ?bool $isActive = null,
        ?array $settings = null,
        ?int $maxDomains = null,
        ?int $updatedBy = null
    ): DomainGroup;

    /**
     * Delete domain group
     */
    public function delete(int $id): bool;

    /**
     * Get active domain groups
     */
    public function findActive(): array;

    /**
     * Get domain groups with domains count
     */
    public function findAllWithDomainsCount(): array;

    /**
     * Check if group has reached max domains limit
     */
    public function hasReachedMaxDomains(int $groupId): bool;

    /**
     * Get domains count for a group
     */
    public function getDomainsCount(int $groupId): int;
}

