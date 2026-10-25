<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Meetup\MeetupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('meetups', MeetupController::class);
    Route::get('/organizer/{user}/meetups', [MeetupController::class, 'organizerMeetups']);

    Route::get('/meetups/{meetup}/bookings', [BookingController::class, 'index']);
    Route::post('/meetups/{meetup}/bookings', [BookingController::class, 'store']);

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
