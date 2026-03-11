<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only checking POST/PUT/PATCH/DELETE
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');

        if (!$key) {
            return $next($request); // Or return error if mandatory
        }

        // Check if key exists
        $cached = DB::table('idempotency_keys')
            ->where('key', $key)
            ->where('user_id', auth()->id()) // Scope to user
            ->first();

        if ($cached) {
            if ($cached->expires_at < now()) {
                // Expired, allow retry? Or fail? Usually fail or treat as new.
                // Here we assume if it exists it's done.
            }

            // Return cached response
            return response($cached->response_body, $cached->response_code)
                ->header('Idempotency-Replay', 'true');
        }

        // Process Request
        $response = $next($request);

        // Store Response (only if successful 2xx)
        if ($response->isSuccessful()) {
            DB::table('idempotency_keys')->insert([
                'key' => $key,
                'user_id' => auth()->id(),
                'path' => $request->path(),
                'method' => $request->method(),
                'response_code' => $response->getStatusCode(),
                'response_body' => $response->getContent(),
                'expires_at' => now()->addDay(), // 24 hours expiry
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $response;
    }
}
