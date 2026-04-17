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
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\CartonController;

// ─── Auth ───────────────────────────────────────
Route::post('/login',   [AuthController::class, 'login']);
Route::get('/me',       fn(Request $r) => $r->user());
Route::post('/logout',  [AuthController::class, 'logout']);

// ─── Dashboard ──────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index']);

// ─── Orders ─────────────────────────────────────
Route::get('/orders',                  [OrderController::class, 'index']);
Route::post('/orders',                 [OrderController::class, 'store']);
Route::post('/orders/search',          [OrderController::class, 'advancedSearch']);
Route::post('/orders/search/export',   [OrderController::class, 'exportSearch']);
Route::get('/orders/{id}/{year}',      [OrderController::class, 'show']);
Route::put('/orders/{id}/{year}',      [OrderController::class, 'update']);
Route::delete('/orders/{id}/{year}',   [OrderController::class, 'destroy']);

// ─── Actions ────────────────────────────────────
Route::get('/actions',         [ActionController::class, 'index']);
Route::post('/actions',        [ActionController::class, 'store']);
Route::get('/actions/{id}',    [ActionController::class, 'show']);
Route::put('/actions/{id}',    [ActionController::class, 'update']);
Route::delete('/actions/{id}', [ActionController::class, 'destroy']);
Route::put('/actions/{id}/{year}',  [ActionController::class, 'update']);    // ← أضف year
Route::delete('/actions/{id}/{year}', [ActionController::class, 'destroy']);
// ─── Cartons ────────────────────────────────────
Route::get('/cartons',         [CartonController::class, 'index']);
Route::post('/cartons',        [CartonController::class, 'store']);
Route::get('/cartons/{id}',    [CartonController::class, 'show']);
Route::put('/cartons/{id}',    [CartonController::class, 'update']);
Route::delete('/cartons/{id}', [CartonController::class, 'destroy']);
Route::put('/cartons/{id}/{year}',  [CartonController::class, 'update']);    // ← أضف year
Route::delete('/cartons/{id}/{year}', [CartonController::class, 'destroy']);
// ─── بقية الـ Resources ─────────────────────────
Route::apiResource('vouchers',   VoucherController::class);
Route::apiResource('customers',  CustomerController::class);
Route::apiResource('users',      UserController::class);
Route::apiResource('materials',  MaterialController::class);
Route::apiResource('problems',   ProblemController::class);
