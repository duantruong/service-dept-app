<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TicketChartController;

// Authentication routes (public)
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes (require authentication)
Route::middleware('auth.session')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Ticket chart routes
    Route::get('/tickets/chart', [TicketChartController::class, 'form'])->name('tickets.form');
    Route::post('/tickets/chart', [TicketChartController::class, 'upload'])->name('tickets.upload');
});