<?php

namespace App\Events;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class MeetupCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $title,
        public CarbonInterface $startsAt,
        public array $attendeeIds,
    ) {}
}
