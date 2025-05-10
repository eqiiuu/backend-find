<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\adminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [adminController::class, 'showRegister'])->name('register');
Route::post('/admin/register', [adminController::class, 'registerPost'])->name('admin.register.post');

Route::get('/login', [adminController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [adminController::class, 'loginPost'])->name('admin.login.post');

Route::middleware(['auth:admin'])->group(function () {
    Route::get('/dashboard', [adminController::class, 'showDashboard'])->name('admin.dashboard');
    Route::post('/admin/logout', [adminController::class, 'logout'])->name('admin.logout');

    // User Management Routes
    Route::get('/admin/users', [adminController::class, 'showUsers'])->name('admin.users.index');
    Route::get('/admin/users/create', [adminController::class, 'createUser'])->name('admin.users.create');
    Route::post('/admin/users', [adminController::class, 'storeUser'])->name('admin.users.store');
    Route::get('/admin/users/{id}/edit', [adminController::class, 'editUser'])->name('admin.users.edit');
    Route::put('/admin/users/{id}', [adminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/admin/users/{id}', [adminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::post('/admin/users/{id}/reset-password', [adminController::class, 'resetPassword'])->name('admin.users.reset-password');

    // Community Management Routes
    Route::get('/admin/communities', [adminController::class, 'showCommunities'])->name('admin.communities.index');
    Route::get('/admin/communities/create', [adminController::class, 'createCommunity'])->name('admin.communities.create');
    Route::post('/admin/communities', [adminController::class, 'storeCommunity'])->name('admin.communities.store');
    Route::get('/admin/communities/{id}/edit', [adminController::class, 'editCommunity'])->name('admin.communities.edit');
    Route::put('/admin/communities/{id}', [adminController::class, 'updateCommunity'])->name('admin.communities.update');
    Route::delete('/admin/communities/{id}', [adminController::class, 'deleteCommunity'])->name('admin.communities.delete');

    // Post Management Routes
    Route::get('/admin/posts', [adminController::class, 'showPosts'])->name('admin.posts.index');
    Route::get('/admin/posts/create', [adminController::class, 'createPost'])->name('admin.posts.create');
    Route::post('/admin/posts', [adminController::class, 'storePost'])->name('admin.posts.store');
    Route::get('/admin/posts/{id}/edit', [adminController::class, 'editPost'])->name('admin.posts.edit');
    Route::put('/admin/posts/{id}', [adminController::class, 'updatePost'])->name('admin.posts.update');
    Route::delete('/admin/posts/{id}', [adminController::class, 'deletePost'])->name('admin.posts.delete');

    // Chat Group Management Routes
    Route::get('/admin/chats', [adminController::class, 'showChats'])->name('admin.chats.index');
    Route::get('/admin/chats/create', [adminController::class, 'createChat'])->name('admin.chats.create');
    Route::post('/admin/chats', [adminController::class, 'storeChat'])->name('admin.chats.store');
    Route::get('/admin/chats/{id}/edit', [adminController::class, 'editChat'])->name('admin.chats.edit');
    Route::put('/admin/chats/{id}', [adminController::class, 'updateChat'])->name('admin.chats.update');
    Route::delete('/admin/chats/{id}', [adminController::class, 'deleteChat'])->name('admin.chats.delete');
});