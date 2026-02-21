<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\FileController;
// use App\Http\Controllers\Api\TaskController;
// use App\Http\Controllers\Api\MinuteController;
// use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\MinuteController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');

// Protected routes
Route::middleware('auth:api')->group(function () {
    
  
    // ===== CLIENTS =====
    Route::apiResource('clients', ClientController::class);
    Route::get('clients/search/{query}', [ClientController::class, 'search']);
    Route::get('clients/{id}/cases', [ClientController::class, 'cases']);
   


    Route::prefix('files')->group(function () {
        Route::post('upload', [FileController::class, 'upload']);
        Route::post('attach', [FileController::class, 'attach']);
        Route::post('detach', [FileController::class, 'detach']);
        Route::get('/', [FileController::class, 'index']);
        Route::get('{id}', [FileController::class, 'show']);
        Route::delete('{id}', [FileController::class, 'destroy']);
        Route::get('{id}/download', [FileController::class, 'download']);
    });

    // Case routes  
    Route::apiResource('cases', CaseController::class);
    Route::post('cases/{id}/archive', [CaseController::class, 'archive']);
    Route::post('cases/{id}/unarchive', [CaseController::class, 'unarchive']);
    Route::post('cases/{id}/attach-files', [CaseController::class, 'attachFiles']);

    // // Task routes
    Route::apiResource('tasks', TaskController::class);
    Route::post('tasks/{id}/archive', [TaskController::class, 'archive']);
    Route::post('tasks/{id}/unarchive', [TaskController::class, 'unarchive']);
    Route::post('tasks/{id}/attach-files', [TaskController::class, 'attachFiles']);

    // Minute routes
    Route::apiResource('minutes', MinuteController::class);
    Route::post('minutes/{id}/archive', [MinuteController::class, 'archive']);
    Route::post('minutes/{id}/unarchive', [MinuteController::class, 'unarchive']);
    Route::post('minutes/{id}/attach-files', [MinuteController::class, 'attachFiles']);

    // Client routes
    // Route::apiResource('clients', ClientController::class);
});