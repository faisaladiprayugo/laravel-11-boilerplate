<?php

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



///////////////////////////////////////////////////////////////////////////////////////////
// GROUP OF JWT TOKEN
///////////////////////////////////////////////////////////////////////////////////////////
Route::middleware(['jwt-authentication'])->group(function () {});
Route::get('/admins/show', [AdminController::class, 'show'])->name('admins--show');
