<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DomainGroupController extends Controller
{
    /**
     * List all domain groups
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $isActive = $request->get('is_active');

        $query = DomainGroup::with(['domains', 'creator', 'updater']);

        // Filtro por nome
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtro por status
        if (!is_null($isActive)) {
            $query->where('is_active', $isActive);
        }

        $domainGroups = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $domainGroups->items(),
            'pagination' => [
                'total' => $domainGroups->total(),
                'per_page' => $domainGroups->perPage(),
                'current_page' => $domainGroups->currentPage(),
                'last_page' => $domainGroups->lastPage(),
                'from' => $domainGroups->firstItem(),
                'to' => $domainGroups->lastItem(),
            ],
        ]);
    }

    /**
     * Show single domain group
     */
    public function show($id)
    {
        $domainGroup = DomainGroup::with(['domains', 'creator', 'updater'])->find($id);

        if (!$domainGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Domain group not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $domainGroup->id,
                'name' => $domainGroup->name,
                'slug' => $domainGroup->slug,
                'description' => $domainGroup->description,
                'is_active' => $domainGroup->is_active,
                'settings' => $domainGroup->settings,
                'max_domains' => $domainGroup->max_domains,
                'domains_count' => $domainGroup->domains->count(),
                'available_domains' => $domainGroup->getAvailableDomainsCount(),
                'has_reached_limit' => $domainGroup->hasReachedMaxDomains(),
                'domains' => $domainGroup->domains->map(function($domain) {
                    return [
                        'id' => $domain->id,
                        'name' => $domain->name,
                        'slug' => $domain->slug,
                        'domain_url' => $domain->domain_url,
                        'is_active' => $domain->is_active,
                    ];
                }),
                'created_by' => $domainGroup->creator ? [
                    'id' => $domainGroup->creator->id,
                    'name' => $domainGroup->creator->name,
                    'email' => $domainGroup->creator->email,
                ] : null,
                'updated_by' => $domainGroup->updater ? [
                    'id' => $domainGroup->updater->id,
                    'name' => $domainGroup->updater->name,
                    'email' => $domainGroup->updater->email,
                ] : null,
                'created_at' => $domainGroup->created_at,
                'updated_at' => $domainGroup->updated_at,
            ],
        ]);
    }

    /**
     * Create new domain group
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:domain_groups,name',
            'slug' => 'nullable|string|max:255|unique:domain_groups,slug',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
            'max_domains' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        // Gerar slug se não fornecido
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Adicionar created_by
        $data['created_by'] = $request->user()->id;

        $domainGroup = DomainGroup::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Domain group created successfully.',
            'data' => $domainGroup->load(['domains', 'creator']),
        ], 201);
    }

    /**
     * Update domain group
     */
    public function update(Request $request, $id)
    {
        $domainGroup = DomainGroup::find($id);

        if (!$domainGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Domain group not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:domain_groups,name,' . $id,
            'slug' => 'nullable|string|max:255|unique:domain_groups,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
            'max_domains' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        // Atualizar slug se o nome mudou
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Adicionar updated_by
        $data['updated_by'] = $request->user()->id;

        $domainGroup->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Domain group updated successfully.',
            'data' => $domainGroup->fresh()->load(['domains', 'creator', 'updater']),
        ]);
    }

    /**
     * Delete domain group
     */
    public function destroy($id)
    {
        $domainGroup = DomainGroup::find($id);

        if (!$domainGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Domain group not found.',
            ], 404);
        }

        // Verificar se tem domínios associados
        if ($domainGroup->domains()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete domain group with associated domains. Please remove or reassign the domains first.',
                'domains_count' => $domainGroup->domains()->count(),
            ], 400);
        }

        $domainGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Domain group deleted successfully.',
        ]);
    }

    /**
     * Get domains of a specific group
     */
    public function domains($id)
    {
        $domainGroup = DomainGroup::with('domains')->find($id);

        if (!$domainGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Domain group not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'group_name' => $domainGroup->name,
                'domains' => $domainGroup->domains,
                'total' => $domainGroup->domains->count(),
                'max_domains' => $domainGroup->max_domains,
                'available' => $domainGroup->getAvailableDomainsCount(),
            ],
        ]);
    }
}
