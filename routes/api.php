<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // the request endpoints will go here in later phases
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
