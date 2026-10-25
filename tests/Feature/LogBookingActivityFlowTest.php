<?php

namespace Tests\Feature;

use App\Events\BookingConfirmed;
use App\Events\BookingRequested;
use App\Listeners\LogBookingActivity;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogBookingActivityFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_is_registered_for_booking_events(): void
    {
        Event::fake();

        Event::assertListening(BookingRequested::class, LogBookingActivity::class);
        Event::assertListening(BookingConfirmed::class, LogBookingActivity::class);
    }

    public function test_booking_through_the_api_writes_a_log_entry(): void
    {
        Log::spy();
        $user = User::factory()->create();
        $meetup = Meetup::factory()->create(['requires_approval' => true]);
        Sanctum::actingAs($user);

        $this->postJson("/api/meetups/{$meetup->id}/bookings")->assertCreated();

        $booking = Booking::where('user_id', $user->id)->firstOrFail();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
        Log::shouldHaveReceived('info')->with('BookingRequested', [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'meetup_id' => $meetup->id,
            'status' => 'pending',
        ]);
    }
}
