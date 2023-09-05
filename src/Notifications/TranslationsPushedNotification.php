<?php

namespace Nikaia\TranslationSheet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TranslationsPushedNotification extends Notification
{
    use Queueable;

    private string $repository;
    private string $branch;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $repository, string $branch)
    {
        $this->repository = $repository;
        $this->branch = $branch;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $link = str_replace(':', '/', str_replace('git@', '', $this->repository));

        return (new MailMessage)
            ->line("New translations branch '$this->branch' was pushed to the repository: $this->repository")
            ->action('Link to branch', url("https://$link"));
    }
}
