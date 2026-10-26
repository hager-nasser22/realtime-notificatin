<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidTransitionsBookingException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InvalidTransitionResponseTest extends TestCase
{
    public function test_invalid_transition_renders_a_422_json_response(): void
    {
        Route::post('/api/test-invalid-transition', function () {
            throw new InvalidTransitionsBookingException(BookingStatus::Rejected, BookingStatus::Confirmed);
        });

        $this->postJson('/api/test-invalid-transition')
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'invalid transition from rejected to confirmed']);
    }
}
