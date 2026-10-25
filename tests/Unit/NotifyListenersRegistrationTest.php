<?php

namespace Tests\Unit;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingRejected;
use App\Events\BookingRequested;
use App\Listeners\NotifyAttendeeOfConfirmation;
use App\Listeners\NotifyAttendeeOfRejection;
use App\Listeners\NotifyOrganizerOfBookingRequest;
use App\Listeners\NotifyOrganizerOfCancellation;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotifyListenersRegistrationTest extends TestCase
{
    public function test_each_event_has_its_notify_listener(): void
    {
        Event::fake();

        Event::assertListening(BookingRequested::class, NotifyOrganizerOfBookingRequest::class);
        Event::assertListening(BookingCancelled::class, NotifyOrganizerOfCancellation::class);
        Event::assertListening(BookingConfirmed::class, NotifyAttendeeOfConfirmation::class);
        Event::assertListening(BookingRejected::class, NotifyAttendeeOfRejection::class);
    }
}
