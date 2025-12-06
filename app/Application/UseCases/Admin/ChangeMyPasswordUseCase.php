<?php

namespace App\Application\UseCases\Admin;

use App\Domain\Repositories\AdminRepositoryInterface;
use App\Domain\Exceptions\AuthenticationException;

class ChangeMyPasswordUseCase
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository
    ) {}

    /**
     * Altera a senha do próprio administrador
     * 
     * @param int $adminId ID do admin autenticado
     * @param string $currentPassword Senha atual para validação
     * @param string $newPassword Nova senha
     * @return array Dados do admin atualizado
     * @throws AuthenticationException Se a senha atual estiver incorreta
     * @throws \Exception Se o admin não for encontrado
     */
    public function execute(
        int $adminId,
        string $currentPassword,
        string $newPassword
    ): array {
        // Buscar o admin
        $admin = $this->adminRepository->findById($adminId);
        
        if (!$admin) {
            throw new \Exception("Admin not found");
        }

        // Verificar se a senha atual está correta
        if (!$admin->validatePassword($currentPassword)) {
            throw new AuthenticationException("Current password is incorrect");
        }

        // Atualizar a senha
        $updatedAdmin = $this->adminRepository->update(
            $adminId,
            null, // name
            null, // email
            $newPassword, // password
            null  // is_active
        );

        return $updatedAdmin->toDto()->toArray();
    }
}

