<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verificar se está autenticado
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Verificar se é um Admin
        if (!$user instanceof \App\Models\Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can access this resource.',
            ], 403);
        }

        // Verificar se é Super Admin
        if (!$user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only Super Admins can perform this action.',
                'required_permission' => 'super_admin',
            ], 403);
        }

        return $next($request);
    }
}
