<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class BookingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, Email> */
    private function sentEmails(): array
    {
        return collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($sent) => $sent->getOriginalMessage())
            ->all();
    }

    private function assertSentTo(string $email, string $subject, string $bodyContains): void
    {
        $match = collect($this->sentEmails())->first(
            fn (Email $mail) => $mail->getTo()[0]->getAddress() === $email && $mail->getSubject() === $subject
        );

        $this->assertNotNull($match, "No '{$subject}' email sent to {$email}.");
        $this->assertStringContainsString($bodyContains, $match->getTextBody());
    }

    public function test_organizer_is_emailed_about_a_new_request(): void
    {
        $user = User::factory()->create(['name' => 'Hager']);
        $meetup = Meetup::factory()->create(['requires_approval' => true, 'title' => 'Laravel Cairo']);
        Sanctum::actingAs($user);

        $this->postJson("/api/meetups/{$meetup->id}/bookings")->assertCreated();

        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'status' => 'pending']);
        $this->assertSentTo($meetup->organizer->email, 'New booking request', 'Hager wants to join "Laravel Cairo"');
    }

    public function test_attendee_is_emailed_when_confirmed(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/approve")->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
        $this->assertSentTo($booking->user->email, 'Booking confirmed', "Your seat at \"{$booking->meetup->title}\" is confirmed");
    }

    public function test_attendee_is_emailed_when_rejected(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Sanctum::actingAs($booking->meetup->organizer);

        $this->postJson("/api/bookings/{$booking->id}/reject")->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'rejected']);
        $this->assertSentTo($booking->user->email, 'Booking request declined', 'was not accepted');
    }

    public function test_organizer_is_emailed_when_a_booking_is_cancelled(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);
        Sanctum::actingAs($booking->user);

        $this->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        $this->assertSentTo(
            $booking->meetup->organizer->email,
            'Booking cancelled',
            "{$booking->user->name} cancelled their booking for \"{$booking->meetup->title}\""
        );
    }

    public function test_failed_booking_sends_no_email(): void
    {
        $meetup = Meetup::factory()->create();
        Sanctum::actingAs($meetup->organizer);

        $this->postJson("/api/meetups/{$meetup->id}/bookings")->assertUnprocessable();

        $this->assertSame([], $this->sentEmails());
    }
}
