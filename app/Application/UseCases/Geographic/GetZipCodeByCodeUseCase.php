<?php

namespace App\Application\UseCases\Geographic;

use App\Domain\Entities\ZipCode;
use App\Domain\Repositories\ZipCodeRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;

class GetZipCodeByCodeUseCase
{
    public function __construct(
        private ZipCodeRepositoryInterface $zipCodeRepository
    ) {}

    public function execute(string $code): ZipCode
    {
        $zipCode = $this->zipCodeRepository->findByCode($code);
        
        if (!$zipCode) {
            throw new NotFoundException("ZIP code {$code} not found");
        }
        
        return $zipCode;
    }
}

