<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
 
    Schema::create('daily_reports', function (Blueprint $table) {
        $table->id();
        $table->date('report_date')->unique(); // تاريخ اليوم الخاص بالجرد
        $table->integer('total_orders')->default(0); // إجمالي عدد الطلبات المعالجة
        $table->decimal('total_revenue', 12, 2)->default(0.00); // إجمالي الأرباح
        $table->string('memory_used')->nullable(); // سنخزن حجم الذاكرة هنا لإثباتها للدكتور!
        $table->timestamps();
    });
}
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
