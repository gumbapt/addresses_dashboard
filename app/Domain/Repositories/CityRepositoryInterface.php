<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\City;

interface CityRepositoryInterface
{
    public function findById(int $id): ?City;
    
    public function findByNameAndState(string $name, int $stateId): ?City;
    
    public function findByState(int $stateId): array;
    
    public function findAll(): array;
    
    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?int $stateId = null,
        ?bool $isActive = null
    ): array;
    
    public function create(
        string $name,
        int $stateId,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $population = null
    ): City;
    
    public function findOrCreate(
        string $name,
        int $stateId,
        ?float $latitude = null,
        ?float $longitude = null
    ): City;
    
    public function findOrCreateByName(
        string $name,
        ?int $stateId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): City;
    
    public function update(
        int $id,
        ?string $name = null,
        ?int $stateId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $population = null,
        ?bool $isActive = null
    ): City;
    
    public function delete(int $id): void;
}

