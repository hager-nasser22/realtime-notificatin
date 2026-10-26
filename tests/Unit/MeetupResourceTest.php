<?php

namespace Tests\Unit;

use App\Http\Resources\MeetupResource;
use App\Models\Meetup;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MeetupResourceTest extends TestCase
{
    private function meetup(): Meetup
    {
        $meetup = new Meetup([
            'title' => 'Laravel Cairo',
            'location' => 'Cairo',
            'starts_at' => Carbon::parse('2026-12-01 18:00:00', 'UTC'),
            'capacity' => 20,
            'requires_approval' => true,
        ]);
        $meetup->id = 3;

        return $meetup;
    }

    public function test_left_capacity_is_capacity_minus_confirmed_bookings(): void
    {
        $meetup = $this->meetup();
        $meetup->confirmed_bookings_count = 5;

        $data = (new MeetupResource($meetup))->resolve(new Request);

        $this->assertSame(15, $data['left_capacity']);
        $this->assertSame('2026-12-01T18:00:00+00:00', $data['starts_at']);
        $this->assertTrue($data['requires_approval']);
    }

    public function test_left_capacity_is_omitted_when_not_counted(): void
    {
        $data = (new MeetupResource($this->meetup()))->resolve(new Request);

        $this->assertArrayNotHasKey('left_capacity', $data);
        $this->assertArrayNotHasKey('organizer', $data);
    }
}
