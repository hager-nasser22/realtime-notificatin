<?php

namespace App\Policies;

use App\Models\Meetup;
use App\Models\User;

class MeetupPolicy
{
    public function update(User $user, Meetup $meetup): bool
    {
        return $meetup->organizedBy($user);
    }

    public function delete(User $user, Meetup $meetup): bool
    {
        return $meetup->organizedBy($user);
    }

    public function viewBookings(User $user, Meetup $meetup): bool
    {
        return $meetup->organizedBy($user);
    }
}
