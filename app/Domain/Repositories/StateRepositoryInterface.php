<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\State;

interface StateRepositoryInterface
{
    public function findById(int $id): ?State;
    
    public function findByCode(string $code): ?State;
    
    public function findOrCreateByCode(string $code, ?string $name = null): State;
    
    public function findAll(): array;
    
    public function findAllActive(): array;
    
    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array;
}

