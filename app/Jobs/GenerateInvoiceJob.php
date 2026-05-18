<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // قيد المحاولات والوقت لحماية السيرفر كما تعلمنا سابقاً
    public $tries = 3;
    public $timeout = 10;

    // نمرر الطلب الذي نريد توليد الفاتورة له
    public function __construct(public Order $order) {}

    public function handle()
    {
        // محاكاة تأخير بسيط (مثلاً ثانيتين) كأن النظام يقوم بحساب الضرائب وتوليد ملف الفاتورة
        sleep(2); 

        // حساب القيمة الإجمالية للطلب من خلال عناصر الطلب (Order Items)
        $totalAmount = $this->order->orderItems->sum(function($item) {
            return $item->amount * $item->price; // تأكدي من مسميات الأعمدة عندك في جدول الـ items
        });

        // إنشاء الفاتورة في قاعدة البيانات
        $invoice = Invoice::create([
            'order_id'       => $this->order->id,
            'invoice_number' => 'INV-' . time() . '-' . $this->order->id,
            'total_amount'   => $totalAmount,
            'status'         => 'paid'
        ]);

        // محاكاة إرسال الإشعار للمستخدم (كتابة في الـ Log)
        \Log::info("Asynchronous Queue: Invoice #{$invoice->invoice_number} generated and Email Notification sent for Order #{$this->order->id}");
    }
}