<?php
declare(strict_types=1);

namespace App\Modules\Task\Notifications;

use App\Modules\Task\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskActionNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Task $task,
        private readonly string $action
    )
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("Task Status Change Notification")
                    ->greeting('Dear ' . $notifiable->name . ',')
                    ->line("Your task ' {$this->task->title} ' has been {$this->action} by admin.")
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our task application!')
                    ->line('Best regards,')
                    ->salutation('App Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
