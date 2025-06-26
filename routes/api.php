<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Transaction
    Route::get('/transaction', [TransactionController::class, 'index']);
    Route::post('/transaction', [TransactionController::class, 'store']);
    Route::get('/transaction/{transaction}', [TransactionController::class, 'show']);
    Route::put('/transaction/{transaction}', [TransactionController::class, 'update']);
    Route::delete('/transaction/{transaction}', [TransactionController::class, 'destroy']);
    
    // Category
    Route::get('/category', [CategoryController::class, 'index']);
    Route::post('/category', [CategoryController::class, 'store']);
    Route::put('/category/{category}', [CategoryController::class, 'update']);
    Route::delete('/category/{category}', [CategoryController::class, 'destroy']);
    
    // Report
    Route::get('/reports/summary', [ReportController::class, 'summary']);
    Route::get('/reports/chart', [ReportController::class, 'chart']);
    Route::post('/reports/export/pdf', [ReportController::class, 'exportPdf']);
    Route::post('/reports/export/excel', [ReportController::class, 'exportExcel']);

    // Payment
    Route::post('/payment/upgrade', [PaymentController::class, 'upgrade']);
    Route::post('/payment/donate', [PaymentController::class, 'donate']);
    Route::post('/payment/check', [PaymentController::class, 'checkStatus']);
});

Route::post('/payment/webhook', [PaymentController::class, 'webhook']);
