<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!$request->user() || !$request->user()->can($permission)) {
            return response()->json([
                'message' => 'No estás autorizado para realizar esta acción'
            ], 403);
        }

        return $next($request);
    }
}