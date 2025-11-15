<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/me', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'throttle:api']);

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api');
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'throttle:api']);

