<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Modules\Task\Models\Task;
use App\Modules\Task\Notifications\TaskActionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskActionNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_via_mail_channel(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $notification = new TaskActionNotification($task, 'deleted');

        $channels = $notification->via($user);

        $this->assertEquals(['mail'], $channels);
    }

    #[Test]
    public function it_creates_mail_message_with_correct_subject(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'title' => 'Important Task',
        ]);

        $notification = new TaskActionNotification($task, 'completed');
        $mailMessage = $notification->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Task Status Change Notification', $mailMessage->subject);
    }

    #[Test]
    public function it_includes_user_name_in_greeting(): void
    {
        $user = User::factory()->create(['name' => 'Jane Smith']);
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $notification = new TaskActionNotification($task, 'restored');
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('Dear Jane Smith,', $mailMessage->greeting);
    }

    #[Test]
    public function it_includes_task_title_in_message(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'title' => 'Update Documentation',
        ]);

        $notification = new TaskActionNotification($task, 'deleted');
        $mailMessage = $notification->toMail($user);

        $lineContainsTaskTitle = false;
        foreach ($mailMessage->introLines as $line) {
            if (str_contains($line, 'Update Documentation')) {
                $lineContainsTaskTitle = true;
                break;
            }
        }

        $this->assertTrue($lineContainsTaskTitle);
    }

    #[Test]
    public function it_includes_action_type_in_message(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $notification = new TaskActionNotification($task, 'restored');
        $mailMessage = $notification->toMail($user);

        $lineContainsAction = false;
        foreach ($mailMessage->introLines as $line) {
            if (str_contains($line, 'restored')) {
                $lineContainsAction = true;
                break;
            }
        }

        $this->assertTrue($lineContainsAction);
    }

    #[Test]
    public function it_includes_action_button(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $notification = new TaskActionNotification($task, 'deleted');
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('Notification Action', $mailMessage->actionText);
        $this->assertEquals(url('/'), $mailMessage->actionUrl);
    }

    #[Test]
    public function it_includes_thank_you_message(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $notification = new TaskActionNotification($task, 'completed');
        $mailMessage = $notification->toMail($user);

        $hasThankYouLine = false;
        // Check both introLines and outroLines
        $allLines = array_merge($mailMessage->introLines, $mailMessage->outroLines);
        foreach ($allLines as $line) {
            if (str_contains($line, 'Thank you')) {
                $hasThankYouLine = true;
                break;
            }
        }

        $this->assertTrue($hasThankYouLine);
    }

    #[Test]
    public function it_has_correct_salutation(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $notification = new TaskActionNotification($task, 'deleted');
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('App Team', $mailMessage->salutation);
    }

    #[Test]
    public function it_returns_empty_array_for_to_array(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $notification = new TaskActionNotification($task, 'deleted');
        $array = $notification->toArray($user);

        $this->assertIsArray($array);
        $this->assertEmpty($array);
    }

    #[Test]
    public function it_sends_notification_to_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $user->notify(new TaskActionNotification($task, 'deleted'));

        Notification::assertSentTo($user, TaskActionNotification::class);
    }

    #[Test]
    public function it_sends_notification_with_correct_task_and_action(): void
    {
        Notification::fake();

        $user = User::factory()->create(['name' => 'Test User']);
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'title' => 'Specific Task',
        ]);

        $user->notify(new TaskActionNotification($task, 'completed'));

        Notification::assertSentTo(
            $user,
            TaskActionNotification::class,
            function ($notification) use ($user) {
                $mailMessage = $notification->toMail($user);

                $hasTaskTitle = false;
                $hasAction = false;

                foreach ($mailMessage->introLines as $line) {
                    if (str_contains($line, 'Specific Task')) {
                        $hasTaskTitle = true;
                    }
                    if (str_contains($line, 'completed')) {
                        $hasAction = true;
                    }
                }

                return $hasTaskTitle && $hasAction;
            }
        );
    }

    #[Test]
    public function it_mentions_admin_as_actor(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $notification = new TaskActionNotification($task, 'deleted');
        $mailMessage = $notification->toMail($user);

        $mentionsAdmin = false;
        foreach ($mailMessage->introLines as $line) {
            if (str_contains($line, 'admin')) {
                $mentionsAdmin = true;
                break;
            }
        }

        $this->assertTrue($mentionsAdmin);
    }

    #[Test]
    public function it_handles_different_action_types(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $actions = ['deleted', 'restored', 'completed', 'updated', 'created'];

        foreach ($actions as $action) {
            $notification = new TaskActionNotification($task, $action);
            $mailMessage = $notification->toMail($user);

            $hasAction = false;
            foreach ($mailMessage->introLines as $line) {
                if (str_contains($line, $action)) {
                    $hasAction = true;
                    break;
                }
            }

            $this->assertTrue($hasAction, "Action '{$action}' not found in message");
        }
    }

    #[Test]
    public function it_uses_queueable_trait(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $notification = new TaskActionNotification($task, 'deleted');

        $this->assertTrue(
            in_array(\Illuminate\Bus\Queueable::class, class_uses_recursive($notification))
        );
    }
}