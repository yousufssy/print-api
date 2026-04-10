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

// ─── All Public ───────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
Route::get('/me', fn(Request $r) => $r->user());
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}/{year}', [OrderController::class, 'show']);
Route::put('/orders/{id}/{year}', [OrderController::class, 'update']);
Route::delete('/orders/{id}/{year}', [OrderController::class, 'destroy']);

Route::apiResource('vouchers',   VoucherController::class);
Route::apiResource('customers',  CustomerController::class);
Route::apiResource('actions',    ActionController::class);
Route::apiResource('users',      UserController::class);
Route::apiResource('materials',  MaterialController::class);
Route::apiResource('problems',   ProblemController::class);
