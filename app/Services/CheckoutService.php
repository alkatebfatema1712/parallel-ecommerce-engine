<?php

namespace App\Services;

use App\Jobs\GenerateInvoiceJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CheckoutService
{
    /**
     * المتطلبات المغطاة هنا:
     * 1) منع Race Condition على المخزون.
     * 7) Distributed Lock خارج قفل قاعدة البيانات.
     * 8) ACID Transaction: دفع + مخزون + حالة الطلب.
     * 3) Queue لإرسال الفاتورة خارج مسار الطلب.
     */
    public function confirmWithPayment(int $orderId, User $user): Order
    {
        $lock = Cache::lock("checkout:order:{$orderId}", 15);

        if (! $lock->get()) {
            throw new RuntimeException('This order is already being processed by another request.');
        }

        try {
            return DB::transaction(function () use ($orderId, $user) {
                // قفل صف المستخدم لمنع سحب الرصيد مرتين في نفس اللحظة.
                $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

                // قفل الطلب وعناصره ضمن نفس المعاملة.
                $order = Order::with('orderItems')
                    ->where('user_id', $lockedUser->id)
                    ->whereKey($orderId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($order->status === 'confirmed') {
                    return $order;
                }

                $total = $order->orderItems->sum(fn ($item) => $item->amount * $item->price);
                if ($total <= 0) {
                    throw new RuntimeException('Order total is zero.');
                }

                // محاكاة الدفع المطلوبة في المشروع: تأخير بسيط بدل Stripe.
                usleep(700000);

                if ($lockedUser->wallet_balance < $total) {
                    $order->update(['status' => 'failed']);
                    throw new RuntimeException('Insufficient wallet balance.');
                }

                foreach ($order->orderItems as $item) {
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                    if ($product->stock < $item->amount) {
                        $order->update(['status' => 'failed']);
                        throw new RuntimeException("Insufficient stock for product {$product->id}");
                    }

                    // Optimistic version counter + safe decrement داخل transaction.
                    $updated = Product::where('id', $product->id)
                        ->where('version', $product->version)
                        ->where('stock', '>=', $item->amount)
                        ->update([
                            'stock' => DB::raw('stock - ' . (int) $item->amount),
                            'version' => DB::raw('version + 1'),
                            'updated_at' => now(),
                        ]);

                    if ($updated !== 1) {
                        throw new RuntimeException("Optimistic locking conflict for product {$product->id}");
                    }
                }

                $lockedUser->decrement('wallet_balance', $total);

                $order->update([
                    'total_price' => $total,
                    'status' => 'confirmed',
                    'paid_at' => now(),
                ]);

                GenerateInvoiceJob::dispatch($order->fresh('orderItems'));
                Log::info('Checkout confirmed safely', ['order_id' => $order->id, 'total' => $total]);

                return $order->fresh('orderItems');
            }, 3);
        } finally {
            optional($lock)->release();
        }
    }
}
