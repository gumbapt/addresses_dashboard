<?php

namespace App\Domain\Entities;

use DateTime;

class Admin implements ChatUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly bool $isActive,
        public readonly ?DateTime $lastLoginAt = null
    ) {}

    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function generateToken(): string
    {
        // Lógica simples de geração de token para o Domain
        return md5($this->email . time() . 'admin');
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    // Implementação da interface ChatUser

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getType(): string
    {
        return 'admin';
    }

    // O método isActive() já existe e é compatível com a interface
} 