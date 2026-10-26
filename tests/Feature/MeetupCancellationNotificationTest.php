<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Events\BookingConfirmed;
use App\Events\MeetupCancelled;
use App\Listeners\NotifyAttendeeOfConfirmation;
use App\Listeners\NotifyAttendeesOfMeetupCancellation;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MeetupCancellationNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, Meetup|Booking> */
    private function meetupWithBookings(): array
    {
        $meetup = Meetup::factory()->create(['title' => 'Laravel Cairo']);
        $confirmed = Booking::factory()->for($meetup)->create(['status' => BookingStatus::Confirmed]);
        $pending = Booking::factory()->for($meetup)->create(['status' => BookingStatus::Pending]);
        $cancelled = Booking::factory()->for($meetup)->create(['status' => BookingStatus::Cancelled]);

        return [$meetup, $confirmed, $pending, $cancelled];
    }

    public function test_deleting_a_meetup_dispatches_event_with_active_attendees(): void
    {
        Event::fake([MeetupCancelled::class]);
        [$meetup, $confirmed, $pending] = $this->meetupWithBookings();
        Sanctum::actingAs($meetup->organizer);

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertOk();

        $this->assertDatabaseMissing('meetups', ['id' => $meetup->id]);
        $this->assertDatabaseCount('bookings', 0);
        Event::assertDispatched(MeetupCancelled::class, fn (MeetupCancelled $e) => $e->title === 'Laravel Cairo'
            && collect($e->attendeeIds)->sort()->values()->all() === collect([$confirmed->user_id, $pending->user_id])->sort()->values()->all());
    }

    public function test_active_attendees_are_emailed_and_cancelled_ones_are_not(): void
    {
        [$meetup, $confirmed, $pending, $cancelled] = $this->meetupWithBookings();
        Sanctum::actingAs($meetup->organizer);

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertOk();

        $recipients = collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($sent) => $sent->getOriginalMessage())
            ->filter(fn (Email $mail) => $mail->getSubject() === 'Meetup cancelled')
            ->map(fn (Email $mail) => $mail->getTo()[0]->getAddress());

        $this->assertEqualsCanonicalizing(
            [User::find($confirmed->user_id)->email, User::find($pending->user_id)->email],
            $recipients->values()->all()
        );
        $this->assertNotContains(User::find($cancelled->user_id)->email, $recipients);
    }

    public function test_other_user_cannot_cancel_and_nothing_is_dispatched(): void
    {
        Event::fake([MeetupCancelled::class]);
        [$meetup] = $this->meetupWithBookings();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertForbidden();

        $this->assertDatabaseHas('meetups', ['id' => $meetup->id]);
        Event::assertNotDispatched(MeetupCancelled::class);
    }

    public function test_email_listeners_are_pushed_to_the_queue(): void
    {
        Queue::fake();
        [$meetup] = $this->meetupWithBookings();
        Sanctum::actingAs($meetup->organizer);

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertOk();
        BookingConfirmed::dispatch(Booking::factory()->create());

        Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === NotifyAttendeesOfMeetupCancellation::class);
        Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === NotifyAttendeeOfConfirmation::class);
    }
}
