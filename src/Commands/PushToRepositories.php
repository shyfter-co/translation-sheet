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
            $branch = uniqid('translations-');
            $this->info("Checking if branch: $branch has any changes");
            $output = [];
            exec("cd $directory && git checkout -b $branch && git status --porcelain", $output);

            if (empty($output)) {
                $this->info("No changes found on branch: $branch");
                $this->info("Deleting branch: $branch");
                exec("cd $directory && git checkout $master && git branch -D $branch",);
                return null;
            }

            $this->info("Preparing to push to git repository: $repository");

            $gitAddCommitProcess = new Process([
                'git', 'checkout', $branch, '&&', 'git', 'commit', '-m', "'updating translation'"
            ], $directory);
            $gitAddCommitProcess->run();
            $gitPushProcess = new Process(['git', 'push', 'origin', $branch], $directory);

            $response = [
                'branch' => $branch,
                'repository' => $repository,
                'success' => true
            ];

            try {
                $gitPushProcess->mustRun();
                return $response;
            } catch (ProcessFailedException $processFailedException) {
                $response['success'] = false;
                $response['error'] = $processFailedException->getProcess()->getErrorOutput();
                return $response;
            }
        })
            ->filter();

        // Notifications
        Notification::route('mail', 'jg@shyfter.co')
            ->route('mail', 'mk@shyfter.co')
            ->notify(new TranslationsPushedNotification($processedRepositories));
    }
}
