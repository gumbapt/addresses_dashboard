<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Domain\Services\DomainPermissionService;
use App\Models\Admin;
use App\Models\Report;

class CheckDomainAccess
{
    public function __construct(
        private DomainPermissionService $domainPermissionService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof Admin) {
            return response()->json([
                'message' => 'Admin privileges required.',
            ], 401);
        }

        // Extrair domain_id da rota ou do report
        $domainId = $this->extractDomainId($request);

        // Se não há domain_id, permitir (será tratado no controller)
        if (!$domainId) {
            return $next($request);
        }

        // Verificar acesso ao domínio
        if (!$this->domainPermissionService->canAccessDomain($user, (int) $domainId)) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to access this domain.',
                'domain_id' => $domainId,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Extrai o domain_id da rota ou do report
     */
    private function extractDomainId(Request $request): ?int
    {
        // Tentar pegar diretamente da rota
        $domainId = $request->route('domainId');
        
        if ($domainId) {
            return (int) $domainId;
        }

        // Se a rota tem {id} de um report, buscar o domain_id do report
        $reportId = $request->route('id');
        
        if ($reportId) {
            $report = Report::find($reportId);
            return $report ? $report->domain_id : null;
        }

        return null;
    }
}

