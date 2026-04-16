<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OperationController; // ✅ غير الاسم أو استخدم ActionController
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

// ─── Operations (العمليات) ─────────────────────
// ✅ غير من /actions إلى /operations
Route::get('/operations',         [ActionController::class, 'index']);     // أو OperationController
Route::post('/operations',        [ActionController::class, 'store']);
Route::get('/operations/{id}',    [ActionController::class, 'show']);
Route::put('/operations/{id}',    [ActionController::class, 'update']);
Route::delete('/operations/{id}', [ActionController::class, 'destroy']);

// ─── Cartons ────────────────────────────────────
Route::get('/cartons',         [CartonController::class, 'index']);
Route::post('/cartons',        [CartonController::class, 'store']);
Route::get('/cartons/{id}',    [CartonController::class, 'show']);
Route::put('/cartons/{id}',    [CartonController::class, 'update']);
Route::delete('/cartons/{id}', [CartonController::class, 'destroy']);

// ─── Problems ───────────────────────────────────
Route::get('/problems',         [ProblemController::class, 'index']);
Route::post('/problems',        [ProblemController::class, 'store']);
Route::get('/problems/{id}',    [ProblemController::class, 'show']);
Route::put('/problems/{id}',    [ProblemController::class, 'update']);
Route::delete('/problems/{id}', [ProblemController::class, 'destroy']);

// ─── بقية الـ Resources ─────────────────────────
Route::apiResource('vouchers',   VoucherController::class);
Route::apiResource('customers',  CustomerController::class);
Route::apiResource('users',      UserController::class);
Route::apiResource('materials',  MaterialController::class);
