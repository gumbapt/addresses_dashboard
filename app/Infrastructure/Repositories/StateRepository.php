<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\State as StateEntity;
use App\Domain\Repositories\StateRepositoryInterface;
use App\Models\State as StateModel;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

class StateRepository implements StateRepositoryInterface
{
    public function findById(int $id): ?StateEntity
    {
        $state = StateModel::find($id);
        
        if (!$state) {
            return null;
        }
        
        return $state->toEntity();
    }

    public function findByCode(string $code): ?StateEntity
    {
        $state = StateModel::where('code', strtoupper($code))->first();
        
        if (!$state) {
            return null;
        }
        
        return $state->toEntity();
    }

    public function findOrCreateByCode(string $code, ?string $name = null): StateEntity
    {
        $normalizedCode = strtoupper($code);
        
        // Use firstOrCreate to avoid race conditions when multiple workers process reports simultaneously
        // However, in high concurrency scenarios, a race condition can still occur where two workers
        // try to create the same state simultaneously. We handle this with a try-catch to retry.
        try {
            $state = StateModel::firstOrCreate( 
                ['code' => $normalizedCode],
                [
                    'name' => $name ?? $normalizedCode,
                    'timezone' => 'America/New_York', // Default timezone
                    'is_active' => true,
                ]
            );
        } catch (QueryException|UniqueConstraintViolationException $e) {
            // Handle race condition: if duplicate entry error (1062), try to find the existing record
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                $state = StateModel::where('code', $normalizedCode)->first();
                
                if (!$state) {
                    // If still not found, throw the original exception
                    throw $e;
                }
            } else {
                // For other database errors, re-throw
                throw $e;
            }
        }
        
        return $state->toEntity();
    }

    public function findAll(): array
    {
        $states = StateModel::orderBy('name')->get();
        
        return $states->map(function ($state) {
            return $state->toEntity();
        })->toArray();
    }

    public function findAllActive(): array
    {
        $states = StateModel::where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return $states->map(function ($state) {
            return $state->toEntity();
        })->toArray();
    }

    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): array {
        $query = StateModel::query();
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        // Apply is_active filter
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }
        
        // Execute pagination
        $paginator = $query->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'data' => $paginator->items() ? array_values(array_map(fn($state) => $state->toEntity(), $paginator->items())) : [],
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}

