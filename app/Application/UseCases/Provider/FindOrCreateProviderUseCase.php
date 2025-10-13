<?php

namespace App\Application\UseCases\Provider;

use App\Domain\Entities\Provider;
use App\Domain\Repositories\ProviderRepositoryInterface;

class FindOrCreateProviderUseCase
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository
    ) {}

    public function execute(
        string $name,
        array $technologies = [],
        ?string $website = null
    ): Provider {
        return $this->providerRepository->findOrCreate($name, $technologies, $website);
    }
}
