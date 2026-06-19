<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Redirect root to appropriate page
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'admin'
            ? redirect('/admin')
            : redirect('/agent');
    }
    return redirect('/login');
});

// Guest Routes
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate']);

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin Routes
    Route::middleware('admin')->group(function () {
        Route::get('/admin', [AdminController::class, 'index']);
        Route::get('/admin/users', [AdminController::class, 'users']);
        Route::post('/admin/users', [AdminController::class, 'createUser']);
        Route::put('/admin/users/{user}', [AdminController::class, 'updateUser']);
        Route::DELETE('/admin/users/{user}', [AdminController::class, 'deleteUser']);

        // Admin Ticket Management
        Route::get('/admin/tickets', [AdminController::class, 'tickets']);
        Route::get('/admin/tickets/{ticket}', [AdminController::class, 'showTicket']);
        Route::patch('/admin/tickets/{ticket}/assign', [AdminController::class, 'assignTicket']);
    });

    // Agent Routes
    Route::middleware('agent')->group(function () {
        Route::get('/agent', [AgentController::class, 'index']);
        Route::get('/agent/tickets/{ticket}', [AgentController::class, 'show']);
        Route::patch('/agent/tickets/{ticket}/status', [AgentController::class, 'updateStatus']);
        Route::patch('/agent/tickets/{ticket}/category', [AgentController::class, 'updateCategory']);
        Route::post('/agent/tickets/{ticket}/replies', [AgentController::class, 'storeReply']);
        Route::post('/agent/tickets/{ticket}/polish-reply', [AgentController::class, 'polishReply']);
    });
});
