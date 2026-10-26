<?php

namespace Tests\Unit;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationResourceTest extends TestCase
{
    private function notification(?string $readAt): DatabaseNotification
    {
        $notification = new DatabaseNotification;
        $notification->forceFill([
            'id' => 'abc-123',
            'type' => 'booking.confirmed',
            'data' => ['message' => 'Hello'],
            'read_at' => $readAt ? Carbon::parse($readAt, 'UTC') : null,
            'created_at' => Carbon::parse('2026-10-26 08:00:00', 'UTC'),
        ]);

        return $notification;
    }

    public function test_unread_notification_has_null_read_at(): void
    {
        $data = (new NotificationResource($this->notification(null)))->resolve(new Request);

        $this->assertSame('abc-123', $data['id']);
        $this->assertSame('booking.confirmed', $data['type']);
        $this->assertSame(['message' => 'Hello'], $data['data']);
        $this->assertNull($data['read_at']);
        $this->assertSame('2026-10-26T08:00:00+00:00', $data['created_at']);
    }

    public function test_read_notification_has_iso_read_at(): void
    {
        $data = (new NotificationResource($this->notification('2026-10-26 09:30:00')))->resolve(new Request);

        $this->assertSame('2026-10-26T09:30:00+00:00', $data['read_at']);
    }
}
