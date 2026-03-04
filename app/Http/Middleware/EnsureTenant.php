<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->tenant_id) {
            abort(403, 'No tenant associated with this user.');
        }

        return $next($request);
    }
}
