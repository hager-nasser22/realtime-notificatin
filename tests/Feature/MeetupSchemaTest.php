<?php

namespace Tests\Feature;

use App\Models\Meetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MeetupSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_meetup_is_stored_with_its_organizer(): void
    {
        $organizer = User::factory()->create();

        $meetup = Meetup::factory()->for($organizer, 'organizer')->create([
            'title' => 'Laravel Cairo',
            'starts_at' => '2026-12-01 18:00:00',
        ]);

        $this->assertDatabaseHas('meetups', [
            'id' => $meetup->id,
            'organizer_id' => $organizer->id,
            'title' => 'Laravel Cairo',
            'requires_approval' => false,
        ]);
        $this->assertTrue($meetup->organizer->is($organizer));
        $this->assertTrue($organizer->organizedMeetups->contains($meetup));
    }

    public function test_attributes_are_cast(): void
    {
        $meetup = Meetup::factory()->create(['requires_approval' => 1])->fresh();

        $this->assertInstanceOf(Carbon::class, $meetup->starts_at);
        $this->assertTrue($meetup->requires_approval);
    }

    public function test_meetups_are_deleted_with_their_organizer(): void
    {
        $meetup = Meetup::factory()->create();

        $meetup->organizer->delete();

        $this->assertDatabaseMissing('meetups', ['id' => $meetup->id]);
    }
}
