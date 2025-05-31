<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\authController;
use App\Http\Controllers\Postingan;
use App\Http\Controllers\Komunitas;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;
use App\Http\Controllers\Pengguna;

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
    
    // Users route
    Route::get('/users', [Pengguna::class, 'index']);
    
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

// Password Reset Routes
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/verify-token', [PasswordResetController::class, 'verifyToken']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Test Resend Email with Error Handling
Route::post('/test-email', function (Request $request) {
    try {
        $email = $request->input('email');
        
        Log::info('Starting test email process', [
            'to' => $email,
            'resend_config' => [
                'api_key_exists' => !empty(config('resend.api_key')),
                'from_address' => config('mail.from.address'),
                'mailer' => config('mail.default')
            ]
        ]);
        
        $result = Resend::emails()->send([
            'from' => config('mail.from.address'),
            'to' => $email,
            'subject' => 'Test Email',
            'html' => '<strong>This is a test email from your application.</strong>'
        ]);
        
        Log::info('Test email sent successfully', ['result' => $result]);
        
        return response()->json([
            'message' => 'Test email sent successfully',
            'result' => $result,
            'config' => [
                'from_address' => config('mail.from.address'),
                'mailer' => config('mail.default')
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Test email failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'config' => [
                'api_key_exists' => !empty(config('resend.api_key')),
                'from_address' => config('mail.from.address'),
                'mailer' => config('mail.default')
            ]
        ]);
        
        return response()->json([
            'message' => 'Failed to send test email',
            'error' => $e->getMessage(),
            'config' => [
                'from_address' => config('mail.from.address'),
                'mailer' => config('mail.default')
            ]
        ], 500);
    }
});

Route::post('/admin/login', [authController::class, 'adminApiLogin']);