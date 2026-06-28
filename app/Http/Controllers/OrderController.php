<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use App\Models\Cart;
use App\Models\Cart_Item;
use App\Models\Order;
use App\Models\Order_Item;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Authp;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Jobs\ConfirmOrderJob;

class OrderController extends Controller
{
    //ok
    public function transfer_cart_contents(Request $request){
        $user = Auth::guard('sanctum')->user();

        if($user){

            $cart= Cart::where('user_id',$user->id)->first();
            if($cart){
                $cartItems = $cart->cartItems;

                if($cartItems->isEmpty()){
                    return response()->json([
                        'message'  => 'Cart is empty'
                    ], 404);
                }
                $totalPrice = $cartItems->sum('total_price');

                $order = Order::create([
                    'user_id'  => $user->id,
                    'total_price' => $totalPrice,
                    'status'    => 'pending',
                ]);

                foreach($cartItems as $cartItem){
                    Order_Item::create([
                        'order_id'  => $order->id,
                        'product_id' =>$cartItem->product_id,
                        'price'   => $cartItem->price,
                        'amount'  => $cartItem->amount,
                        'total_price'  => $cartItem->total_price,
                    ]);
                }
                $cartItems->each->delete();//بيحذف العناصر واحد واحد من السلة

                return response()->json([
                    'message'  =>  'Order created successfully',
                    'order_id' => $order->id
                ], 201);
            }
        }else
        {
            $ip = $request->ip();
            $cart= Cart::where('user_ip',$ip)->first();
            if($cart){
                $cartItems = $cart->cartItems;

                if($cartItems->isEmpty()){
                    return response()->json([
                        'message'  => 'Cart is empty'
                    ], 404);
                }
                $totalPrice = $cartItems->sum('total_price');

                $order = Order::create([
                    'user_ip'  => $ip,
                    'total_price' => $totalPrice,
                    'status'    => 'pending',
                ]);

                foreach($cartItems as $cartItem){
                    Order_Item::create([
                        'order_id'  => $order->id,
                        'product_id' =>$cartItem->product_id,
                        'price'   => $cartItem->price,
                        'amount'  => $cartItem->amount,
                        'total_price'  => $cartItem->total_price,
                    ]);
                }
                $cartItems->each->delete();//بيحذف العناصر واحد واحد من السلة

                return response()->json([
                    'message'  =>  'Order created successfully',
                ], 201);
            }
        }

    }
    //ok
    //عرض جميع الطلابات الخاصة بالمستخدم
    public function index(Request $request){
        $user = Auth::guard('sanctum')->user();
        if($user){
            $orders =Order::where('user_id',$user->id)->first();
        } else
        {
            $ip= $request->ip();
            $orders= Order::where('user_ip','=',$ip)->first();
        }
        if (!$orders) {
            return response()->json(['message' => 'Order not found',
        ], 404);
        }

        return response()->json([
            'message'   =>  'Your orders',
            'orders'    =>  $orders,
        ], 200);
    }
    //ok
    public function show_order(Request $request, $orderId){
        $user = Auth::guard('sanctum')->user();
        if($user){
            $order =Order::where('user_id',$user->id)->where('id', $orderId)->with('orderItems')->first();
        } else
        {
            $ip = $request->ip();
            $order = Order::where('user_ip','=',$ip)->where('id', $orderId)->with('orderItems')->first();
        }
        if (!$order) {
            return response()->json(['message' => 'Order not found',
        ], 404);
        }
        //$orderItems = $order->orderItems;
        return response()->json([
            'message'   =>  'Your order',
            'order'     =>  $order,
           //'order_items' =>$orderItems,
        ], 200);

    }
    //ok
    public function delete_order_item(Request $request , $orderId , $productId  ){
        $user = Auth::guard('sanctum')->user();
        if( $user){
            $order =Order::where('user_id',$user->id)->where('id',$orderId)->first();
        } else
        {
            $ip =$request->ip();
            $order =Order::where('user_ip',$ip)->where('id',$orderId)->first();
        }
        if (!$order) {
            return response()->json(['message' => 'Order not found',
        ], 404);
        }
        $orderItem = $order->orderItems()->where('product_id','=', $productId)->first();

        if(!$orderItem){
            return response()->json([
                'message'   =>  'Product not found in your order '
            ], 404);
        }
        $orderItem->delete();
        return response()->json([
            'message'      =>  'Product deleted successfully'
        ], 200);

}
    //ok
    public function cancel_order(Request $request, $orderId){
        $user = Auth::guard('sanctum')->user();
        if($user){
            $order = Order::where('user_id',$user->id)->where('id',$orderId)->first();
        }else
        {
            $ip = $request->ip();
            $order = Order::where('user_ip',$ip)->where('id',$orderId)->first();
        }
        if (!$order) {
            return response()->json(['message' => 'Order not found',
        ], 404);
        }
        $orderItems = $order->orderItems;
        if($order->status !=='canceled' ){
                foreach($orderItems as $orderItem){
                    $product = $orderItem->product();//استرجاع المنتج المرتبط بعنصر الطلب عن طريق العلاقة belongsTo
                    if($product){//اذا تم استرجاعه من قاعدة البيانات
                        $product->stock += $orderItem->amount;
                        $product->save();
                    }
                }
            $order->status = 'canceled';
            $order->save();
            return response()->json([
                'message'  =>  'Order canceled successfully',
            ], 200);
        } else
        {
            return response()->json([
                'message'   =>  'Order already canceled',
            ], 400);
        }
    }
    //ok
    public function add_order_item(Request $request , $orderId , $categoryId){
        $request->validate([
            'product_id'  =>  'required|integer|exists:products,id',
            'amount'         =>  'required|integer|min:1',
        ]);
        $amount = $request->amount;
        $category = Category::findOrFail($categoryId);
        $productId = $request->product_id;
        $product = $category->products()->findOrFail($productId);

        $user = Auth::guard('sanctum')->user();
        if( $user){
            $order = Order::where('user_id','=',$user->id)->where('id',$orderId)->first();

        } else
        {
            $ip = $request->ip();
            $order = Order::where('user_ip','=',$ip)->where('id',$orderId)->first();
        }
        if (!$order) {
            return response()->json(['message' => 'Order not found',
        ], 404);
        }
        $productExists = $order->orderItems()->where('product_id', $product->id)->exists();//اذا كان المنتج بالاصل موجود بالطلب
        if( $productExists){
            return response()->json([
                'message'  => ' Product already exists in your order'
            ], 400);
        }else{
            $totalPrice = $product->price * $amount;
            $orderItem = $order->orderItems()->create([
                'product_id'  => $request->product_id,
                'amount'         => $request->amount,
                'price'     =>  $product->price,
                'total_price'  => $totalPrice
            ]);
            return response()->json([
                'message'    => 'Product added successfully to your order ',
                'order_item'    => $orderItem,
            ], 201);
        }
    }
    //ok
    public function update_order_item(Request $request ,$orderId ,$productId ){
       $request->validate([
        'amount'  => 'required|integer|min:0',
    ]);
       $user = Auth::guard('sanctum')->user();
        if($user){
            $order =Order::where('user_id',$user->id)->where('id',$orderId)->first();
        } else
        {
            $ip =$request->ip();
            $order = Order::where('user_ip','=',$ip)->where('id',$orderId)->first();
        }
        if (!$order) {
            return response()->json(['message' => 'Order not found',
        ], 404);
        }
        $orderItem = $order->orderItems()->where('product_id', $productId)->first();// اذا لم يعثر على المنتج يرجع قيمة null
        if(!$orderItem){
            return response()->json([
                'message'    =>   'Product not found in your order',
            ], 404);
        }
        if($request->amount === 0){//اذا عملت الكمية صفر بيحذف المنتج
            $orderItem->delete();
            return response()->json([
                'message'   =>  'Product deleted from order',
            ],200);
        }
        $orderItem->amount = $request->amount;
        $orderItem->total_price = $orderItem->amount * $orderItem->price;
        $orderItem->save();
        return response()->json([
            'message'  =>  'Product quantity updated successfully',
            'order_item'  => $orderItem,
        ], 200);
    }
    // //
    // public function confirm_order(Request $request,$orderId){
    //     $user = Auth::guard('sanctum')->user();
    //     if($user){
    //         $order = Order::where([
    //             'user_id' => $user->id,
    //             'id'=> $orderId])->first();
    //     }else
    //     {
    //         $ip = $request->ip();
    //         $order = Order::where([
    //             'user_ip' => $ip,
    //             'id'=> $orderId])->first();
    //     }
    //     $orderItems = $order->orderItems();
    //     if($order->status !=='confirmed'){
    //         foreach($orderItems as $orderItem ){
    //             $product = $orderItem->product;//بدي اوصل للمنتجات لحتى عدل على المخزون
    //             if($product->stock < $orderItem->amount){
    //                 return response()->json([
    //                     'message'   =>   "Insufficient stock of product : {$product->name}",
    //                 ], 400);
    //             }
    //             $product->stock -= $orderItem->amount;
    //             $product->save();
    //         }
    //         $order->status = 'confirmed';
    //         $order->save();
    //         return response()->json([
    //             'message'   =>   'Order confirmed successfully'
    //         ], 200);

