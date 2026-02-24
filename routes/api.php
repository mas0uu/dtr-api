<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DtrController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dtr/months', [DtrController::class, 'months']);
    Route::post('/dtr/months/start', [DtrController::class, 'startMonth']);
    
    Route::get('/dtr/months/current', [DtrController::class, 'currentMonth']);
    Route::get('/dtr/months/{monthId}', [DtrController::class, 'monthDetail']);

    Route::post('/dtr/months/{monthId}/rows', [DtrController::class, 'addRow']);
    Route::post('/dtr/rows/{rowId}/finish', [DtrController::class, 'finishRow']);


});