<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketChartController;
use App\Http\Controllers\UserController;

// Authentication routes (public)
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
/*
// Registration routes (public)
Route::get('/register', [UserController::class, 'create'])->name('register');
Route::post('/register', [UserController::class, 'store'])->name('register.post');
*/
// Protected routes (require authentication)
Route::middleware('auth.session')->group(function () {
    Route::get('/home', [TicketChartController::class, 'form'])->name('home');

    // Ticket chart routes
    Route::post('/tickets/chart', [TicketChartController::class, 'upload'])->name('tickets.upload');
    Route::post('/tickets/chart/filter', [TicketChartController::class, 'filter'])->name('tickets.filter');
    Route::get('/tickets/chart/{week?}', [TicketChartController::class, 'showChart'])->name('tickets.chart');
    Route::get('/tickets/list', [TicketChartController::class, 'getTickets'])->name('tickets.list');
});