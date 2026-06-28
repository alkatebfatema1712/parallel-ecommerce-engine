<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Order;
use App\Models\Order_Item; // 
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrderTestSeeder extends Seeder
{
    public function run(): void

   {
    
  
 
    // من شان خلي 100 مستخدم يحجزوا نفس الطلب   $ product  first  عون استخدمت ال 
    // for ($i = 1; $i <= 100; $i++) {
    //  $product = Product::first();
    //     $user = User::create([
    //         'first_name' => "Test User $i",
    //         'email' => "user$i@test.com",
    //         'password' => bcrypt('password123'),
    //     ]);

    //     $order = Order::create([
    //         'user_id' => $user->id,
    //         'status' => 'pending',
    //     ]);

    //     Order_Item::create([
    //         'order_id' => $order->id,
    //         'product_id' => $product->id,
    //         'price' => $product->price,
    //         'amount' => 1,
    //         'total_price' => $product->price,
    //     ]);

        
    // }}

    //هون استخدمن 
    // products all
    // منشان خلي المستخدمين يحجزوا
    //  الطلبات بشكل عشوائي مو شرط
    // أول منتج

// جلب المنتجات
// // جلب المنتجات
$products = Product::all();

if ($products->isEmpty()) {
    $this->command->error("Please seed products first!");
    return;
}

$this->command->info("Starting to seed 100 users and orders...");
DB::transaction(function () use ($products) {
    $hashedPassword = Hash::make('password123');

    for ($i = 1; $i <= 500; $i++) {
        
        // استخدام firstOrCreate يضمن: إذا كان الحساب موجوداً يجلبه، وإذا لم يكن موجوداً ينشئه
        // هذا يمنع خطأ الـ Duplicate entry تماماً مهما تداخلت السييدرز
        $user = User::firstOrCreate(
            ['email' => "user{$i}@test.com"], // حقل التحقق الفريد
            [
                'first_name' => "Test User $i", 
                'password'   => $hashedPassword,
            ]
        );

        // 2. إنشاء الطلب 
        $order = Order::create([
            'user_id' => $user->id,
            'status'  => 'pending', 
        ]);

        // اختيار منتج عشوائي لتحديد سعره لعناصر الطلب
        $randomProduct = $products->random();

        // 3. إنشاء عناصر الطلب 
        Order_Item::create([
            'order_id'    => $order->id,
            'product_id'  => $randomProduct->id,
            'price'       => $randomProduct->price,       
            'amount'      => 1,                           
            'total_price' => $randomProduct->price,       
        ]);
    }
});

$this->command->info("Successfully seeded 100 users, orders, and order items!");
}
}