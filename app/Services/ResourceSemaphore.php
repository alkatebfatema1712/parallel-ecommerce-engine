<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ResourceSemaphore
{
    /**
     * Semaphore بسيط لإدارة الموارد: يسمح بعدد عمليات ثقيلة محدود في نفس اللحظة.
     */
    public function attempt(string $name, int $maxSlots, callable $callback)
    {
        $slot = null;
        for ($i = 1; $i <= $maxSlots; $i++) {
            $lock = Cache::lock("semaphore:{$name}:slot:{$i}", 20);
            if ($lock->get()) {
                $slot = $lock;
                break;
            }
        }

        if (! $slot) {
            abort(response()->json([
                'message' => 'System capacity is full now. Try again after a few seconds.',
                'concept' => 'Resource Management / Capacity Control',
            ], 429));
        }

        try {
            return $callback();
        } finally {
            $slot->release();
        }
    }
}
