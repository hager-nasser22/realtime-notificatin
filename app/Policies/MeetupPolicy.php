<?php

namespace App\Policies;

use App\Models\Meetup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MeetupPolicy
{

    public function update(User $user, Meetup $meetup): bool
    {
        return $meetup->organizedBy($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Meetup $meetup): bool
    {
        return $meetup->organizedBy($user);
    }

    public function viewBookings(User $user, Meetup $meetup) : bool
    {
        return $meetup->organizedBy($user);
    }

}
