<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ReportsController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/clientes', [ClienteController::class, 'clientes']);
Route::post('/clientes', [ClienteController::class, 'store']);
Route::post('/clientes/{id}', [ClienteController::class, 'update']);
Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);

Route::get('/sales', [SalesController::class, 'index']);
Route::get('/sales/{id}', [SalesController::class, 'show']);

Route::get('/reports/revenue', [ReportsController::class, 'revenue']);
Route::get('/reports/categories', [ReportsController::class, 'categories']);
Route::get('/reports/customers', [ReportsController::class, 'customers']);