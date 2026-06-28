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
        Schema::create('cart__items', function (Blueprint $table) {
            $table->id();
            $table->decimal('price');
            $table->integer('amount');
            $table->decimal('total_price')->defualt(0);
            //$table->bigInteger('qty');
            $table->unsignedBigInteger('cart_id');
            $table->foreign('cart_id')
            ->references('id')
            ->on('carts')
            ->cascadeOnDelete();

            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
            ->references('id')
            ->on('products')
            ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart__items');
    }
};
