<?php
declare(strict_types=1);

use App\Modules\Task\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::apiResource('tasks', TaskController::class);
Route::patch('tasks/{task}/restore', [TaskController::class, 'restore'])->name('tasks.restore');