    //     } else
    //     {
    //         return response()->json([
    //             'message'  =>  'Order is already confirmed',
    //         ], 200);
    //     }

    // }
    // 



    //--------------------------------------------
    // هذا الكود منشان يساوي الفاتورة فورا بعد تأكيد كل طلب 
     // يعني بدون تطبيق مفهوم المتطلب الثالث 

//     public function confirm_order(Request $request, $orderId)
// {
//     $user = Auth::guard('sanctum')->user();
//     $order = $user 
//         ? Order::where(['user_id' => $user->id, 'id' => $orderId])->first()
//         : Order::where(['user_ip' => $request->ip(), 'id' => $orderId])->first();

//     if (!$order) {
//         return response()->json(['message' => 'Order not found'], 404);
//     }

//     if ($order->status === 'confirmed') {
//         return response()->json(['message' => 'Order already confirmed'], 400);
//     }

//     //  محاكاة التوليد المتزامن الثقيل داخل الـ Controller:
//     sleep(2); // تأخير ثانيتين (حساب ضرائب، إرسال إيميل، إلخ)
    
//     $totalAmount = $order->orderItems->sum(function($item) {
//         return $item->amount * $item->price;
//     });

//     \App\Models\Invoice::create([
//         'order_id'       => $order->id,
//         'invoice_number' => 'INV-SYNC-' . time() . '-' . $order->id,
//         'total_amount'   => $totalAmount,
//         'status'         => 'paid'
//     ]);

//     // تأكيد الطلب مباشرة في نفس اللحظة
//     $order->update(['status' => 'confirmed']);

//     return response()->json([
//         'message' => 'Order confirmed and Invoice generated synchronously!'
//     ], 200);
// }
    // ------------------------------------------


    // -------------------------------------------

    public function confirm_order(Request $request, $orderId)
{
    $user = Auth::guard('sanctum')->user();

    if ($user) {
        $order = Order::where([
            'user_id' => $user->id,
            'id' => $orderId
        ])->first();
    } else {
        $ip = $request->ip();

        $order = Order::where([
            'user_ip' => $ip,
            'id' => $orderId
        ])->first();
    }

    if (!$order) {
        return response()->json([
            'message' => 'Order not found'
        ], 404);
    }

    if ($order->status === 'confirmed') {
        return response()->json([
            'message' => 'Order already confirmed'
        ], 400);
    }

    //  هنا أهم سر
    ConfirmOrderJob::dispatch($order)->delay(now()->addSeconds(1));


    return response()->json([
        'message' => 'Order is being processed (queued)'
    ], 200);
}
// تم استخدام آليات التزامن (Concurrency Control)
// لمعالجة مشكلة الـ Race Condition عند تنفيذ عدة طلبات بالتوازي
}