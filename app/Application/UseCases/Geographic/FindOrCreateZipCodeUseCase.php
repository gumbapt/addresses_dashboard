<?php

namespace App\Application\UseCases\Geographic;

use App\Domain\Entities\ZipCode;
use App\Domain\Repositories\ZipCodeRepositoryInterface;

class FindOrCreateZipCodeUseCase
{
    public function __construct(
        private ZipCodeRepositoryInterface $zipCodeRepository
    ) {}

    public function execute(
        string $code,
        int $stateId,
        ?int $cityId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): ZipCode {
        return $this->zipCodeRepository->findOrCreate($code, $stateId, $cityId, $latitude, $longitude);
    }
}

