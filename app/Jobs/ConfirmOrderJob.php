<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Jobs\GenerateInvoiceJob;
class ConfirmOrderJob implements ShouldQueue
{//قبل هون ما عالجنا اذا انقطع الاتصال فجأة 
//     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//     // تحديد عدد محاولات إعادة التشغيل في حال الفشل مؤقتاً لمنع اللوب اللانهائي
//     public $tries = 3;

//     public function __construct(public Order $order) {}

//     /**
//      * الحارس الذكي: يمنع معالجة أكثر من مَهمة لنفس الطلب في نفس اللحظة
//      */
//     public function middleware()
//     {
//         return [
//             // تمنع التداخل بناءً على معرّف الطلب، وتؤجل المهام المتزامنة لـ 10 ثوانٍ بدلاً من قفل السيرفر
//             (new WithoutOverlapping($this->order->id))->dontRelease()->expireAfter(10)
//         ];
//     }
    
//     public function handle()
//    {
//     DB::transaction(function () {
//         // 1. جلب الطلب من قاعدة البيانات مع قفله (Row Lock) لمنع التداخل
//         $order = Order::with('orderItems')->lockForUpdate()->find($this->order->id);

//         // إذا لم يتم العثور على الطلب أصلاً
//         if (!$order) {
//             \Log::warning("Job Skipped: Order ID {$this->order->id} not found in database.");
//             return;
//         }

//         // 2. التحقق مما إذا كان الطلب قد حُسم مصيره مسبقاً في محاولة أو خيط (Thread) آخر
//         if ($order->status === 'failed') {
//             \Log::info("Job Skipped for Order #{$order->id}: This order was already marked as FAILED due to insufficient stock.");
//             return;
//         }

//         if ($order->status === 'confirmed') {
//             \Log::info("Job Skipped for Order #{$order->id}: This order is already CONFIRMED.");
//             return;
//         }

//         // 3. المرور على جميع المنتجات داخل الطلب للتحقق من المخزون
//         foreach ($order->orderItems as $item) {
//             // قفل سجل المنتج بشكل آمن لمنع الـ Race Condition
//             $product = Product::where('id', $item->product_id)
//                 ->lockForUpdate()
//                 ->first();

//             // التحقق من وجود المنتج ومن كفاية المخزون
//             if (!$product || $product->stock < $item->amount) {
//                 // تحديث حالة الطلب إلى فشل في قاعدة البيانات لحسم مصيره
//                 $order->update(['status' => 'failed']);
                
//                 // تسجيل تفاصيل الفشل في الـ Log لرؤيتها بوضوح
//                 \Log::warning("Order #{$order->id} FAILED: Insufficient stock for Product ID: {$item->product_id}. Available: " . ($product ? $product->stock : 0) . ", Requested: {$item->amount}");
                
//                 return; // الخروج بسلام لمنع انهيار السيرفر بخطأ 500 في JMeter
//             }

//             // 4. خصم الكمية بأمان من المخزون بعد التأكد من توفرها
//             $product->decrement('stock', $item->amount);
//         }

//         // 5. إذا مرت جميع المنتجات بنجاح، يتم تأكيد الطلب
//         $order->update(['status' => 'confirmed']);
//         \Log::info("Order #{$order->id} CONFIRMED successfully. Stock updated.");
//        });
//    }

// هون اذا انقطع الاتصال فجأة



// ... (الـ imports المعتادة في الأعلى)


    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    //  التعديل الأول (هنا تماماً فوق الـ Constructor):
    // نحدد المحاولات بـ 1 فقط. إذا سقطت القاعدة، يفشل الجوب فوراً ولا يدخل في لوووب إعادات مكرر
    public $tries = 1;

    // نحدد مهلة تنفيذ الجوب بـ 5 ثوانٍ، إن طوّلت يُقتل الـ Worker تلقائياً
    public $timeout = 5;

    public function __construct(public Order $order) {}

    public function middleware()
    {
        return [
            (new WithoutOverlapping($this->order->id))->dontRelease()->expireAfter(10)
        ];
    }
    
    public function handle()
    {
        //  التعديل الثاني (تغليف الكود بالكامل داخل try-catch):
        try {
            DB::transaction(function () {
                $order = Order::with('orderItems')->lockForUpdate()->find($this->order->id);

                if (!$order || $order->status === 'failed' || $order->status === 'confirmed') {
                    return;
                }

                foreach ($order->orderItems as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                    if (!$product || $product->stock < $item->amount) {
                        $order->update(['status' => 'failed']);
                        return;
                    }
                    $product->decrement('stock', $item->amount);
                }

                $order->update(['status' => 'confirmed']);
                \Log::info("Order #{$order->id} CONFIRMED successfully.");
                //  المطلوب للمتطلب الثالث:
                // إرسال مهمة توليد الفاتورة والإشعار إلى طابور الخلفية بشكل غير متزامن
                 GenerateInvoiceJob::dispatch($order);
            });

        } catch (\Exception $e) {
            //  التعديل الثالث (داخل الـ catch هنا يُحسم الأمر):
            // تسجيل الخطأ في الـ Log لمرة واحدة لتوثيقه
            \Log::error("CRITICAL: قاعدة البيانات مقطوعة أثناء معالجة الطلب #{$this->order->id} -> " . $e->getMessage());
            
            // استدعاء fail يدوياً لإجبار لارافيل على نقل الجوب فوراً لجدول الـ failed_jobs وإيقاف اللوب
            $this->fail($e); 
        }
    }
}