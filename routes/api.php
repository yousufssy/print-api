<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActionController;

// ─── Public ───────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Authenticated ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', fn(Request $r) => $r->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);      // جلب جميع الطلبات
    Route::post('/orders', [OrderController::class, 'store']);     // إنشاء طلب جديد
    Route::get('/orders/{id}/{year}', [OrderController::class, 'show']);    // عرض طلب محدد
    Route::put('/orders/{id}/{year}', [OrderController::class, 'update']);  // تحديث طلب
    Route::delete('/orders/{id}/{year}', [OrderController::class, 'destroy'])->middleware('admin'); // حذف طلب (Admin فقط)

    // Vouchers
    Route::apiResource('vouchers', VoucherController::class);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Actions
    Route::apiResource('actions', ActionController::class);

    // ─── Admin only ───────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});