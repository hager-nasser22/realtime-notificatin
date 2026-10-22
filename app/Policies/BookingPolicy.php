<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
class BookingPolicy
{
    public function cancel(User $user , Booking $booking){
        return $booking->isOwner($user);
    }
    public function approve(User $user , Booking $booking){
        return $booking->meetup->organizedBy($user);
    }
    public function reject(User $user , Booking $booking){
        return $booking->meetup->organizedBy($user);
    }
}
