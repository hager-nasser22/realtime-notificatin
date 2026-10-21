<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meetup extends Model
{
    protected $fillable = [
        'organizer_id', 'title', 'description', 'location', 'starts_at', 'capacity','requires_approval'
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'requires_approval' => 'boolean',
        ];
    }
     public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'bookings')->withTimestamps();
    }
}
