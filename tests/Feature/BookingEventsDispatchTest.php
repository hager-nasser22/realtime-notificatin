<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingRejected;
use App\Events\BookingRequested;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingEventsDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([BookingRequested::class, BookingConfirmed::class, BookingRejected::class, BookingCancelled::class]);
    }

    public function test_booking_an_open_meetup_dispatches_confirmed(): void
    {
        $user = User::factory()->create();
        $meetup = Meetup::factory()->create(['requires_approval' => false]);
        Sanctum::actingAs($user);

        $this->postJson("/api/meetups/{$meetup->id}/bookings")
            ->assertCreated()
            ->assertJsonPath('data.status', 'confirmed');

        $booking = Booking::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        Event::assertDispatched(BookingConfirmed::class, fn ($e) => $e->booking->is($booking));
        Event::assertNotDispatched(BookingRequested::class);
    }

    public function test_booking_an_approval_meetup_dispatches_requested(): void
    {
        $user = User::factory()->create();
        $meetup = Meetup::factory()->create(['requires_approval' => true]);
        Sanctum::actingAs($user);

        $this->postJson("/api/meetups/{$meetup->id}/bookings")
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'status' => 'pending']);
        Event::assertDispatched(BookingRequested::class, fn ($e) => $e->booking->user_id === $user->id);
        Event::assertNotDispatched(BookingConfirmed::class);
    }

    public function test_approve_dispatches_confirmed(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/approve")->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
        Event::assertDispatched(BookingConfirmed::class, fn ($e) => $e->booking->is($booking));
    }

    public function test_reject_dispatches_rejected(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/reject")->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'rejected']);
        Event::assertDispatched(BookingRejected::class, fn ($e) => $e->booking->is($booking));
    }

    public function test_cancel_dispatches_cancelled(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);
        Sanctum::actingAs($booking->user);

        $this->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        Event::assertDispatched(BookingCancelled::class, fn ($e) => $e->booking->is($booking));
    }

    public function test_failed_booking_dispatches_nothing(): void
    {
        $meetup = Meetup::factory()->create(['capacity' => 1]);
        Booking::factory()->for($meetup)->create(['status' => BookingStatus::Confirmed]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/meetups/{$meetup->id}/bookings")->assertUnprocessable();

        Event::assertNothingDispatched();
    }
}
