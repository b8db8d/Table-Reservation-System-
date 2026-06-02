<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JoiningGroupRestrictionController as AdminRestrictionController;
use App\Http\Controllers\Admin\OperatingHoursController as AdminOperatingHoursController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\RestaurantTableController as AdminTableController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\TableJoiningGroupController as AdminGroupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReservationCancellationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReservationDeepLinkController;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

Route::get('/', HomeController::class)->name('home');

Route::post('/reservations', [ReservationController::class, 'store'])
    ->name('reservations.store')
    ->middleware(['throttle:reservations', ProtectAgainstSpam::class]);

Route::get('/reservations/success', fn () => inertia('ReservationSuccess', [
    'referenceNumber' => session('reference_number'),
]))->name('reservations.success');

Route::get('/reservations/{reservation}/cancel/{token}', [ReservationCancellationController::class, 'cancel'])
    ->name('reservations.cancel');

Route::get('/reservations/{reservation}/confirm', [ReservationDeepLinkController::class, 'confirm'])
    ->name('reservations.deeplink.confirm')
    ->middleware('signed');

Route::get('/reservations/{reservation}/reject', [ReservationDeepLinkController::class, 'rejectForm'])
    ->name('reservations.deeplink.reject')
    ->middleware('signed');

Route::post('/reservations/{reservation}/reject', [ReservationDeepLinkController::class, 'reject'])
    ->name('reservations.deeplink.reject.submit')
    ->middleware('signed');

Route::get('/reservations/cancelled', fn () => inertia('ReservationCancelled', [
    'message' => session('message'),
]))->name('reservations.cancelled');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:manager|staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/create', [AdminReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [AdminReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/pending', [AdminReservationController::class, 'pending'])->name('reservations.pending');
    Route::get('/reservations/{reservation}/edit', [AdminReservationController::class, 'edit'])->name('reservations.edit');
    Route::patch('/reservations/{reservation}', [AdminReservationController::class, 'update'])->name('reservations.update');
    Route::patch('/reservations/{reservation}/confirm', [AdminReservationController::class, 'confirm'])->name('reservations.confirm');
    Route::patch('/reservations/{reservation}/reject', [AdminReservationController::class, 'reject'])->name('reservations.reject');
    Route::delete('/reservations/{reservation}', [AdminReservationController::class, 'destroy'])->name('reservations.destroy')->middleware('can:reservations.delete');

    Route::middleware('can:tables.manage')->prefix('tables')->name('tables.')->group(function () {
        Route::get('/', [AdminTableController::class, 'index'])->name('index');
        Route::get('/create', [AdminTableController::class, 'create'])->name('create');
        Route::post('/', [AdminTableController::class, 'store'])->name('store');
        Route::get('/{table}/edit', [AdminTableController::class, 'edit'])->name('edit');
        Route::patch('/{table}', [AdminTableController::class, 'update'])->name('update');
        Route::delete('/{table}', [AdminTableController::class, 'destroy'])->name('destroy');

        Route::prefix('groups')->name('groups.')->group(function () {
            Route::get('/', [AdminGroupController::class, 'index'])->name('index');
            Route::get('/create', [AdminGroupController::class, 'create'])->name('create');
            Route::post('/', [AdminGroupController::class, 'store'])->name('store');
            Route::get('/{group}/edit', [AdminGroupController::class, 'edit'])->name('edit');
            Route::patch('/{group}', [AdminGroupController::class, 'update'])->name('update');
            Route::delete('/{group}', [AdminGroupController::class, 'destroy'])->name('destroy');

            Route::get('/{group}/restrictions', [AdminRestrictionController::class, 'index'])->name('restrictions.index');
            Route::post('/{group}/restrictions', [AdminRestrictionController::class, 'store'])->name('restrictions.store');
            Route::delete('/{group}/restrictions/{restriction}', [AdminRestrictionController::class, 'destroy'])->name('restrictions.destroy');
        });
    });

    Route::middleware('can:operating-hours.manage')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/operating-hours', [AdminOperatingHoursController::class, 'index'])->name('operating-hours.index');
        Route::patch('/operating-hours', [AdminOperatingHoursController::class, 'update'])->name('operating-hours.update');
    });

    Route::middleware('can:staff.manage')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [AdminStaffController::class, 'index'])->name('index');
        Route::get('/create', [AdminStaffController::class, 'create'])->name('create');
        Route::post('/', [AdminStaffController::class, 'store'])->name('store');
        Route::patch('/{user}/toggle-active', [AdminStaffController::class, 'toggleActive'])->name('toggle-active');
    });
});

require __DIR__.'/settings.php';
