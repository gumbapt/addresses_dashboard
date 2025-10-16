<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\ZipCode;

interface ZipCodeRepositoryInterface
{
    public function findById(int $id): ?ZipCode;
    
    public function findByCode(string $code): ?ZipCode;
    
    public function findByState(int $stateId): array;
    
    public function findByCity(int $cityId): array;
    
    public function findAll(): array;
    
    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?int $stateId = null,
        ?int $cityId = null,
        ?bool $isActive = null
    ): array;
    
    public function create(
        string $code,
        int $stateId,
        ?int $cityId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $type = null,
        ?int $population = null
    ): ZipCode;
    
    public function findOrCreate(
        string $code,
        int $stateId,
        ?int $cityId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): ZipCode;
    
    public function findOrCreateByCode(
        string $code,
        ?int $stateId = null,
        ?int $cityId = null
    ): ZipCode;
    
    public function update(
        int $id,
        ?int $stateId = null,
        ?int $cityId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $type = null,
        ?int $population = null,
        ?bool $isActive = null
    ): ZipCode;
    
    public function delete(int $id): void;
}

