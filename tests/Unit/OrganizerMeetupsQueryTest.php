<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class OrganizerMeetupsQueryTest extends TestCase
{
    public function test_organized_meetups_uses_organizer_foreign_key(): void
    {
        $relation = (new User)->organizedMeetups();

        $this->assertSame('organizer_id', $relation->getForeignKeyName());
        $this->assertSame('meetups', $relation->getRelated()->getTable());
    }
}
