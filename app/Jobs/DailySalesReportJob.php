<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\DailyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DailySalesReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    public function __construct() {}

    public function handle()
    {
        $today = Carbon::today();
        
        $totalOrdersCount = 0;
        $totalRevenueSum = 0;

        //  السر الهندسي (المتطلب الرابع): جلب البيانات على دفعات Chunks لزيادة سرعة الأداء وحماية الذاكرة
        // نقرأ كل 100 طلب معاً كدفعة (Batch)
        Order::where('status', 'confirmed')
            ->whereDate('created_at', $today)
            ->chunk(100, function ($ordersBatch) use (&$totalOrdersCount, &$totalRevenueSum) {
                
                foreach ($ordersBatch as $order) {
                    $totalOrdersCount++;
                    $totalRevenueSum += $order->total_price;
                }

                // تسجيل log لمراقبة معالجة الدفعات في الـ Terminal
                \Log::info("Batch Processing: Processed a chunk of " . $ordersBatch->count() . " orders.");
            });

        //  حساب أعلى حجم ذاكرة مستهلكة أثناء معالجة الدفعات 
        $peakMemory = round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB';

        // حفظ التقرير النهائي في قاعدة البيانات
        DailyReport::updateOrCreate(
            ['report_date' => $today->toDateString()],
            [
                'total_orders'  => $totalOrdersCount,
                'total_revenue' => $totalRevenueSum,
                'memory_used'   => $peakMemory
            ]
        );

        \Log::info("Batch Processing SUCCESS: Daily Report generated for {$today->toDateString()}. Memory Used: {$peakMemory}");
    }
}