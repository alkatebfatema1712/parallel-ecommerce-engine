<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LoadBalancerController;
// use App\Http\Controllers\UserProductController;
/*yes
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register',[UserController::class,'register']);
Route::post('/login',[UserController::class,'login']);
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');


Route::apiResource('/categories', CategoryController::class);

Route::post('/category/store',[CategoryController::class,'store'])->name('restaurants.store');
Route::get('/category/index',[CategoryController::class,'index'])->name('restaurants.index');
Route::get('/category/show/{categoryId}',[CategoryController::class,'show'])->name('restaurants.show');
Route::put ('/category/update/{categoryId}',[CategoryController::class,'update'])->name('restaurants.update');
Route::delete('/category/destroy/{categoryId}',[CategoryController::class,'destroy'])->name('restaurants.destroy');

/****************************************************************************************************/

Route::apiResource('/products', ProductController::class);

Route::post('/category/{categoryId}/products/store',[ProductController::class,'store'])->name('restaurants.products.store');
Route::get('/category/{categoryId}/products',[ProductController::class,'index'])->name('restaurants.products.index');
Route::get('/category/{categoryId}/products/{productId}',[ProductController::class,'show'])->name('restaurants.products.show');
Route::put('/category/{categoryId}/products/{productId}',[ProductController::class,'update'])->name('restaurants.products.update');
Route::delete('/category/{categoryId}/products/{productId}',[ProductController::class,'destroy'])->name('restaurants.products.destroy');

Route::get('/search',[ProductController::class,'search'])->name('search');


/****************************************************************************************************/

//اضافة منتج الى السلة
Route::post('/cart/add/{categoryId}/product/{productId}', [CartController::class, 'add_cart'])->name('add.cart');

//عرض معلومات السلة
Route::get('/cart/view', [CartController::class, 'cart_view'])->name('view.cart');

//حذف منتج من السلة
Route::delete('/cart/delete/{productId}', [CartController::class, 'delete_cart_item'])->name('delete.cart');


Route::put('/cart/update/{productId}', [CartController::class, 'update_cart_item'])->name('update.cart');


//افراغ السلة
Route::delete('/cart/empty', [CartController::class, 'empty_cart'])->name('empty.cart');

/****************************************************************************************************/

//الطلبات

// Route::post('/cart/transfer',[OrderController::class,'transfer_cart_contents'])->name('cart.transfer');

Route::get('/orders/index', [OrderController::class,'index'])->name('order.index');

Route::get('/orders/{orderId}/show', [OrderController::class,'show_order'])->name('order.show');

Route::delete('/orders/{orderId}/items/{productId}', [OrderController::class,'delete_order_item'])->name('order.delete');

Route::post('/orders/{orderId}/cancel', [OrderController::class,'cancel_order'])->name('order.cancel');

Route::post('/orders/{orderId}/category/{categoryId}/items/add', [OrderController::class,'add_order_item'])->name('order.add');

Route::put('/orders/{orderId}/items/{productId}/update', [OrderController::class,'update_order_item'])->name('order.update');

// Route::post('/orders/{orderId}/confirm', [OrderController::class,'confirm_order'])->name('order.confirm');
// ضفت هدول منشان مشروع البرمجة التفرعية 
Route::middleware(['auth:sanctum', 'throttle:checkout-capacity'])->group(function () {
    Route::post('/order/transfer', [OrderController::class, 'transfer_cart_contents']);
    Route::post('/order/confirm/{id}', [OrderController::class, 'confirm_order']);
});
//*********************************************************************************************************
//الراوتس الخاصة بالطلب الرابع تبع جرد المبيعات 
// روابط فحص المتطلب الرابع (جرد المبيعات على دفعات)

Route::get('/report-before', [ReportController::class, 'reportBefore']);
Route::get('/report-after', [ReportController::class, 'reportAfter']);

//  */
//
//للسيرفرات هذا الراوت 

Route::get('/simulate/load-balancing', [LoadBalancerController::class, 'simulateLeastConnections']);
/************************************************************************************ */

// Route::post('/products/favorite/add',[UserProductController::class,'add_to_favorite'])->name('product.favorite.add');

// Route::get('/products/favorite/get',[UserProductController::class,'get_favorites'])->name('product.favorite');

// Route::post('/products/favorite/remove',[UserProductController::class,'remove_favorite_product'])->name('product.favorite.remove');

// Route::post('/products/favorite/order',[UserProductController::class,'make_order_favorite'])->name('product.favorite.order');
