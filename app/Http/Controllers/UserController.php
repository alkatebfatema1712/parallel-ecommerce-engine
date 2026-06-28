<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Order;
use App\Models\Order_Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function login(Request $request){
        $request->validate([
        'email'=>'required|email',
        'password'=>'required|string',
       // 'device_name' => 'required',
        ]);
        $user= User::where('email',$request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $token = $user->createToken('auth_Token')->plainTextToken;
        $order = Order::where('user_id', $user->id)->first();
         return response()->json([
            'message'  => 'Login successful',
            'order_id' => $order?->id,
            'token'    => $token], 201);
            return response([
                'isSuccess' => true,
                'token'     =>$user->createToken($request->device_name)->plainTextToken
            ]);

    }
    public function register(Request $request){
        $request->validate([
            'first_name'    =>    'required|string|min:3|max:50',
            // 'last_name'     =>    'required|string|min:3|max:50',
             'email'         =>    'required|email|unique:users,email',
             'password'      =>    'required|string|min:8|confirmed',
            // 'profile_img'   =>    'nullable|image|mimes:jpeg,png,jpg,gif',
            // 'adress'        =>    'nullable|string|max:255',
        ]);

        $profileImgPath = null;
        if ($request->hasFile('profile_img')) {
            $profileImgPath = $request->file('profile_img')->store('profile_img', 'public');
        }
        $user = User::create([
            'first_name'    =>     $request->first_name,
            // 'last_name'     =>     $request->last_name,
             'email'         =>     $request->email,
            'password'      =>     bcrypt($request->password),
            // 'profile_img'   =>     $profileImgPath,
            // 'adress'        =>     $request->adress,
        ]);
        $profileImgUrl = $user->profile_img ? Storage::url($user->profile_img) : null;//ضافة رابط الوصول للصورة باستخدام Storage::url():

        return response()->json([
            'message'=>"User Registered Successful *-*",
            'User'=>$user,
            'profile_img_url' => $profileImgUrl
         ], 201);
        //  return response([
        //     'isSuccess'   =>   true,
        //     'message'     =>   'User Created !!'
        //  ]);
    }
    public function logout(Request $request){
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        // $request->user()->currentAccessToken()->delete();
        return response()->json(
            [
                'message'=>'Logout Successful'
            ],201);
       }
}







