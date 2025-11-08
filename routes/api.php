<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\DomainController;
use App\Http\Controllers\Api\Admin\DomainGroupController;
use App\Http\Controllers\Api\Admin\StateController;
use App\Http\Controllers\Api\Admin\CityController;
use App\Http\Controllers\Api\Admin\ZipCodeController;
use App\Http\Controllers\Api\Admin\ProviderController;
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
use App\Http\Controllers\Api\ReportController;
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
        // Role management routes
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/role/create', [RoleController::class, 'create']);
        Route::put('/role/update', [RoleController::class, 'update']);
        Route::post('/role/delete', [RoleController::class, 'delete']);
        Route::post('/role/update-permissions', [RoleController::class, 'updatePermissions']);
        Route::get('/permissions', [PermissionController::class, 'index']);
        
        // Domain permissions for roles
        Route::post('/role/assign-domains', [RoleController::class, 'assignDomains'])->name('admin.role.assign-domains');
        Route::delete('/role/revoke-domains', [RoleController::class, 'revokeDomains'])->name('admin.role.revoke-domains');
        Route::get('/role/{roleId}/domains', [RoleController::class, 'getDomains'])->name('admin.role.domains');
        
        
        // Admin management routes
        Route::get('/admins', [AdminController::class, 'index']);
        Route::post('/admins', [AdminController::class, 'create']);
        Route::put('/admins', [AdminController::class, 'update']);
        Route::delete('/admins', [AdminController::class, 'delete']);
        
        // User management routes
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'create']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'delete']);
        
        // Domain management routes (Super Admin only for create/update/delete)
        Route::get('/domains', [DomainController::class, 'index']);
        Route::get('/domains/{id}', [DomainController::class, 'show']);
        
        // Domain Groups management routes (Super Admin only)
        Route::middleware('super.admin')->group(function () {
            Route::get('/domain-groups', [DomainGroupController::class, 'index']);
            Route::get('/domain-groups/{id}', [DomainGroupController::class, 'show']);
            Route::post('/domain-groups', [DomainGroupController::class, 'store']);
            Route::put('/domain-groups/{id}', [DomainGroupController::class, 'update']);
            Route::delete('/domain-groups/{id}', [DomainGroupController::class, 'destroy']);
            Route::get('/domain-groups/{id}/domains', [DomainGroupController::class, 'domains']);
            
            // Domain CRUD (Super Admin only)
            Route::post('/domains', [DomainController::class, 'create']);
            Route::put('/domains/{id}', [DomainController::class, 'update']);
            Route::delete('/domains/{id}', [DomainController::class, 'destroy']);
            Route::post('/domains/{id}/regenerate-api-key', [DomainController::class, 'regenerateApiKey']);
        });
        
        // Geographic reference data routes
        Route::get('/states', [StateController::class, 'index']);
        Route::get('/states/all', [StateController::class, 'all']); // All active states (no pagination)
        Route::get('/states/{code}', [StateController::class, 'showByCode']);
        
        Route::get('/cities', [CityController::class, 'index']);
        Route::get('/cities/by-state/{stateId}', [CityController::class, 'byState']); // Cities of a specific state
        
        Route::get('/zip-codes', [ZipCodeController::class, 'index']);
        Route::get('/zip-codes/{code}', [ZipCodeController::class, 'show']); // Get ZIP by code
        Route::get('/zip-codes/by-state/{stateId}', [ZipCodeController::class, 'byState']); // ZIPs of a state
        Route::get('/zip-codes/by-city/{cityId}', [ZipCodeController::class, 'byCity']); // ZIPs of a city
        
        Route::get('/providers', [ProviderController::class, 'index']);
        Route::get('/providers/technologies', [ProviderController::class, 'technologies']); // Available technologies
        Route::get('/providers/{slug}', [ProviderController::class, 'show']); // Get provider by slug
        Route::get('/providers/by-technology/{technology}', [ProviderController::class, 'byTechnology']); // Providers by tech
        
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);
        
        // Admin's accessible domains
        Route::get('/my-domains', [AdminController::class, 'getMyDomains'])->name('admin.my-domains');

    });
});

// Report Submission API (Domain API Key Authentication)
Route::prefix('reports')->group(function () {
    // Public endpoint for domains to submit reports (authenticated via API key)
    Route::post('/submit', [ReportController::class, 'submit'])
        ->name('reports.submit');
    // Daily report endpoint (WordPress format)
    Route::post('/submit-daily', [ReportController::class, 'submitDaily'])
        ->name('reports.submit-daily');
});

// Report Management API (Admin Authentication) 
Route::middleware(['auth:sanctum', 'admin.auth'])->prefix('admin/reports')->group(function () {
    // Routes without domain restriction
    Route::get('/', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/recent', [ReportController::class, 'recent'])->name('admin.reports.recent');
    
    // Routes that check domain access
    Route::middleware('check.domain.access')->group(function () {
        Route::get('/domain/{domainId}/dashboard', [ReportController::class, 'dashboard'])->name('admin.reports.dashboard');
        Route::get('/domain/{domainId}/aggregate', [ReportController::class, 'aggregate'])->name('admin.reports.aggregate');
        Route::get('/{id}', [ReportController::class, 'show'])->name('admin.reports.show');
    });
    
    // Global/Cross-Domain Reports (filtered by accessible domains)
    Route::prefix('global')->group(function () {
        Route::get('/domain-ranking', [ReportController::class, 'globalRanking'])->name('admin.reports.global.ranking');
        Route::get('/comparison', [ReportController::class, 'compareDomains'])->name('admin.reports.global.comparison');
    });
});

// Broadcast routes for private channels
Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    return \Illuminate\Support\Facades\Broadcast::auth($request);
}); 
