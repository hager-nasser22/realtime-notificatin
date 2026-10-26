<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id', 'title', 'description', 'location', 'starts_at', 'capacity', 'requires_approval',
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

    public function organizedBy(User $user): bool
    {
        return $this->organizer_id === $user->id;
    }
}
