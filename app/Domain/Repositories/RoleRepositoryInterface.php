<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Role;

interface RoleRepositoryInterface
{
    public function findById(int $id): ?Role;
    public function findByName(string $name): ?Role;
    public function findAll(): array;
    public function create(string $name, string $description, string $display_name): Role;
    public function update(int $id, string $name, string $description, string $display_name): void;
    public function delete(int $id): void;
}