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
            $repoName = $repository['repository'];
            $link = str_replace(':', '/', str_replace('git@', '', $repoName));
            $message
                ->line("New translations branch '" . $repository['branch'] . "' was pushed to the repository: $repoName")
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
                $link = str_replace(':', '/', str_replace('git@', '', $repoName));
                $message->headerBlock("New translations were pushed");
                $message
                    ->contextBlock(function (ContextBlock $block) use ($branch, $repoName) {
                        $block->text(
                            "Branch: $branch \n\r".
                            "Repository: $repoName \n\r"
                        );
                    })
                    ->dividerBlock()
                    ->sectionBlock(function (SectionBlock $block) use ($branch, $repoName, $link) {
                        $block->field($link)->markdown();
                    });
            } else {
                $message->headerBlock("Could not push new translations");
                $message
                    ->contextBlock(function (ContextBlock $block) use ($branch, $repoName) {
                        $block->text(
                            "Branch: $branch \n\r Repository: $repoName \n\r"
                        );
                    })
                    ->dividerBlock()
                    ->sectionBlock(function (SectionBlock $block) use ($repository) {
                        $block->text($repository['error']);
                    });
            }
        }

        return $message;
    }
}
