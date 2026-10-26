<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingService;
    }

    public function test_open_meetup_confirms_the_booking(): void
    {
        $user = User::factory()->create();
        $meetup = Meetup::factory()->create(['requires_approval' => false]);

        $booking = $this->service->book($user, $meetup);

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'meetup_id' => $meetup->id, 'status' => 'confirmed']);
    }

    public function test_approval_meetup_creates_a_pending_booking(): void
    {
        $user = User::factory()->create();
        $meetup = Meetup::factory()->create(['requires_approval' => true]);

        $this->service->book($user, $meetup);

        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'status' => 'pending']);
    }

    public function test_organizer_cannot_book_own_meetup(): void
    {
        $meetup = Meetup::factory()->create();

        $this->expectExceptionObject(BookingException::organizerCannotBook());

        $this->service->book($meetup->organizer, $meetup);
    }

    public function test_started_meetup_cannot_be_booked(): void
    {
        $meetup = Meetup::factory()->create(['starts_at' => now()->subHour()]);

        $this->expectExceptionObject(BookingException::meetupStarted());

        $this->service->book(User::factory()->create(), $meetup);
    }

    public function test_full_meetup_cannot_be_booked(): void
    {
        $meetup = Meetup::factory()->create(['capacity' => 1]);
        Booking::factory()->for($meetup)->create(['status' => BookingStatus::Confirmed]);

        try {
            $this->service->book(User::factory()->create(), $meetup);
            $this->fail('Expected BookingException.');
        } catch (BookingException $e) {
            $this->assertSame('This meetup is full.', $e->getMessage());
        }

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_active_booking_cannot_be_duplicated(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);

        $this->expectExceptionObject(BookingException::alreadyBooked());

        $this->service->book($booking->user, $booking->meetup);
    }

    public function test_rejected_user_cannot_book_again(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Rejected]);

        $this->expectExceptionObject(BookingException::rejected());

        $this->service->book($booking->user, $booking->meetup);
    }

    public function test_cancelled_booking_is_reactivated_on_the_same_row(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Cancelled]);

        $rebooked = $this->service->book($booking->user, $booking->meetup);

        $this->assertSame($booking->id, $rebooked->id);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
        $this->assertDatabaseCount('bookings', 1);
    }
}
