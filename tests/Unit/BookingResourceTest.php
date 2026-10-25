<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use App\Policies\MeetupPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingResourceTest extends TestCase
{
    public function test_it_returns_status_value_and_omits_unloaded_relations(): void
    {
        $booking = new Booking(['status' => BookingStatus::Pending]);
        $booking->id = 9;
        $booking->created_at = Carbon::parse('2026-10-25 10:00:00', 'UTC');

        $data = (new BookingResource($booking))->resolve(new Request);

        $this->assertSame(9, $data['id']);
        $this->assertSame('pending', $data['status']);
        $this->assertSame('2026-10-25T10:00:00+00:00', $data['created_at']);
        $this->assertArrayNotHasKey('user', $data);
        $this->assertArrayNotHasKey('meetup', $data);
    }

    public function test_only_the_organizer_can_view_bookings(): void
    {
        $organizer = new User;
        $organizer->id = 1;
        $other = new User;
        $other->id = 2;
        $meetup = new Meetup(['organizer_id' => 1]);

        $this->assertTrue((new MeetupPolicy)->viewBookings($organizer, $meetup));
        $this->assertFalse((new MeetupPolicy)->viewBookings($other, $meetup));
    }
}
