<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class authController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'email'=>'required',
            'password'=>'required'
        ]);
        User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password)
        ]);
        return response()->json(['message'=>'berhasil registrasi'],201);
      }
      public function login(Request $request)
      {
        $request->validate([ 
            'email'=>'required',
            'password'=>'required'
        ]);
        $user=User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password,$user->password)){
            return response()->json(['message'=>'UNAUTHORIZED'],401);
      }
      $token=$user->createToken('Auth-token')->plainTextToken;
      return response()->json([
        'user'=>$user,
        'token'=>$token
      ],200);
  }
  public function user()
  {
    return response()->json('SELAMAT DATANG DI F!ND, '.Auth()->User()-> name);
  }
  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()->delete();
    return response()->json('ANDA TELAH BERHASIL LOGOUT! SAMPAI JUMPA KEMBALI :)');
  }
}

