<?php

namespace Nikaia\TranslationSheet\Commands;

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

            //Git Push to chosen remote
            $branch = uniqid('translations-');
            $this->info("Checking if branch: $branch has any changes");
            exec("cd $directory && git checkout -b $branch && git status --porcelain", $output);
            if (empty($output)) {
                $this->info("No changes found on branch: $branch");
                $this->info("Deleting branch: $branch");
                exec("cd $directory
                                    && git checkout $master
                                    && git branch -D $branch",
                    $output,
                    $result
                );
            } else {
                $this->info("Preparing to push to git repository: $repository");
                exec("cd $directory && git checkout $branch
                                            && git add .
                                            && git commit -m 'updating translation'
                                            && git push origin $branch",
                    $output,
                    $result
                );
            }
            return [$output, $result, $branch, $repository];
        });

//        switch ($result) {
//            case Command::SUCCESS:
//                return  "Git pushed branch $branch to the repository $repository";
//            case Command::FAILURE:
//                return "Could not push branch $branch to the repository $repository";
//        }

        // Notifications
//        $processedRepositories

//        Notification::route('mail', 'jg@shyfter.co')
//            ->route('mail', 'lh@shyfter.co')
//            ->route('mail', 'mk@shyfter.co')
//            ->notify(new TranslationsPushedNotification($repository, $branch))
        ;
    }
}
