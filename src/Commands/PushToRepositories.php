<?php

namespace Nikaia\TranslationSheet\Commands;

use Nikaia\TranslationSheet\Notifications\TranslationsPushedNotification;
use Illuminate\Console\Command;
use Nikaia\TranslationSheet\SheetPusher;
use Nikaia\TranslationSheet\Spreadsheet;
use Illuminate\Support\Facades\Notification;
use Spatie\SlackAlerts\Facades\SlackAlert;

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

        collect($repositories)->each(function ($repo) use ($basePath) {
            $repository = $repo['repo'];
            $this->info("Preparing to push to git repository: $repository");
            $name = $repo['name'];
            $master = $repo['master'];
            $directory = storage_path("$basePath/$name");

            //Git Push to chosen remote
            $branch = uniqid('translations-');
            $this->info("Checking if branch: $branch has any changes");
            exec("cd $directory && git checkout -b $branch && git status --porcelain", $output);
            if (empty($output)) {
                $this->info("No changes found on branch: $branch");
                $this->info("Deleting branch: $branch");
                exec("cd $directory && git checkout $master && git branch -D $branch");
                $message = "No changes found on branch: $branch. Branch: $branch deleted.";
                SlackAlert::to('translations')->message($message);
            } else {
                $this->info("Preparing to push to git repository: $repository");
                exec("cd $directory && git checkout $branch && git add . && git commit -m 'updating translation' && git push origin $branch", $output, $result);

                switch ($result) {
                    case Command::SUCCESS:
                        $message = "Git pushed branch $branch to the repository $repository";
                        $this->info($message);
                        SlackAlert::to('translations')->message($message);
                    case Command::FAILURE:
                        $message = "Could not push branch $branch to the repository $repository";
                        $this->error("Repository [$repository] could not get cloned!");
                        SlackAlert::to('translations')->message($message);
                }

                Notification::route('mail', 'jg@shyfter.co')
                    ->route('mail', 'lh@shyfter.co')
                    ->route('mail', 'mk@shyfter.co')
                    ->notify(new TranslationsPushedNotification($repository, $branch));
            }
        });
    }
}
