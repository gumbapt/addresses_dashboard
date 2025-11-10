<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Domain;

interface DomainRepositoryInterface
{
    public function findById(int $id): ?Domain;
    
    public function findByIds(array $ids): array;
    
    public function findBySlug(string $slug): ?Domain;
    
    public function findByApiKey(string $apiKey): ?Domain;
    
    public function findAll(): array;
    
    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array;
    
    public function findAllActive(): array;
    
    public function create(
        string $name,
        string $domainUrl,
        ?string $siteId = null,
        string $timezone = 'UTC',
        ?string $wordpressVersion = null,
        ?string $pluginVersion = null,
        ?array $settings = null
    ): Domain;
    
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
    ): Domain;
    
    public function delete(int $id): void;
    
    public function activate(int $id): Domain;
    
    public function deactivate(int $id): Domain;
    
    public function regenerateApiKey(int $id): Domain;
    
    public function findAccessibleByAdmin(int $adminId): array;
}

