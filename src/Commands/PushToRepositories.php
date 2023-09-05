<?php

namespace Nikaia\TranslationSheet\Commands;

use App\Department;
use App\Notifications\TranslationsPushedNotification;
use App\Services\Slack;
use App\Shyfter\Department\Notificant;
use App\Shyfter\Department\Notificants;
use Illuminate\Console\Command;
use Nikaia\TranslationSheet\SheetPusher;
use Nikaia\TranslationSheet\Spreadsheet;

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
            $directory = storage_path("$basePath/$name");

            //Git Push to chosen remote
            $branch = uniqid('translations-');
            $hasChange = true;
            exec("cd $directory && git checkout -b $branch && git status --porcelain", $output);
            if ($output === [] || $output[1] === "Your branch is up-to-date with 'origin/master'.") {
                $hasChange = false;
                exec("git -D $branch");
            }

            if ($hasChange) {
                exec("git add . &&  git commit -m 'updating translation' ");
                exec("git push origin $branch -f");

                $this->info("Git pushed branch $branch to repository $repository");
                Notification::route('mail', 'jg@shyfter.co')
//                    ->route('mail', 'lh@shyfter.co')
//                    ->route('mail', 'maxim.kerstens@shyfter.co')
//                ->route('slack', 'https://hooks.slack.com/services/...')
                    ->notify(new TranslationsPushedNotification($repository, $branch));
            }
        });
    }
}
