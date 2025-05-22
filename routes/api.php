<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\authController;
use App\Http\Controllers\Postingan;
use App\Http\Controllers\Komunitas;
use App\Http\Controllers\ChatController;

// Broadcast authentication route (must be before the other routes)
Broadcast::routes(['middleware' => ['auth:sanctum']]);

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
    
    // Posts routes
    Route::get('/posts', [Postingan::class, 'index']);
    Route::get('/posts/recommended', [Postingan::class, 'getRecommendedPosts']);
    Route::post('/post',[Postingan::class,'post']);
    Route::get('/user/community-posts', [Postingan::class, 'getUserCommunityPosts']);
    Route::get('/posts/{post_id}', [Postingan::class, 'show']);
    
    // Like routes
    Route::post('/posts/{post_id}/toggle-like', [Postingan::class, 'toggleLike']);
    
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

    // Community routes
    Route::get('/communities', [Komunitas::class, 'index']);
    Route::get('/communities/{id}', [Komunitas::class, 'show']);
    Route::post('/communities', [Komunitas::class, 'createCommunity']);
    Route::put('/communities/{id}', [Komunitas::class, 'update']);
    Route::delete('/communities/{id}', [Komunitas::class, 'destroy']);
});

// Test route for chat groups (remove this in production)
Route::get('/test/chat-groups', [ChatController::class, 'testChatGroups']);