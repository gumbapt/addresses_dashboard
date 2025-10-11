<?php

namespace App\Application\UseCases\Geographic;

use App\Domain\Entities\City;
use App\Domain\Repositories\CityRepositoryInterface;

class FindOrCreateCityUseCase
{
    public function __construct(
        private CityRepositoryInterface $cityRepository
    ) {}

    public function execute(
        string $name,
        int $stateId,
        ?float $latitude = null,
        ?float $longitude = null
    ): City {
        return $this->cityRepository->findOrCreate($name, $stateId, $latitude, $longitude);
    }
}

