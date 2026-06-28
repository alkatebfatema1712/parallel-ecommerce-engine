<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 20;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        $order = Order::with('orderItems')->findOrFail($this->order->id);

        // محاكاة عمل خلفي لا ينتظر المستخدم نتيجته.
        sleep(2);

        $total = $order->total_price ?: $order->orderItems->sum(fn ($item) => $item->amount * $item->price);

        Invoice::updateOrCreate(
            ['order_id' => $order->id],
            [
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . $order->id,
                'total_amount' => $total,
                'status' => 'paid',
            ]
        );
    }
}
