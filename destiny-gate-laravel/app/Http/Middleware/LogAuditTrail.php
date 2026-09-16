<?php

namespace App\Http\Middleware;

use App\Support\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every state-changing API request (POST/PUT/PATCH/DELETE) by a logged-in user —
 * this is the "who is doing what" half of the audit trail. Runs globally on the api
 * middleware group; by the time it inspects the request post-response, route-level
 * auth:sanctum has already resolved $request->user() if the request was authenticated.
 *
 * GET/HEAD requests are deliberately not logged (a single page load fires a dozen of
 * them — logging every read would drown the trail in noise without adding security
 * value). Login/logout are logged separately and more descriptively in
 * AuthApiController, so they're excluded here to avoid a confusing duplicate entry.
 */
class LogAuditTrail
{
    private const EXCLUDED_PATHS = ['api/login', 'api/logout'];
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), self::MUTATING_METHODS, true) && !in_array($request->path(), self::EXCLUDED_PATHS, true)) {
            $user = $request->user();
            if ($user) {
                AuditLogger::log(
                    $user->id,
                    $user->name,
                    $user->role,
                    'api_request',
                    $this->describe($request),
                    $request,
                    $response->getStatusCode()
                );
            }
        }

        return $response;
    }

    private function describe(Request $request): string
    {
        $verb = match ($request->method()) {
            'POST'          => 'Created/actioned',
            'PUT', 'PATCH'  => 'Updated',
            'DELETE'        => 'Deleted',
            default         => 'Modified',
        };

        $segments = array_values(array_filter(explode('/', $request->path())));
        if (($segments[0] ?? null) === 'api') {
            array_shift($segments);
        }

        return trim("{$verb} — " . implode(' / ', $segments));
    }
}
