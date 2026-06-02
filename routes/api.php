<?php

use App\Http\Controllers\Api\AvailabilityController;
use Illuminate\Support\Facades\Route;

Route::get('availability', AvailabilityController::class)->name('api.availability');
