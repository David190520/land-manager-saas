<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->tenant_id) {
            abort(403, 'No tenant associated with this user.');
        }

        if ($request->user()->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Esta cuenta ha sido desactivada. Contacte al administrador.']);
        }

        return $next($request);
    }
}
