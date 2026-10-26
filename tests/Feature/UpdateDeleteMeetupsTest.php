<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateDeleteMeetupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_update_their_meetup(): void
    {
        $meetup = Meetup::factory()->create(['title' => 'Old title']);
        Sanctum::actingAs($meetup->organizer);

        $this->patchJson("/api/meetups/{$meetup->id}", ['title' => 'New title', 'requires_approval' => true])
            ->assertOk()
            ->assertJsonPath('data.title', 'New title')
            ->assertJsonPath('data.requires_approval', true);

        $this->assertDatabaseHas('meetups', ['id' => $meetup->id, 'title' => 'New title', 'requires_approval' => true]);
    }

    public function test_other_user_cannot_update_the_meetup(): void
    {
        $meetup = Meetup::factory()->create(['title' => 'Old title']);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/meetups/{$meetup->id}", ['title' => 'Hacked'])->assertForbidden();

        $this->assertDatabaseHas('meetups', ['id' => $meetup->id, 'title' => 'Old title']);
    }

    public function test_capacity_cannot_drop_below_confirmed_bookings(): void
    {
        $meetup = Meetup::factory()->create(['capacity' => 10]);
        Booking::factory()->count(3)->for($meetup)->create(['status' => BookingStatus::Confirmed]);
        Sanctum::actingAs($meetup->organizer);

        $this->patchJson("/api/meetups/{$meetup->id}", ['capacity' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('capacity');

        $this->assertDatabaseHas('meetups', ['id' => $meetup->id, 'capacity' => 10]);
    }

    public function test_organizer_can_delete_their_meetup(): void
    {
        $meetup = Meetup::factory()->create();
        Sanctum::actingAs($meetup->organizer);

        $this->deleteJson("/api/meetups/{$meetup->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Meetup deleted successfully');

        $this->assertDatabaseMissing('meetups', ['id' => $meetup->id]);
    }

    public function test_other_user_cannot_delete_the_meetup(): void
    {
        $meetup = Meetup::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertForbidden();

        $this->assertDatabaseHas('meetups', ['id' => $meetup->id]);
    }
}
