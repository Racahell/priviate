<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;

class WhitelabelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $tenant = null;

        if ($this->tableExists('tenants')) {
            $tenant = Tenant::where('domain', $host)->where('is_active', true)->first();
        }

        if (!$tenant) {
            // Fallback or 404. For dev, maybe localhost defaults to a dummy tenant
            if ($host === 'localhost' || $host === '127.0.0.1') {
                $tenant = new Tenant([
                    'name' => 'PrivTuition (Dev)',
                    'primary_color' => '#007bff',
                    'logo_url' => '/img/logo-big.png',
                    'footer_content' => '&copy; 2026 PrivTuition Dev Team',
                ]);
            }
        }

        if ($tenant) {
            // Share tenant with all views
            View::share('tenant', $tenant);
            
            // You can also config set here
            // config(['app.name' => $tenant->name]);
        }

        return $next($request);
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
