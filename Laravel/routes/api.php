<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::apiResources([
    'categories' => CategoryController::class,
    'venues' => VenueController::class,
    'organizers' => OrganizerController::class,
    'events' => EventController::class,
    'participants' => ParticipantController::class,
    'registrations' => RegistrationController::class,
]);
