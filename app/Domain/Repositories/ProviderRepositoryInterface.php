<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Provider;

interface ProviderRepositoryInterface
{
    public function findById(int $id): ?Provider;
    
    public function findByName(string $name): ?Provider;
    
    public function findBySlug(string $slug): ?Provider;
    
    public function findAll(): array;
    
    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?string $technology = null,
        ?bool $isActive = null
    ): array;
    
    public function findByTechnology(string $technology): array;
    
    public function create(
        string $name,
        ?string $website = null,
        ?string $logoUrl = null,
        ?string $description = null,
        array $technologies = []
    ): Provider;
    
    public function findOrCreate(
        string $name,
        array $technologies = [],
        ?string $website = null
    ): Provider;
    
    public function update(
        int $id,
        ?string $name = null,
        ?string $website = null,
        ?string $logoUrl = null,
        ?string $description = null,
        ?array $technologies = null,
        ?bool $isActive = null
    ): Provider;
    
    public function delete(int $id): void;
    
    public function addTechnology(int $providerId, string $technology): void;
    
    public function removeTechnology(int $providerId, string $technology): void;
}
