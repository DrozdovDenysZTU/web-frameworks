<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

$catalog = [
    'categories' => CategoryController::class,
    'venues' => VenueController::class,
    'organizers' => OrganizerController::class,
    'events' => EventController::class,
];

$management = [
    'participants' => ParticipantController::class,
    'registrations' => RegistrationController::class,
];

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('me', [AuthController::class, 'me'])->middleware(['auth:api', 'role:client']);
});

Route::middleware('auth:api')->group(function () use ($catalog, $management) {
    Route::middleware('role:client')->group(function () use ($catalog) {
        Route::apiResources($catalog, ['only' => ['index', 'show']]);
    });

    Route::middleware('role:manager')->group(function () use ($catalog, $management) {
        Route::apiResources($catalog, ['only' => ['store', 'update']]);
        Route::apiResources($management, ['only' => ['index', 'show', 'store', 'update']]);
    });

    Route::middleware('role:admin')->group(function () use ($catalog, $management) {
        Route::apiResources([...$catalog, ...$management], ['only' => ['destroy']]);
        Route::apiResource('users', UserController::class);
    });
});
