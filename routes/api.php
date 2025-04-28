<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authController;
use App\Http\Controllers\Komunitas;

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//  return $request->user();
// });
Route::post('/register',[authController::class,'register']);
Route::post('/login',[authController::class,'login']);
Route::post('/store',[authController::class,'store']);
Route::post('/delete',[authController::class,'delete']);
Route::post('/update',[authController::class,'update']);
Route::get('/tampilkan/{id}',[authController::class,'tampilkan']);
Route::middleware('auth:sanctum')->group(function(){
    Route::get('/user',[authController::class,'user']);
    Route::post('/logout',[authController::class,'logout']);
});
