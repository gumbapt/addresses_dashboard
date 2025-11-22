<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\ZipCode as ZipCodeEntity;
use App\Domain\Repositories\ZipCodeRepositoryInterface;
use App\Models\ZipCode as ZipCodeModel;
use App\Helpers\ZipCodeHelper;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

class ZipCodeRepository implements ZipCodeRepositoryInterface
{
    public function findById(int $id): ?ZipCodeEntity
    {
        $zipCode = ZipCodeModel::find($id);
        
        if (!$zipCode) {
            return null;
        }
        
        return $zipCode->toEntity();
    }

    public function findByCode(string $code): ?ZipCodeEntity
    {
        $normalizedCode = ZipCodeHelper::normalize($code);
        $zipCode = ZipCodeModel::where('code', $normalizedCode)->first();
        
        if (!$zipCode) {
            return null;
        }
        
        return $zipCode->toEntity();
    }

    public function findByState(int $stateId): array
    {
        $zipCodes = ZipCodeModel::where('state_id', $stateId)
            ->orderBy('code')
            ->get();
        
        return $zipCodes->map(function ($zipCode) {
            return $zipCode->toEntity();
        })->toArray();
    }

    public function findByCity(int $cityId): array
    {
        $zipCodes = ZipCodeModel::where('city_id', $cityId)
            ->orderBy('code')
            ->get();
        
        return $zipCodes->map(function ($zipCode) {
            return $zipCode->toEntity();
        })->toArray();
    }

    public function findAll(): array
    {
        $zipCodes = ZipCodeModel::orderBy('code')->get();
        
        return $zipCodes->map(function ($zipCode) {
            return $zipCode->toEntity();
        })->toArray();
    }

    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?int $stateId = null,
        ?int $cityId = null,
        ?bool $isActive = null
    ): array {
        $query = ZipCodeModel::query()->with(['state', 'city']);
        
        // Apply search filter
        if ($search) {
            $query->where('code', 'like', "%{$search}%");
        }
        
        // Filter by state
        if ($stateId !== null) {
            $query->where('state_id', $stateId);
        }
        
        // Filter by city
        if ($cityId !== null) {
            $query->where('city_id', $cityId);
        }
        
        // Apply is_active filter
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }
        
        // Execute pagination
        $paginator = $query->orderBy('code')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'data' => $paginator->items() ? array_values(array_map(fn($zipCode) => $zipCode->toEntity(), $paginator->items())) : [],
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    public function create(
        string $code,
        int $stateId,
        ?int $cityId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $type = null,
        ?int $population = null
    ): ZipCodeEntity {
        $normalizedCode = ZipCodeHelper::normalize($code);
        
        $zipCode = ZipCodeModel::create([
            'code' => $normalizedCode,
            'state_id' => $stateId,
            'city_id' => $cityId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'type' => $type,
            'population' => $population,
            'is_active' => true,
        ]);
        
        return $zipCode->toEntity();
    }

    public function findOrCreate(
        string $code,
        int $stateId,
        ?int $cityId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): ZipCodeEntity {
        $normalizedCode = ZipCodeHelper::normalize($code);
        
        $zipCode = ZipCodeModel::firstOrCreate(
            ['code' => $normalizedCode],
            [
                'state_id' => $stateId,
                'city_id' => $cityId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_active' => true,
            ]
        );
        
        return $zipCode->toEntity();
    }

    public function findOrCreateByCode(
        string $code,
        ?int $stateId = null,
        ?int $cityId = null
    ): ZipCodeEntity {
        $normalizedCode = ZipCodeHelper::normalize($code);
        
        // Prepare default values for creation
        $defaults = [
            'is_active' => true,
        ];
        
        // If stateId not provided, try to infer from zip code
        if (!$stateId) {
            $inferredStateCode = ZipCodeHelper::inferStateFromFirstDigit($normalizedCode);
            if ($inferredStateCode) {
                $state = \App\Models\State::where('code', $inferredStateCode[0] ?? null)->first();
                $stateId = $state ? $state->id : null;
            }
        }
        
        // If still no stateId, use first available state
        if (!$stateId) {
            $firstState = \App\Models\State::where('is_active', true)->first();
            $stateId = $firstState ? $firstState->id : 1;
        }
        
        $defaults['state_id'] = $stateId;
        if ($cityId !== null) {
            $defaults['city_id'] = $cityId;
        }
        
        // Use firstOrCreate to avoid race conditions when multiple workers process reports simultaneously
        // However, in high concurrency scenarios, a race condition can still occur where two workers
        // try to create the same zip code simultaneously. We handle this with a try-catch to retry.
        try {
            $zipCode = ZipCodeModel::firstOrCreate(
                ['code' => $normalizedCode],
                $defaults
            );
        } catch (QueryException|UniqueConstraintViolationException $e) {
            // Handle race condition: if duplicate entry error (1062), try to find the existing record
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                $zipCode = ZipCodeModel::where('code', $normalizedCode)->first();
                
                if (!$zipCode) {
                    // If still not found, throw the original exception
                    throw $e;
                }
            } else {
                // For other database errors, re-throw
                throw $e;
            }
        }
        
        return $zipCode->toEntity();
    }

    public function update(
        int $id,
        ?int $stateId = null,
        ?int $cityId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $type = null,
        ?int $population = null,
        ?bool $isActive = null
    ): ZipCodeEntity {
        $zipCode = ZipCodeModel::findOrFail($id);
        
        $updateData = [];
        
        if ($stateId !== null) $updateData['state_id'] = $stateId;
        if ($cityId !== null) $updateData['city_id'] = $cityId;
        if ($latitude !== null) $updateData['latitude'] = $latitude;
        if ($longitude !== null) $updateData['longitude'] = $longitude;
        if ($type !== null) $updateData['type'] = $type;
        if ($population !== null) $updateData['population'] = $population;
        if ($isActive !== null) $updateData['is_active'] = $isActive;
        
        $zipCode->update($updateData);
        
        return $zipCode->fresh()->toEntity();
    }

    public function delete(int $id): void
    {
        ZipCodeModel::findOrFail($id)->delete();
    }
}

