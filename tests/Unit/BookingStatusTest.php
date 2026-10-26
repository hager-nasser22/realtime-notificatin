<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidTransitionsBookingException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BookingStatusTest extends TestCase
{
    public static function transitions(): array
    {
        return [
            'pending to confirmed' => [BookingStatus::Pending, BookingStatus::Confirmed, true],
            'pending to cancelled' => [BookingStatus::Pending, BookingStatus::Cancelled, true],
            'pending to rejected' => [BookingStatus::Pending, BookingStatus::Rejected, true],
            'confirmed to cancelled' => [BookingStatus::Confirmed, BookingStatus::Cancelled, true],
            'confirmed to pending' => [BookingStatus::Confirmed, BookingStatus::Pending, false],
            'confirmed to rejected' => [BookingStatus::Confirmed, BookingStatus::Rejected, false],
            'cancelled to pending' => [BookingStatus::Cancelled, BookingStatus::Pending, true],
            'cancelled to confirmed' => [BookingStatus::Cancelled, BookingStatus::Confirmed, true],
            'cancelled to rejected' => [BookingStatus::Cancelled, BookingStatus::Rejected, false],
            'rejected to confirmed' => [BookingStatus::Rejected, BookingStatus::Confirmed, false],
            'rejected to pending' => [BookingStatus::Rejected, BookingStatus::Pending, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_can_transition_to(BookingStatus $from, BookingStatus $to, bool $allowed): void
    {
        $this->assertSame($allowed, $from->canTransitionTo($to));
    }

    public function test_every_status_has_a_label(): void
    {
        $this->assertSame('Pending', BookingStatus::Pending->label());
        $this->assertSame('Confirmed', BookingStatus::Confirmed->label());
        $this->assertSame('Cancelled', BookingStatus::Cancelled->label());
        $this->assertSame('Rejected', BookingStatus::Rejected->label());
    }

    public function test_exception_message_names_both_statuses(): void
    {
        $exception = new InvalidTransitionsBookingException(BookingStatus::Cancelled, BookingStatus::Confirmed);

        $this->assertSame('invalid transition from cancelled to confirmed', $exception->getMessage());
    }
}
