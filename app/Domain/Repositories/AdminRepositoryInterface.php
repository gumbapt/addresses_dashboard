<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Admin;

interface AdminRepositoryInterface
{
    public function findById(int $id): ?Admin;
    public function findByEmail(string $email): ?Admin;
    public function create(string $name, string $email, string $password): Admin;
    public function updateLastLogin(int $id): void;
} 