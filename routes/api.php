<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;


Route::post('/login', [AuthController::class, 'login']);
Route::get('/clientes', [ClienteController::class, 'clientes']);
Route::post('/clientes', [ClienteController::class, 'store']);
Route::post('/clientes/{id}', [ClienteController::class, 'update']);
Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);
