<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaffPlanningController;

Route::get('/reception/staff/planning/events', [StaffPlanningController::class, 'events'])->name('staff.planning.events.debug');
