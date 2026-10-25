<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidTransitionsBookingException;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_is_stored_with_its_user_and_meetup(): void
    {
        $booking = Booking::factory()->create();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'user_id' => $booking->user->id,
            'meetup_id' => $booking->meetup->id,
            'status' => 'pending',
        ]);
        $this->assertTrue($booking->meetup->bookings->contains($booking));
    }

    public function test_transition_to_persists_the_new_status(): void
    {
        $booking = Booking::factory()->create();

        $booking->transitionTo(BookingStatus::Confirmed);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    public function test_disallowed_transition_leaves_the_status_unchanged(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Cancelled]);

        try {
            $booking->transitionTo(BookingStatus::Confirmed);
            $this->fail('Expected InvalidTransitionsBookingException.');
        } catch (InvalidTransitionsBookingException) {
        }

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
    }

    public function test_confirmed_bookings_only_returns_confirmed(): void
    {
        $meetup = Meetup::factory()->create();
        $confirmed = Booking::factory()->for($meetup)->create(['status' => BookingStatus::Confirmed]);
        Booking::factory()->for($meetup)->create(['status' => BookingStatus::Pending]);

        $this->assertSame([$confirmed->id], $meetup->confirmedBookings->pluck('id')->all());
    }

    public function test_user_cannot_book_the_same_meetup_twice(): void
    {
        $user = User::factory()->create();
        $meetup = Meetup::factory()->create();
        Booking::factory()->for($user)->for($meetup)->create();

        $this->expectException(QueryException::class);

        Booking::factory()->for($user)->for($meetup)->create();
    }
}
