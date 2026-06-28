<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        // 'usertitle_id',
        // 'product_img',
        'price',
        'category_id',
        'stock',
        'version',
        'title',
        // 'description',
    ];
    public function categories(){
        return $this->belongsTo(Category::class);
    }
    public function userProductFa(){
        return $this->belongsToMany(User::class,'user_products');
    }
    public function orderItems(){
        return $this->hasMany(Order_Item::class);
    }
    public function cartItems(){
        return $this->hasMany(CartItem::class);
    }
}
