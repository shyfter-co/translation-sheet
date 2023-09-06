<?php

namespace Nikaia\TranslationSheet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;

class TranslationsPushedNotification extends Notification
{
    use Queueable;

    private Collection $processedRepositories;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Collection $processedRepositories)
    {
        $this->processedRepositories = $processedRepositories;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail', 'slack'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage);
        foreach ($this->processedRepositories as $repository) {
            $repo = $repository['repository'];
            $link = str_replace(':', '/', str_replace('git@', '', $repo));
            $message
                ->line("New translations branch '" . $repository['branch'] . "' was pushed to the repository: $repo")
                ->action('Link to branch', url("https://$link"));
        }

        return $message;
    }


    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $message = (new SlackMessage);
        foreach ($this->processedRepositories as $repository) {
            $repoName = $repository['repository'];
            $branch = $repository['branch'];
            if ($repository['success']) {
                $link = str_replace(':', '/', str_replace('git@', '', $repo));
                $message
                    ->text("New translations branch '$branch' was pushed to the repository: $repoName")
                    ->text(url("https://$link"))
                    ->unfurlLinks();
            } else {
                $message
                    ->text("Could not push to repository: $repoName.")
                    ->contextBlock(function (ContextBlock $block)  use ($branch) {
                        $block->text("Branch $branch");
                    })
                    ->dividerBlock()
                    ->sectionBlock(function (SectionBlock $block) use ($branch, $repository) {
                        $block->text("Process exited with the following errors:");
                        $block->text($repository['error']);
                    });

            }
        }

        return $message;
    }
}
