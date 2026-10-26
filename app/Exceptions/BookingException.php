<?php

namespace App\Exceptions;

use Exception;

class BookingException extends Exception
{
    public static function organizerCannotBook(): self
    {
        return new self('You cannot book your own meetup.');
    }

    public static function meetupStarted(): self
    {
        return new self('This meetup has already started.');
    }

    public static function alreadyBooked(): self
    {
        return new self('You already have an active booking for this meetup.');
    }

    public static function rejected(): self
    {
        return new self('Your booking request for this meetup was rejected.');
    }

    public static function meetupFull(): self
    {
        return new self('This meetup is full.');
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 422);
    }
}
