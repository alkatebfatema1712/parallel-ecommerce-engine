<?php

namespace App\Http\Controllers;

use App\Jobs\DailySalesReportJob;
use App\Models\Order;
use App\Models\PerformanceLog;
use App\Models\Product;
use App\Services\CheckoutService;
use App\Services\ResourceSemaphore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ParallelProjectController extends Controller
{
    public function checkout(Request $request, int $orderId, CheckoutService $checkout, ResourceSemaphore $semaphore)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Login by Sanctum token is required for wallet checkout.'], 401);
        }

        return $semaphore->attempt('checkout', (int) env('CHECKOUT_MAX_PARALLEL', 8), function () use ($checkout, $orderId, $user) {
            $order = $checkout->confirmWithPayment($orderId, $user);
            return response()->json([
                'message' => 'Order confirmed safely: payment + stock + order status committed atomically.',
                'order' => $order,
            ]);
        });
    }

    public function productCached(int $id)
    {
        $product = Cache::remember("products:{$id}", now()->addMinutes(10), function () use ($id) {
            return Product::findOrFail($id);
        });

        return response()->json([
            'source' => Cache::has("products:{$id}") ? 'cache-or-warmed' : 'database',
            'product' => $product,
        ]);
    }

    public function popularProducts()
    {
        $products = Cache::remember('products:popular', now()->addMinutes(10), function () {
            return Product::query()->orderByDesc('stock')->limit(20)->get();
        });

        return response()->json([
            'concept' => 'Distributed Caching using Redis/file cache',
            'products' => $products,
        ]);
    }

    public function searchCached(Request $request)
    {
        $q = trim((string) $request->query('query', ''));
        $key = 'search:' . md5($q);

        $result = Cache::remember($key, now()->addMinutes(5), function () use ($q) {
            return Product::where('title', 'like', "%{$q}%")->limit(25)->get();
        });

        return response()->json([
            'query' => $q,
            'cache_key' => $key,
            'result' => $result,
        ]);
    }

    public function resourceHeavy(ResourceSemaphore $semaphore)
    {
        return $semaphore->attempt('heavy-operation', 4, function () {
            usleep(1200000);
            return response()->json([
                'message' => 'Heavy operation executed inside limited capacity semaphore.',
                'concept' => 'Resource Management & Capacity Control',
            ]);
        });
    }

    public function dailyBatchReport()
    {
        DailySalesReportJob::dispatch();
        return response()->json([
            'message' => 'Daily sales report job dispatched. It will process confirmed orders by chunks.',
            'concept' => 'Batch Processing / Chunks',
        ]);
    }

    public function metrics()
    {
        return response()->json([
            'requests_count' => PerformanceLog::count(),
            'avg_response_ms' => round((float) PerformanceLog::avg('duration_ms'), 2),
            'max_response_ms' => round((float) PerformanceLog::max('duration_ms'), 2),
            'avg_memory_mb' => round((float) PerformanceLog::avg('memory_mb'), 2),
            'slowest_paths' => PerformanceLog::query()
                ->select('path', DB::raw('count(*) as hits'), DB::raw('round(avg(duration_ms),2) as avg_ms'))
                ->groupBy('path')
                ->orderByDesc('avg_ms')
                ->limit(10)
                ->get(),
        ]);
    }

    public function lockDemo(int $productId)
    {
        $lock = Cache::lock("manual-product-lock:{$productId}", 10);
        if (! $lock->get()) {
            return response()->json(['message' => 'Product is locked by another process.'], 423);
        }

        try {
            usleep(900000);
            return response()->json([
                'message' => 'Distributed lock acquired and released successfully.',
                'product_id' => $productId,
            ]);
        } finally {
            $lock->release();
        }
    }
}
