<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the site private while it is not launched: only the addresses in
 * `app.allowed_ips` get through. An empty list turns the check off.
 *
 * The webhook stays open because Storyblok calls it from its own servers and
 * it is authenticated by its signature. The health check stays open for the host.
 *
 * Behind a proxy or load balancer, configure trusted proxies, or every
 * request appears to come from the proxy's address.
 */
class RestrictToAllowedIps
{
    private const ALWAYS_OPEN = ['webhooks/storyblok', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $allowed */
        $allowed = config('app.allowed_ips', []);

        if ($allowed === [] || $request->is(...self::ALWAYS_OPEN)) {
            return $next($request);
        }

        $ip = $request->ip();

        if ($ip !== null && IpUtils::checkIp($ip, $allowed)) {
            return $next($request);
        }

        // A plain response, not the designed error page: a stranger should not cost a Storyblok request.
        return response('Forbidden', Response::HTTP_FORBIDDEN, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
