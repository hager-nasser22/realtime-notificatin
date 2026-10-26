<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManageBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendee_can_cancel_their_booking(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);
        Sanctum::actingAs($booking->user);

        $this->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
    }

    public function test_other_user_cannot_cancel_the_booking(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/bookings/{$booking->id}/cancel")->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    public function test_organizer_can_approve_a_pending_booking(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.user.id', $booking->user_id);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    public function test_approve_fails_when_the_meetup_is_full(): void
    {
        $meetup = Meetup::factory()->create(['capacity' => 1]);
        Booking::factory()->for($meetup)->create(['status' => BookingStatus::Confirmed]);
        $pending = Booking::factory()->for($meetup)->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($meetup->organizer);

        $this->postJson("/api/bookings/{$pending->id}/approve")
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'This meetup is full.']);

        $this->assertDatabaseHas('bookings', ['id' => $pending->id, 'status' => 'pending']);
    }

    public function test_organizer_can_reject_a_pending_booking(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'rejected']);
    }

    public function test_invalid_transition_returns_422(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Rejected]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/approve")
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'invalid transition from rejected to confirmed']);
    }

    public function test_attendee_cannot_approve_their_own_booking(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->user);

        $this->postJson("/api/bookings/{$booking->id}/approve")->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }
}
