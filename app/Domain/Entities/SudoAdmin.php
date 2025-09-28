<?php

namespace App\Domain\Entities;

class SudoAdmin extends Admin
{
    public function __construct(
        int $id,
        string $name,
        string $email,
        string $password,
        bool $isActive = true,
        ?\DateTime $lastLoginAt = null
    ) {
        parent::__construct($id, $name, $email, $password, $isActive, $lastLoginAt);
    }

    public function isSuperAdmin(): bool
    {
        return true;
    }

    public function getType(): string
    {
        return 'sudo_admin';
    }

    public function canAccessEverything(): bool
    {
        return true;
    }

    public function hasAllPermissions(): bool
    {
        return true;
    }
}