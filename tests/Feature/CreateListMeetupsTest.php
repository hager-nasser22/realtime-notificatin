<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateListMeetupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_meetup(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/meetups', [
            'title' => 'Laravel Cairo',
            'location' => 'Cairo',
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'capacity' => 30,
            'requires_approval' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Laravel Cairo')
            ->assertJsonPath('data.organizer.id', $user->id)
            ->assertJsonPath('data.requires_approval', true);

        $this->assertDatabaseHas('meetups', [
            'title' => 'Laravel Cairo',
            'organizer_id' => $user->id,
            'capacity' => 30,
            'requires_approval' => true,
        ]);
    }

    public function test_meetup_in_the_past_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/meetups', [
            'title' => 'Old',
            'location' => 'Cairo',
            'starts_at' => now()->subDay()->toDateTimeString(),
            'capacity' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors(['starts_at', 'capacity']);

        $this->assertDatabaseCount('meetups', 0);
    }

    public function test_guest_cannot_create_a_meetup(): void
    {
        $this->postJson('/api/meetups', [])->assertUnauthorized();
    }

    public function test_index_lists_only_upcoming_meetups_in_order(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $later = Meetup::factory()->create(['starts_at' => now()->addDays(10)]);
        $sooner = Meetup::factory()->create(['starts_at' => now()->addDay()]);
        Meetup::factory()->create(['starts_at' => now()->subDay()]);

        $this->getJson('/api/meetups')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $sooner->id)
            ->assertJsonPath('data.1.id', $later->id);
    }

    public function test_show_returns_left_capacity(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $meetup = Meetup::factory()->create(['capacity' => 10]);
        Booking::factory()->for($meetup)->create(['status' => BookingStatus::Confirmed]);
        Booking::factory()->for($meetup)->create(['status' => BookingStatus::Pending]);

        $this->getJson("/api/meetups/{$meetup->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $meetup->id)
            ->assertJsonPath('data.left_capacity', 9)
            ->assertJsonPath('data.organizer.id', $meetup->organizer_id);
    }
}
