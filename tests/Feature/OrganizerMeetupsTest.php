<?php

namespace Tests\Feature;

use App\Models\Meetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizerMeetupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_upcoming_meetups_of_one_organizer(): void
    {
        $organizer = User::factory()->create();
        $later = Meetup::factory()->for($organizer, 'organizer')->create(['starts_at' => now()->addDays(5), 'capacity' => 8]);
        $sooner = Meetup::factory()->for($organizer, 'organizer')->create(['starts_at' => now()->addDay()]);
        Meetup::factory()->for($organizer, 'organizer')->create(['starts_at' => now()->subDay()]);
        Meetup::factory()->create(['starts_at' => now()->addDay()]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/organizer/{$organizer->id}/meetups")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $sooner->id)
            ->assertJsonPath('data.1.id', $later->id)
            ->assertJsonPath('data.1.left_capacity', 8);

        $this->assertDatabaseCount('meetups', 4);
    }

    public function test_unknown_organizer_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/organizer/999/meetups')->assertNotFound();
    }

    public function test_guest_cannot_list_organizer_meetups(): void
    {
        $organizer = User::factory()->create();

        $this->getJson("/api/organizer/{$organizer->id}/meetups")->assertUnauthorized();
    }
}
