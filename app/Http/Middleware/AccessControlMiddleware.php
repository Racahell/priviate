<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessControlMiddleware
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            $this->auditService->log('ACCESS_DENIED', $user, [], [
                'reason' => 'user_inactive',
                'route' => $request->route()?->getName(),
            ]);

            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
