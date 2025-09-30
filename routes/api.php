<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\Auth\ResendVerificationCodeController;
use App\Http\Controllers\Api\Auth\AdminLoginController;
use App\Http\Controllers\Api\Auth\AdminRegisterController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Api\Chat\ChatController;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth routes
Route::post('/login', LoginController::class);  
Route::post('/register', RegisterController::class);
Route::post('/verify-email', VerifyEmailController::class);
Route::post('/resend-verification-code', ResendVerificationCodeController::class);

// Chat routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/chat/create-private', [ChatController::class, 'createPrivateChat']);
    Route::post('/chat/create-group', [ChatController::class, 'createGroupChat']);
    // Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::post('/chat/{chatId}/send', [ChatController::class, 'sendMessageToChat']);
    Route::get('/chat/{chatId}/messages', [ChatController::class, 'getChatMessages']);
    Route::post('/chat/{chatId}/read', [ChatController::class, 'markMessagesAsRead']);
    Route::get('/chat/{chatId}/unread-count', [ChatController::class, 'getUnreadCount']);
    Route::get('/chat/conversation/{otherUserId}/{otherUserType}', [ChatController::class, 'getConversation']);
    Route::get('/chats', [ChatController::class, 'getChats']);
    Route::post('/broadcasting/auth', [ChatController::class, 'broadcastAuth']);
});

// Admin Auth routes
Route::prefix('admin')->group(function () {
    Route::post('/login', AdminLoginController::class);
    Route::post('/register', AdminRegisterController::class);
    
    // Protected admin routes
    Route::middleware(['auth:sanctum', 'admin.auth'])->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/role/create', [RoleController::class, 'create']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        // Admin chat routes
        // Route::prefix('chat')->group(function () {
        //     Route::get('/conversations', [AdminChatController::class, 'getConversations']);
        //     Route::get('/conversation', [AdminChatController::class, 'getConversationWithUser']);
        //     Route::post('/send', [AdminChatController::class, 'sendMessageToUser']);
        //     Route::post('/create-private', [ChatController::class, 'createPrivateChat']);
        // });
    });
});

// Broadcast routes for private channels
Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    return \Illuminate\Support\Facades\Broadcast::auth($request);
}); 
