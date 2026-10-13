<?php

namespace App\Adapters\Http\Middleware;

use App\Adapters\Http\Errors\Unauthenticated;
use App\Adapters\Security\JwtVerifier;
use Closure;
use Illuminate\Http\Request;
use Throwable;

// api-contract §4: Bearer token on every route except login and health.
// Any failure (missing, forged, expired) answers 401 UNAUTHENTICATED.
final class AuthenticateToken
{
    public function __construct(private readonly JwtVerifier $verifier) {}

    public function handle(Request $request, Closure $next)
    {
        $header = (string) $request->bearerToken();

        if ($header === '') {
            throw new Unauthenticated();
        }

        try {
            $claims = $this->verifier->decode($header);
        } catch (Throwable) {
            throw new Unauthenticated();
        }

        $request->attributes->set('user_id', $claims->sub);
        $request->attributes->set('role', $claims->role);

        return $next($request);
    }
}
