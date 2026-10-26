<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\BookingRejectedNotification;
use App\Notifications\NewBookingRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_is_notified_about_a_new_request(): void
    {
        Notification::fake();
        $user = User::factory()->create(['name' => 'Hager']);
        $meetup = Meetup::factory()->create(['requires_approval' => true, 'title' => 'Laravel Cairo']);
        Sanctum::actingAs($user);

        $this->postJson("/api/meetups/{$meetup->id}/bookings")->assertCreated();

        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'status' => 'pending']);
        Notification::assertSentTo($meetup->organizer, NewBookingRequestNotification::class, function ($n, array $channels) use ($meetup) {
            return $channels === ['mail', 'database']
                && $n->toArray($meetup->organizer)['message'] === 'Hager wants to join "Laravel Cairo".';
        });
    }

    public function test_attendee_is_notified_when_confirmed(): void
    {
        Notification::fake();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/approve")->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
        Notification::assertSentTo($booking->user, BookingConfirmedNotification::class, fn ($n) => $n->booking->is($booking));
    }

    public function test_attendee_is_notified_when_rejected(): void
    {
        Notification::fake();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/reject")->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'rejected']);
        Notification::assertSentTo($booking->user, BookingRejectedNotification::class, fn ($n) => $n->booking->is($booking));
    }

    public function test_organizer_gets_a_database_notification_when_a_booking_is_cancelled(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);
        Sanctum::actingAs($booking->user);

        $this->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        $notification = $booking->meetup->organizer->notifications()->sole();
        $this->assertSame('booking.cancelled', $notification->type);
        $this->assertSame(
            "{$booking->user->name} cancelled their booking for \"{$booking->meetup->title}\".",
            $notification->data['message']
        );
    }

    public function test_confirmation_is_stored_and_emailed(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/approve")->assertOk();

        $notification = $booking->user->notifications()->sole();
        $this->assertSame('booking.confirmed', $notification->type);
        $this->assertSame($booking->id, $notification->data['booking_id']);

        $subjects = collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($sent) => $sent->getOriginalMessage()->getSubject());
        $this->assertContains("You're in: {$booking->meetup->title}", $subjects);
    }

    public function test_failed_booking_sends_no_notification(): void
    {
        Notification::fake();
        $meetup = Meetup::factory()->create();
        Sanctum::actingAs($meetup->organizer);

        $this->postJson("/api/meetups/{$meetup->id}/bookings")->assertUnprocessable();

        Notification::assertNothingSent();
    }
}
