<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authController;
use App\Http\Controllers\Postingan;
use App\Http\Controllers\Komunitas;
use App\Http\Controllers\ChatController;

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//  return $request->user();
// });
Route::post('/register',[authController::class,'register']);
Route::post('/login',[authController::class,'login']);
Route::post('/store',[authController::class,'store']);
Route::post('/delete',[authController::class,'delete']);
Route::get('/tampilkan/{id}',[authController::class,'tampilkan']);
Route::get('/getpost/{id}',[Postingan::class,'show']);
Route::middleware('auth:sanctum')->group(function(){
    Route::get('/user',[authController::class,'user']);
    Route::post('/logout',[authController::class,'logout']);
    Route::post('/update',[authController::class,'update']);
    
    // Comment routes
    Route::post('/comments', [Postingan::class, 'addComment']);
    Route::get('/posts/{post_id}/comments', [Postingan::class, 'getComments']);
    Route::delete('/comments/{comment_id}', [Postingan::class, 'deleteComment']);

    // Chat routes
    Route::post('/chat/groups', [ChatController::class, 'createGroup']);
    Route::get('/chat/groups', [ChatController::class, 'getUserGroups']);
    Route::get('/chat/groups/{group}/messages', [ChatController::class, 'getMessages']);
    Route::post('/chat/groups/{group}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/chat/groups/{group}/users', [ChatController::class, 'addUserToGroup']);
    Route::delete('/chat/groups/{group}/users', [ChatController::class, 'removeUserFromGroup']);

    // Post routes
    Route::post('/post',[Postingan::class,'post']);
    Route::get('/user/community-posts', [Postingan::class, 'getUserCommunityPosts']);
});
