<?php

namespace App\Http\Middleware;

use App\Models\PerformanceLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitor
{
    public function handle(Request $request, Closure $next)
{
    $start = microtime(true);

    $response = $next($request);

    try {
        PerformanceLog::create([
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'user_key' => $request->ip(),
        ]);
    } catch (\Throwable $e) {
        // مهم جداً: لا تكسر الطلب أبداً
    }

    return $response;
}
}
