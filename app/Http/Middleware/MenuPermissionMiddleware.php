<?php

namespace App\Http\Middleware;

use App\Models\MenuItem;
use App\Models\MenuPermission;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class MenuPermissionMiddleware
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !Schema::hasTable('menu_items') || !Schema::hasTable('menu_permissions')) {
            return $next($request);
        }
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return $next($request);
        }

        // Never block core entry/profile routes for authenticated users.
        if (in_array($routeName, ['dashboard', 'profile.edit', 'profile.update', 'location.consent'], true)) {
            return $next($request);
        }

        // Fallback: keep core parent portal accessible even if legacy menu permissions
        // were saved with can_view=false.
        if (
            $user->hasRole('orang_tua')
            && in_array($routeName, ['dashboard', 'parent.dashboard', 'parent.children', 'parent.children.link', 'parent.schedule', 'parent.reschedule', 'parent.disputes'], true)
        ) {
            return $next($request);
        }

        $menu = MenuItem::where('route_name', $routeName)->where('is_active', true)->first();
        if (!$menu) {
            return $next($request);
        }

        $role = $user->getRoleNames()->first();
        $permission = MenuPermission::where('menu_item_id', $menu->id)
            ->where('role_name', $role)
            ->first();

        if ($permission && !$permission->can_view) {
            $this->auditService->log('ACCESS_DENIED', $user, [], [
                'reason' => 'menu_permission_denied',
                'route' => $routeName,
            ]);
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
