<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Artisan::command('try:booking', function () {
    $org  = User::factory()->create();
    $user = User::factory()->create();

    $meetup = Meetup::create([
        'organizer_id'      => $org->id,
        'title'             => 'Laravel Cairo',
        'location'          => 'Cairo',
        'starts_at'         => now()->addDays(2),
        'capacity'          => 50,
        'requires_approval' => true,
    ]);
    $this->info("Meetup created: #{$meetup->id}");

    $booking = Booking::create([
        'user_id'   => $user->id,
        'meetup_id' => $meetup->id,
        'status'    => BookingStatus::Pending,
    ]);
    $this->info("Booking status: {$booking->status->value}");

    $booking->transitionTo(BookingStatus::Confirmed);
    $this->info("After approve: {$booking->status->value}");

    $booking->transitionTo(BookingStatus::Cancelled);
    $this->info("After cancel: {$booking->status->value}");

    $booking->transitionTo(BookingStatus::Pending);
    $this->info("After re-book: {$booking->status->value}");

    try {
        $booking->transitionTo(BookingStatus::Rejected);
        $booking->transitionTo(BookingStatus::Confirmed);
        $this->error('This should NOT happen!');
    } catch (\App\Exceptions\InvalidTransitionsBookingException $e) {
        $this->warn("Blocked as expected: {$e->getMessage()}");
    }
});
