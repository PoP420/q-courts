<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CourtController;
use App\Http\Controllers\Api\CourtSessionController;
use Illuminate\Support\Facades\Route;

// Courts
Route::get('/courts', [CourtController::class, 'index']);

// Bookings (used by the website's booking widget)
Route::get('/bookings', [BookingController::class, 'index']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::patch('/bookings/{booking}', [BookingController::class, 'update']);
Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);

// Live sessions (used by the staff mobile app for time/game monitoring)
Route::get('/sessions/active', [CourtSessionController::class, 'active']);
Route::post('/sessions/start', [CourtSessionController::class, 'start']);
Route::patch('/sessions/{session}/end', [CourtSessionController::class, 'end']);
Route::patch('/sessions/{session}/score', [CourtSessionController::class, 'updateScore']);
