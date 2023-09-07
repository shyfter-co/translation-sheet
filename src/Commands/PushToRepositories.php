<?php

namespace Nikaia\TranslationSheet\Commands;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Console\Command;
use Nikaia\TranslationSheet\SheetPusher;
use Nikaia\TranslationSheet\Spreadsheet;
use Illuminate\Support\Facades\Notification;
use Nikaia\TranslationSheet\Notifications\TranslationsPushedNotification;

class PushToRepositories extends Command
{
    protected $signature = 'translation_sheet:push-repositories';
    protected $description = 'Push updated translations to Shyfter repositories.';

    /**
     * @throws \Exception
     */
    public function handle(SheetPusher $pusher, Spreadsheet $spreadsheet)
    {
        $repositories = config('translation_sheet.extra_sheets');
        $basePath = config('translation_sheet.base_path');

        $processedRepositories = collect($repositories)->map(function ($repo) use ($basePath) {
            $repository = $repo['repo'];
            $this->info("Preparing to push to git repository: $repository");
            $name = $repo['name'];
            $master = $repo['master'];
            $directory = storage_path("$basePath/$name");

            // git push to remote if there were some changes
            $branch = uniqid("translations-$name-");
            $this->info("Checking if branch: $branch has any changes");
            $output = [];
            exec("cd $directory && git checkout -b $branch");

            $gitStatusProcess = new Process(['git', 'status', '--porcelain'], $directory);
            $gitStatusProcess->run();
            $output = $gitStatusProcess->getOutput();

            if (empty($output)) {
                $this->info("No changes found on branch: $branch");
                $this->info("Deleting branch: $branch");
                exec("cd $directory && git checkout $master && git branch -D $branch");

                return null;
            }

            $comands = [
                ['git', 'checkout', $branch],
                ['git', 'add', '.'],
                ['git', 'commit', '-m', '"updating translations"']
            ];

            foreach ($comands as $cmd) {
                $process = new Process($cmd, $directory);
                $process->mustRun();
            }

            $gitPushProcess = new Process(['git', 'push', 'origin', $branch], $directory);

            $response = [
                'branch' => $branch,
                'repository' => $repository,
                'success' => true
            ];

            try {
                $gitPushProcess->mustRun();
                exec("cd $directory && git checkout $master && git branch -D $branch");
                return $response;
            } catch (ProcessFailedException $processFailedException) {
                $response['success'] = false;
                $response['error'] = $processFailedException->getProcess()->getErrorOutput();
                exec("cd $directory && git checkout $master && git branch -D $branch");
                return $response;
            }

        })
            ->filter();

        if (!$processedRepositories->isEmpty()) {
            // Notifications
            $notification = Notification::route('slack', config('translation_sheet.notifications.slack'));
            $notificants = explode(',', config('translation_sheet.notifications.mail'));
            foreach ($notificants as $notificant) {
                $notification->route('mail', $notificant);
            }
            $notification->notify(new TranslationsPushedNotification($processedRepositories));
        }
    }
}
