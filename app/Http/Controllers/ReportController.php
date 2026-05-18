<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Jobs\DailySalesReportJob;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     *  الفحص للحالة التقليدية (قبل - تحميل كل البيانات دفعة واحدة في الذاكرة)
     */
    public function reportBefore()
    {
        $startMemory = memory_get_usage();
        
        // جلب كلي لجميع طلبات اليوم دفعة واحدة في الذاكرة
        $orders = Order::where('status', 'confirmed')->whereDate('created_at', Carbon::today())->get();
        
        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total_price');
        
        $endMemory = memory_get_usage();
        $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 4) . ' MB';
        
        return response()->json([
            'mode' => 'Synchronous (Before) - Load All at Once',
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'memory_allocated_for_objects' => $memoryUsed
        ]);
    }

    /**
     *  الفحص للحالة المتقدمة (بعد - معالجة البيانات على دفعات Chunks في الخلفية)
     */
    public function reportAfter()
    {
        // إرسال جوب المعالجة على دفعات (Batch Processing) إلى الخلفية فوراً
        DailySalesReportJob::dispatch();
        
        return response()->json([
            'mode' => 'Asynchronous Batch Processing (After) - Chunking Enabled',
            'message' => 'The background job is now processing daily sales in chunks efficiently!'
        ]);
    }
}