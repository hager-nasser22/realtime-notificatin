<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    private function addNotification(User $user, string $message, bool $read = false): string
    {
        $id = (string) Str::uuid();

        $user->notifications()->create([
            'id' => $id,
            'type' => 'booking.confirmed',
            'data' => ['message' => $message],
            'read_at' => $read ? now() : null,
        ]);

        return $id;
    }

    public function test_user_lists_only_their_notifications(): void
    {
        $user = User::factory()->create();
        $this->addNotification($user, 'mine');
        $this->addNotification(User::factory()->create(), 'not mine');
        Sanctum::actingAs($user);

        $this->getJson('/api/me/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'booking.confirmed')
            ->assertJsonPath('data.0.data.message', 'mine')
            ->assertJsonPath('data.0.read_at', null);
    }

    public function test_unread_filter_and_count(): void
    {
        $user = User::factory()->create();
        $this->addNotification($user, 'new');
        $this->addNotification($user, 'old', read: true);
        Sanctum::actingAs($user);

        $this->getJson('/api/me/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.message', 'new');

        $this->getJson('/api/me/notifications/unread-count')
            ->assertOk()
            ->assertExactJson(['count' => 1]);
    }

    public function test_user_can_mark_one_notification_as_read(): void
    {
        $user = User::factory()->create();
        $id = $this->addNotification($user, 'hello');
        Sanctum::actingAs($user);

        $this->postJson("/api/me/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('data.message', 'hello');

        $this->assertNotNull($user->notifications()->find($id)->read_at);
    }

    public function test_user_cannot_mark_someone_elses_notification(): void
    {
        $other = User::factory()->create();
        $id = $this->addNotification($other, 'private');
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/me/notifications/{$id}/read")->assertNotFound();

        $this->assertDatabaseHas('notifications', ['id' => $id, 'read_at' => null]);
    }

    public function test_user_can_mark_all_as_read(): void
    {
        $user = User::factory()->create();
        $this->addNotification($user, 'one');
        $this->addNotification($user, 'two');
        $otherId = $this->addNotification(User::factory()->create(), 'other');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/notifications/read-all')
            ->assertOk()
            ->assertExactJson(['message' => 'All notifications marked as read']);

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertDatabaseHas('notifications', ['id' => $otherId, 'read_at' => null]);
    }

    public function test_guest_cannot_see_notifications(): void
    {
        $this->getJson('/api/me/notifications')->assertUnauthorized();
    }
}
