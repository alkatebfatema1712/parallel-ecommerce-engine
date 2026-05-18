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
    Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        // ربط الفاتورة بالطلب
        $table->foreignId('order_id')->constrained()->onDelete('cascade'); 
        $table->string('invoice_number')->unique();
        $table->decimal('total_amount', 10, 2);
        $table->string('status')->default('paid'); // paid, pending, cancelled
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
