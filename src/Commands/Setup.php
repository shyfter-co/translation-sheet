<?php

namespace Nikaia\TranslationSheet\Commands;

use Illuminate\Console\Command;
use Nikaia\TranslationSheet\Sheet\TranslationsSheet;
use Nikaia\TranslationSheet\Spreadsheet;

class Setup extends Command
{
    protected $signature = 'translation_sheet:setup';

    protected $description = 'Setup spreadsheet and get it ready to host translations';

    public function handle(Spreadsheet $spreadsheet)
    {
        $this->output->writeln('<info>Running Shyfter custom setup first!</info>');
        $this->customShyfterSetup();

        $spreadsheet->ensureConfiguredSheetsAreCreated();

        $spreadsheet->sheets()->each(function (TranslationsSheet $translationsSheet) {
            $this->output->writeln(
                '<comment>Setting up translations sheet [' . $translationsSheet->getTitle() . ']</comment>'
            );

            $translationsSheet->api()->addBatchRequests(
                $translationsSheet->api()->setTabColor($translationsSheet->getId(), $translationsSheet->getTabColor())
            );
        });

        $spreadsheet->api()->sendBatchRequests();

        $this->output->writeln('<info>Done. Spreasheet is ready.</info>');
    }

    protected function customShyfterSetup()
    {
        $translationsSourceDir = config('translation_sheet.base_path');
        $this->addDirectoryIfDoesNotExist($translationsSourceDir);

        $directory = storage_path($translationsSourceDir);
        $repositories = config('translation_sheet.extra_sheets');

        if ($dir = opendir($directory)) {

            foreach ($repositories as $repository) {
                $repositoryName = $repository['name'];

                if (file_exists("$directory/$repositoryName")) {
                    $this->info("Skipping! The requested repository [$repositoryName] already exist in the translations folder.");
                } else {
                    $output = null;
                    $res = null;
                    $gitRepository = $repository['repo'];
                    exec("cd $directory && git clone --branch master $gitRepository", $output, $res);

                    if ($res === Command::SUCCESS) {
                        $this->info("Repository [$repositoryName] cloned successfully");
                    }
                    if ($res === Command::FAILURE) {
                        $this->error("Repository [$repositoryName] could not get cloned!");
                    }

                }
            }
        }

        closedir($dir);
    }

    /**
     * @param string $directory
     * @return void
     */
    protected function addDirectoryIfDoesNotExist(string $directory): void
    {
        if (!is_dir(storage_path($directory))) {
            mkdir(storage_path($directory));
        }
    }
}
