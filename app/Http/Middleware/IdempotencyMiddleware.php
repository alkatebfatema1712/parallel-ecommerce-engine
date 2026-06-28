<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // لازم يجي من JMeter أو Postman
        $key = $request->header('Idempotency-Key');

        if (!$key) {
            return response()->json([
                'message' => 'Missing Idempotency-Key'
            ], 400);
        }

        $cacheKey = "idem_{$key}";

        // إذا نفس الطلب انبعت قبل → نوقفه
        if (Cache::has($cacheKey)) {
            return response()->json([
                'message' => 'Duplicate request blocked'
            ], 200);
        }

        // نخزن المفتاح لمدة 10 دقائق
        Cache::put($cacheKey, true, now()->addMinutes(10));

        return $next($request);
    }
}