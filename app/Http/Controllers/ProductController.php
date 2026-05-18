<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request , $categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $products  = $category->products()->select('title','','stock','id')->get();//get the name and image to product from the relationship between the category and product
        return response()->json([
            'category'      =>       $category->name,
            'product'       =>       $products,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request,$categoryId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            // 'price' => 'required|regex:/^\$?\d+(\.\d{1,2})?$/',
            'stock' => 'required|numeric',
            '' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

         $category = Category::findOrFail($categoryId);
         if ($request->hasFile('product_img')) {
            $product_img = $request->file('product_img');
            $productImagePath = $product_img->store('productImages', 'public');
        } else {
            $productImagePath = null;
        }
        $product = $category->products()->create([
            'product_img' => $productImagePath,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,

        ]);
        return response()->json([
            'message'         =>         'Product created successfuly.',
            'product'         =>          $product,
        ], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $categoryId, $productId)
    {
        $category = Category::findOrFail($categoryId);
        $product = $category->products()->findOrFail($productId);
        return response()->json([
            'store'                =>        $category->name,
            'product'              =>
             [
            //         'product_img'      =>        $product->product_img,
                    'title'            =>        $product->title,
                    // 'description'      =>        $product->description,
                    'price'            =>        $product->price,
                ]
         ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $categoryId, $productId)
    {
        $category = Category::findOrFail($categoryId);
        $product = $category->products()->findOrFail($productId);
        $request->validate([
            'title'            =>        'sometimes|string|max:255',
            'description'      =>        'sometimes|string',
            'price'            =>        'sometimes|numeric',
            'stock'              =>        'sometimes|numeric',
            'product_img'      =>        'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
             ]);

             if ($request->has('title')) {
                $product->title = $request->title;
           }

          if ($request->has('description')) {
                $product->description = $request->description;
           }

          if ($request->has('price')) {
               $product->price = $request->price;
           }

          if ($request->has('stock')) {
               $product->stock = $request->stock;
           }
           if($request->hasFile('product_img')){
            if($product->product_img){
                Storage::delete($product->product_img);
            }
            $product->product_img = $request->file('product_img')->store('productImges','public');
     }
        //    if ($request->hasFile('product_img')) {
        //     $product_img                 =        $request->file('product_img');
        //     $productImgPath              =        $product_img->store('productImages,public');
        //     $product->product_img        =        $productImgPath;
        // }
        $product->save();
        return response()->json([
            'message'     =>       'Product updated successfully',
            'product'     =>       $product],
             200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($categoryId,$productId)
    {
        $category = Category::findOrFail($categoryId);
        $product  = $category->products()->findOrFail($productId);

        $product->delete();
        return response()->json([
           'message' => 'Product deleted successfully'],200);
    }
    public function search(Request $request){
        $query = $request->query('query');
        $products = Product::where('title','like',"%{$query}%")
        ->orWhere('description','like',"%{$query}%")
        ->select('title','product_img')
        ->get();

        $categories = Category::where('name','like',"%{$query}%")
        ->select('name')
        ->get();

        if(!$products->isEmpty()  || !$categories->isEmpty()){
            return response()->json([
                'products'       =>       $products,
                'categories'     =>       $categories
            ]);
        } else{
            return response()->json([
                'message'       =>       'not found'
            ], 404);
        }
    }
}
