<?php

use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\CourtController as StaffCourtController;
use App\Http\Controllers\Staff\CourtSessionController as StaffCourtSessionController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\LiveBoardController;
use App\Http\Controllers\Staff\StaffAuthController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/login', fn () => redirect()->route('staff.login'))->name('login');

Route::middleware('guest')->group(function () {
	Route::get('/staff/login', [StaffAuthController::class, 'create'])->name('staff.login');
	Route::post('/staff/login', [StaffAuthController::class, 'store'])->name('staff.login.store');
});

Route::post('/staff/logout', [StaffAuthController::class, 'destroy'])
	->middleware('auth')
	->name('staff.logout');

Route::prefix('staff')
	->middleware(['auth', 'staff'])
	->group(function () {
		Route::get('/', DashboardController::class)->name('staff.dashboard');
		Route::get('/bookings', [StaffBookingController::class, 'index'])->name('staff.bookings.index');
		Route::patch('/bookings/{booking}/confirm', [StaffBookingController::class, 'confirm'])->name('staff.bookings.confirm');
		Route::patch('/bookings/{booking}/cancel', [StaffBookingController::class, 'cancel'])->name('staff.bookings.cancel');
		Route::patch('/bookings/{booking}/reschedule', [StaffBookingController::class, 'reschedule'])->name('staff.bookings.reschedule');
		Route::patch('/bookings/{booking}/assign', [StaffBookingController::class, 'assign'])->name('staff.bookings.assign');

		Route::get('/courts', [StaffCourtController::class, 'index'])->name('staff.courts.index');
		Route::post('/courts', [StaffCourtController::class, 'store'])->name('staff.courts.store');
		Route::patch('/courts/{court}', [StaffCourtController::class, 'update'])->name('staff.courts.update');

		Route::get('/sessions', [StaffCourtSessionController::class, 'index'])->name('staff.sessions.index');
		Route::post('/sessions', [StaffCourtSessionController::class, 'store'])->name('staff.sessions.store');

		Route::get('/board', LiveBoardController::class)->name('staff.board');
	});
