<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Defense-in-depth headers, safe for both HTTP and HTTPS. They add no
        // redirect and never force a plain-HTTP install onto HTTPS.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('Referrer-Policy', 'same-origin', false);

        // HSTS is only meaningful — and only safe — when the request already
        // arrived over HTTPS, so a plain-HTTP/LAN deployment never receives it.
        // Behind a TLS-terminating reverse proxy, secure() reflects
        // X-Forwarded-Proto via the trusted-proxy config in bootstrap/app.php.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        return $response;
    }
}
