<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AddressController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/clientes', [AuthController::class, 'clientes']);
Route::post('/clientes', [AuthController::class, 'store']);

Route::get('/customers/{customerId}/addresses', [AddressController::class, 'index']);
Route::post('/customers/{customerId}/addresses', [AddressController::class, 'store']);
Route::put('/customers/{customerId}/addresses/{id}', [AddressController::class, 'update']);
Route::delete('/customers/{customerId}/addresses/{id}', [AddressController::class, 'destroy']);