<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\City as CityEntity;
use App\Domain\Repositories\CityRepositoryInterface;
use App\Models\City as CityModel;
use Illuminate\Database\QueryException;

class CityRepository implements CityRepositoryInterface
{
    public function findById(int $id): ?CityEntity
    {
        $city = CityModel::find($id);
        
        if (!$city) {
            return null;
        }
        
        return $city->toEntity();
    }

    public function findByNameAndState(string $name, int $stateId): ?CityEntity
    {
        $city = CityModel::where('name', $name)
            ->where('state_id', $stateId)
            ->first();
        
        if (!$city) {
            return null;
        }
        
        return $city->toEntity();
    }

    public function findByState(int $stateId): array
    {
        $cities = CityModel::where('state_id', $stateId)
            ->orderBy('name')
            ->get();
        
        return $cities->map(function ($city) {
            return $city->toEntity();
        })->toArray();
    }

    public function findAll(): array
    {
        $cities = CityModel::orderBy('name')->get();
        
        return $cities->map(function ($city) {
            return $city->toEntity();
        })->toArray();
    }

    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?int $stateId = null,
        ?bool $isActive = null
    ): array {
        $query = CityModel::query()->with('state');
        
        // Apply search filter
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Filter by state
        if ($stateId !== null) {
            $query->where('state_id', $stateId);
        }
        
        // Apply is_active filter
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }
        
        // Execute pagination
        $paginator = $query->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'data' => $paginator->items() ? array_values(array_map(fn($city) => $city->toEntity(), $paginator->items())) : [],
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    public function create(
        string $name,
        int $stateId,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $population = null
    ): CityEntity {
        $city = CityModel::create([
            'name' => $name,
            'state_id' => $stateId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'population' => $population,
            'is_active' => true,
        ]);
        
        return $city->toEntity();
    }

    public function findOrCreate(
        string $name,
        int $stateId,
        ?float $latitude = null,
        ?float $longitude = null
    ): CityEntity {
        try {
            $city = CityModel::firstOrCreate(
                [
                    'name' => $name,
                    'state_id' => $stateId,
                ],
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'is_active' => true,
                ]
            );
        } catch (QueryException $e) {
            // Handle race condition: if duplicate entry error (1062), try to find the existing record
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                $city = CityModel::where('name', $name)
                    ->where('state_id', $stateId)
                    ->first();
                
                if (!$city) {
                    // If still not found, throw the original exception
                    throw $e;
                }
            } else {
                // For other database errors, re-throw
                throw $e;
            }
        }
        
        return $city->toEntity();
    }

    public function findOrCreateByName(
        string $name,
        ?int $stateId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): CityEntity {
        // If stateId is provided, use the regular findOrCreate
        if ($stateId !== null) {
            return $this->findOrCreate($name, $stateId, $latitude, $longitude);
        }
        
        // If stateId is not provided, get default state
        $firstState = \App\Models\State::where('is_active', true)->first();
        $defaultStateId = $firstState ? $firstState->id : 1;
        
        // Use firstOrCreate to avoid race conditions when multiple workers process reports simultaneously
        // However, in high concurrency scenarios, a race condition can still occur where two workers
        // try to create the same city simultaneously. We handle this with a try-catch to retry.
        // Note: We use name + state_id as unique constraint, so we need to use firstOrCreate with both
        try {
            $city = CityModel::firstOrCreate(
                [
                    'name' => $name,
                    'state_id' => $defaultStateId,
                ],
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'is_active' => true,
                ]
            );
        } catch (QueryException $e) {
            // Handle race condition: if duplicate entry error (1062), try to find the existing record
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                $city = CityModel::where('name', $name)
                    ->where('state_id', $defaultStateId)
                    ->first();
                
                if (!$city) {
                    // If still not found, throw the original exception
                    throw $e;
                }
            } else {
                // For other database errors, re-throw
                throw $e;
            }
        }
        
        return $city->toEntity();
    }

    public function update(
        int $id,
        ?string $name = null,
        ?int $stateId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $population = null,
        ?bool $isActive = null
    ): CityEntity {
        $city = CityModel::findOrFail($id);
        
        $updateData = [];
        
        if ($name !== null) $updateData['name'] = $name;
        if ($stateId !== null) $updateData['state_id'] = $stateId;
        if ($latitude !== null) $updateData['latitude'] = $latitude;
        if ($longitude !== null) $updateData['longitude'] = $longitude;
        if ($population !== null) $updateData['population'] = $population;
        if ($isActive !== null) $updateData['is_active'] = $isActive;
        
        $city->update($updateData);
        
        return $city->fresh()->toEntity();
    }

    public function delete(int $id): void
    {
        CityModel::findOrFail($id)->delete();
    }
}

