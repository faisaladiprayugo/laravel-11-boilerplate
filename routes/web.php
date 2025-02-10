<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DocumentationController;

Route::get('/', [DocumentationController::class, 'login'])->name('login');
Route::post('/login-process', [DocumentationController::class, 'loginProcess'])->name('login-process');
Route::get('/logout', [DocumentationController::class, 'logout'])->name('logout');
