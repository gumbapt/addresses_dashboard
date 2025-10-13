<?php

namespace App\Application\UseCases\Provider;

use App\Domain\Entities\Provider;
use App\Domain\Repositories\ProviderRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;

class GetProviderBySlugUseCase
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository
    ) {}

    public function execute(string $slug): Provider
    {
        $provider = $this->providerRepository->findBySlug($slug);
        
        if (!$provider) {
            throw new NotFoundException("Provider with slug {$slug} not found");
        }
        
        return $provider;
    }
}
