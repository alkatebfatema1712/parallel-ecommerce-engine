<?php

namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories= Category::select('name','description','id')->get();

        return response()->json(['categories'   =>   $categories
    ],200 );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'                 =>         'sometimes|string|max:255',
            'description'          =>         'sometimes|string',
            //'category_img'         =>         'nullable|image|mimes:jpeg,png,jpg,gif'
        ]);



        // if ($request->hasFile('category_img')) {
        //      $categoryImgPath = $request->file('category_img')->store('categoryImages', 'public');
        // }else{
        //     $categoryImgPath = null;
        // }

        $category=Category::create([
            'name'                =>            $request->name,
            'description'         =>            $request->description,
            //category_img'        =>            $categoryImgPath
        ]);

        //$categoryImgUrl = $category->category_img ? Storage::url($category->category_img) : null;//ضافة رابط الوصول للصورة باستخدام Storage::url():

        return response()->json([
            'message'             =>            "category created Successful *-*",
            'category'            =>            $category,
            //'category_img_url'    =>            $categoryImgUrl
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show( $categoryId)
    {
        $category= Category::findOrFail($categoryId);
        if(!$category){
            return response()->json(['message'    =>    'Category not found'
        ],404);
        }

        return response()->json([
            'name'               =>             $category->name,
            // 'description'        =>             $category->description
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,  $categoryId)
    {
        $category = Category:: findOrFail($categoryId);
        $request->validate([
            'name'               =>            'sometimes|string|max:255',
            'description'        =>            'sometimes|string',
            'category_img'       =>            'sometimes|image|mimes:jpeg,png,jpg,gif'
        ]);


         if(!$category){
            return response()->json([
                'message'        =>            'Category not found.'
            ], 404);
         }

        if($request->has('name')){
            $category->name             =       $request->name;
            $category->description      =       $request->description;
        }

         if($request->hasFile('category_img')){
                if($category->category_img){
                    Storage::delete($category->category_img);
                }
                $category->category_img = $request->file('category_img')->store('categoryImges','public');
         }
         $category->save();
         return response()->json([
            'message'            =>          'category updated successfully.',
            'name'               =>          $category->name,
            'description'        =>          $category->description,
         ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $categoryId)
    {
        $category = Category::find($categoryId);
        if(!$category){
            return response()->json([
                'message'     =>      'Category not found.'
            ], 404);

        }
        $category->delete();
        return response()->json([
            'message'        =>       'Category deleted successfully .'
        ], 200);
    }
}
