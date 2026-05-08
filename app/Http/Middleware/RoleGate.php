<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleGate
{
    /**
     * Usage: ->middleware('role:admin') or ->middleware('role:admin|staff')
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $allowedRoles = explode('|', $roles);
        if (! $user->hasAnyRole($allowedRoles)) {
            return response()->json([
                'message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้',
                'required_roles' => $allowedRoles,
            ], 403);
        }

        return $next($request);
    }
}
