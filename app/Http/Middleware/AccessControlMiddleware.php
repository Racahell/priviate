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

        $method = strtoupper((string) $request->method());
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $input = collect($request->except([
                '_token',
                '_method',
                'password',
                'password_confirmation',
                'otp_code',
                'g-recaptcha-response',
            ]))->map(function ($value) {
                if (is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                if (is_object($value)) {
                    return '[object]';
                }
                $text = (string) $value;
                return mb_strlen($text) > 500 ? mb_substr($text, 0, 500) . '...' : $text;
            })->all();

            $this->auditService->log('USER_INPUT', null, [], [
                'route' => $request->route()?->getName(),
                'method' => $method,
                'input' => $input,
            ], [
                'location_status' => $request->input('location_status'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'device_fingerprint' => $request->header('X-Device-Fingerprint'),
                'browser' => $request->header('X-Browser'),
                'os' => $request->header('X-OS'),
            ]);
        }

        return $next($request);
    }
}
