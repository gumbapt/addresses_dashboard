<?php

namespace App\Application\UseCases\Geographic;

use App\Domain\Entities\State;
use App\Domain\Repositories\StateRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;

class GetStateByCodeUseCase
{
    public function __construct(
        private StateRepositoryInterface $stateRepository
    ) {}

    public function execute(string $code): State
    {
        $state = $this->stateRepository->findByCode($code);
        
        if (!$state) {
            throw new NotFoundException("State with code {$code} not found");
        }
        
        return $state;
    }
}

