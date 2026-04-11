<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);

    Route::post('/expenses/{id}/submit', [ExpenseController::class, 'submit']);
    Route::post('/expenses/{id}/approve', [ExpenseController::class, 'approve']);
    Route::post('/expenses/{id}/reject', [ExpenseController::class, 'reject']);
    Route::apiResource('expenses', ExpenseController::class);

    Route::post('/expenses/{id}/receipts', [ReceiptController::class, 'store']);
    Route::get('/expenses/{id}/receipts', [ReceiptController::class, 'index']);
    Route::get('/receipts/{id}/download', [ReceiptController::class, 'download']);
    Route::delete('/receipts/{id}', [ReceiptController::class, 'destroy']);

    Route::post('/expenses/{id}/comments', [CommentController::class, 'store']);
    Route::get('/expenses/{id}/comments', [CommentController::class, 'index']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    Route::get('/reports/team-summary', [ReportController::class, 'teamSummary']);
    Route::get('/reports/by-category', [ReportController::class, 'byCategory']);
    Route::get('/reports/by-member', [ReportController::class, 'byMember']);

    Route::patch('/admin/users/{user}/toggle-active', [AdminController::class, 'toggleUser']);
    Route::patch('/admin/teams/{team}/toggle-active', [AdminController::class, 'toggleTeam']);
    Route::patch('/admin/categories/{category}/toggle-active', [AdminController::class, 'toggleCategory']);
});