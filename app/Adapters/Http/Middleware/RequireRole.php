<?php

namespace App\Adapters\Http\Middleware;

use App\Adapters\Http\Errors\ForbiddenRole;
use Closure;
use Illuminate\Http\Request;

// Usage in routes: ->middleware('role:admin') or ->middleware('role:admin,librarian') (CA-01.3)
final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! in_array($request->attributes->get('role'), $roles, true)) {
            throw new ForbiddenRole();
        }

        return $next($request);
    }
}
