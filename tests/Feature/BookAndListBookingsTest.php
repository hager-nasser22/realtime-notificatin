<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookAndListBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_book_a_meetup(): void
    {
        $user = User::factory()->create();
        $meetup = Meetup::factory()->create(['requires_approval' => false]);
        Sanctum::actingAs($user);

        $this->postJson("/api/meetups/{$meetup->id}/bookings")
            ->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.meetup.id', $meetup->id);

        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'meetup_id' => $meetup->id, 'status' => 'confirmed']);
    }

    public function test_booking_rule_errors_return_422(): void
    {
        $meetup = Meetup::factory()->create();
        Sanctum::actingAs($meetup->organizer);

        $this->postJson("/api/meetups/{$meetup->id}/bookings")
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'You cannot book your own meetup.']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_organizer_can_list_and_filter_bookings(): void
    {
        $meetup = Meetup::factory()->create();
        $pending = Booking::factory()->for($meetup)->create(['status' => BookingStatus::Pending]);
        Booking::factory()->for($meetup)->create(['status' => BookingStatus::Confirmed]);
        Booking::factory()->create();
        Sanctum::actingAs($meetup->organizer);

        $this->getJson("/api/meetups/{$meetup->id}/bookings")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/meetups/{$meetup->id}/bookings?status=pending")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id)
            ->assertJsonPath('data.0.user.id', $pending->user_id);
    }

    public function test_other_users_cannot_list_bookings(): void
    {
        $meetup = Meetup::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/meetups/{$meetup->id}/bookings")->assertForbidden();
    }
}
