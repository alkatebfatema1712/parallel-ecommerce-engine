<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Cart_Item;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function add_cart(Request $request ,$categoryId,$productId){
        $request->validate([
           // 'product_id'  =>  'required|exists:products,id',
            'amount'   =>   'required|integer|min:1',

        ]);
        $amount = $request->amount;
        $category = Category::findOrFail($categoryId);
       // $product_id = $request->product_id;
        $product = $category->products()->findOrFail($productId);

        $user = Auth::guard('sanctum')->user();
        if($user){
            $cart = Cart::where('user_id','=',$user->id)->first();
            if(!$cart){
                $cart = Cart::create([
                    'user_id'  =>  $user->id,
                    'created_at'  =>now(),
                ]);
            }//التأكد اذا كان المنتج موجود اساسا
                $cartItem = Cart_Item ::where([
                    'cart_id' => $cart->id,
                    'product_id' => $productId
                    ])->first();

                    if($cartItem){
                        $cartItem->amount += $amount;
                        $cartItem->total_price = $cartItem->amount * $product->price;
                        $cartItem->save();

                        return response()->json([
                            'message'  =>  'Product quantity updated successfully',
                        ], 200);
                    }else{
                        $totalPrice = $product->price * $amount;

                         $cartItem = Cart_Item ::create([
                            'cart_id' => $cart->id,
                            'product_id'=>$productId,
                            'price'  => $product->price,
                            'amount' => $amount,
                            'total_price' => $totalPrice,
                        ]);
                        return response()->json([
                            'message'  =>  'Product added to Cart successfully',
                            'cart'     => $cart,
                            'cart_item'=> $cartItem,
                        ], 201);
                    }
                }
        //         else
        // {
        //     $ip = $request->ip();
        //     $cart = Cart::where('user_ip','=',$ip)->first();
        //     if(!$cart){
        //         $cart = Cart::create([
        //             'user_ip'  =>  $ip,
        //             'created_at'  =>now(),
        //         ]);
        //     }
        //         $cartItem = Cart_Item ::where([
        //             'cart_id' => $cart->id,
        //             'product_id' => $product_id
        //             ])->first();

        //             if($cartItem){
        //                 $cartItem->amount += $amount;
        //                 $cartItem->total_price = $cartItem->amount * $product->price;
        //                 $cartItem->save();
        //                 return response()->json([
        //                     'message'  =>  'Product quantity updated successfully',
        //                 ], 200);
        //             }else
        //             {
        //                 $total_price = $product->price * $amount;

        //                  $cartItem = Cart_Item ::create([
        //                     'cart_id' => $cart->id,
        //                     'product_id'=>$product_id,
        //                     'price'  => $product->price,
        //                     'amount' => $amount,
        //                     'total_price' => $total_price,
        //                 ]);
        //                 return response()->json([
        //                     'message'  =>  'Product added to Cart successfully',
        //                     'cart'     => $cart,
        //                     'cart_item'=> $cartItem,
        //                 ], 201);
        //             }
        // }
    }

    public function cart_view(Request $request){
        $data=[];
        $user = Auth::guard('sanctum')->user();
        if($user){
            //استرجاع للسلة الخاصة بالمستخدم مع العناصر المرتبطة بكل منتج
            $cart = Cart::where('user_id','=',$user->id)
            ->with('cartItems.product')//تحميل العناصر المرتبطة بالسلة مع جميع البيانات الخاصة بكل عنصر الموجودة في جدول المنتجات
            ->first();
        }
        // else
        // {
        //     $ip =$request->ip();
        //     $cart = Cart::where('user_ip','=',$ip)
        //     ->with('cartItems.product:id,title')//تحميل العناصر المرتبطة بالسلة مع جميع البيانات الخاصة بكل عنصر الموجودة في جدول المنتجات
        //     ->first();
        // }
            if($cart){
                return response()->json([
                    'message'  => 'Cart retrieved successfully',
                    'cart'    => $cart,
                ], 200);

            } else {
                return response()->json([
                    'message'  =>  'No cart found',
                ], 404);
            }
    }

    public function delete_cart_item(Request $request ,$productId){
        $user = Auth::guard('sanctum')->user();
        if($user){
        $cart = Cart::where('user_id','=',$user->id)->first();
        }else
        {
            $ip =$request->ip();
            $cart = Cart::where('user_ip','=',$ip)->first();
        }
        if(!$cart){
            return response()->json([
                'mesage'  => 'Cart not found',
            ], 404);
        }

        $cartItem = $cart->cartItems()->where('product_id','=',$productId)->first();
        if(!$cartItem){
            return response()->json([
                'message'  =>  'Product not found',
            ], 404);
        }
        $cartItem->delete();
        return response()->json([
            'message'  =>  ' Product deleted successfully',
        ], 200);
}

public function update_cart_item( Request $request,$productId){
    $request->validate([
        'amount' =>  'required|integer|min:1',
    ]);
    $user = Auth::guard('sanctum')->user();
    if($user){
    $cart = Cart::where('user_id','=',$user->id)->first();
    }else
    {
        $ip =$request->ip();
        $cart = Cart::where('user_ip','=',$ip)->first();
    }
    if(!$cart){
            return response()->json([
                'message'  =>  'Cart not found'
                ], 404);
             }
             $cartItem = $cart->cartItems()->where('product_id',$productId)->first();
             if(!$cartItem){
                return response()->json([
                    'message'  =>   'Product not found',
                ], 404);
            }
                $cartItem->amount = $request->amount;
                $cartItem->total_price = $cartItem->amount * $cartItem->price;
                $cartItem->save();
                 return response()->json([
                    'message'  =>   'Product updated successfully',
                    'cart_item' => $cartItem
                 ], 200);

    }
    public function empty_cart(Request $request){
        $user = Auth::guard('sanctum')->user();
         if ($user){
            $cart = Cart::where('user_id',$user->id)->first();
         }else
         {
            $ip =$request->ip();
            $cart = Cart::where('user_ip','=',$ip)->first();
         }
            if ($cart){
                $cart->cartItems()->delete();

                return response()->json([
                    'message' => 'Cart cleared successfully',
                ], 200);
            } else
            {
                return response()->json([
                    'message'  =>  'Cart is already empty'
                ], 404);
            }

    }
}
