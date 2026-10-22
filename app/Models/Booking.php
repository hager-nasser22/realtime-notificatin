<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidTransitionsBookingException;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'meetup_id',
        'status',
    ];
    protected function casts (): array
    {
        return [
            'status' => BookingStatus::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meetup()
    {
        return $this->belongsTo(Meetup::class);
    }
    public function transitionTo(BookingStatus $newStatus): void
    {
        if(! $this->status->canTransitionTo($newStatus)){
            throw new InvalidTransitionsBookingException($this->status , $newStatus);
        }
        $this->update(['status' => $newStatus]);
    }
    public function isOwner(User $user) : bool
    {
        return $this->user_id === $user->id;
    }
}
