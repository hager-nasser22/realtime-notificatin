<?php

namespace Tests\Unit;

use App\Exceptions\BookingException;
use Illuminate\Http\Request;
use Tests\TestCase;

class BookingExceptionTest extends TestCase
{
    public function test_named_constructors_have_clear_messages(): void
    {
        $this->assertSame('You cannot book your own meetup.', BookingException::organizerCannotBook()->getMessage());
        $this->assertSame('This meetup has already started.', BookingException::meetupStarted()->getMessage());
        $this->assertSame('You already have an active booking for this meetup.', BookingException::alreadyBooked()->getMessage());
        $this->assertSame('Your booking request for this meetup was rejected.', BookingException::rejected()->getMessage());
        $this->assertSame('This meetup is full.', BookingException::meetupFull()->getMessage());
    }

    public function test_it_renders_as_422_json(): void
    {
        $response = BookingException::meetupFull()->render(new Request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['message' => 'This meetup is full.'], $response->getData(true));
    }
}
