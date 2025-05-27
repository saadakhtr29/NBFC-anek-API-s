<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userPermissions = $request->user()->permissions->pluck('slug')->toArray();
        $rolePermissions = $request->user()->roles->flatMap->permissions->pluck('slug')->toArray();
        $allPermissions = array_unique(array_merge($userPermissions, $rolePermissions));

        foreach ($permissions as $permission) {
            if (in_array($permission, $allPermissions)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
} 