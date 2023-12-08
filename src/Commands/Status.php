<?php

namespace Nikaia\TranslationSheet\Commands;

use Illuminate\Console\Command;
use Nikaia\TranslationSheet\Sheet\TranslationsSheet;
use Nikaia\TranslationSheet\Spreadsheet;

class Status extends Command
{
    protected $signature = 'translation_sheet:status';

    protected $description = 'Display the status of translations : Locked / Unlocked.';

    public function handle(Spreadsheet $spreadsheet)
    {
        $spreadsheet->sheets()->each(function (TranslationsSheet $translationsSheet) {
            $this->info("Translation sheet status for [<comment>{$translationsSheet->getTitle()}</comment>] :");

            $locked = $translationsSheet->isTranslationsLocked();

            $label = $locked ? 'LOCKED' : 'UNLOCKED';
            $style = $locked ? 'error' : 'info';

            $this->line("Translations area is <$style>$label</$style>");
            $this->output->writeln(PHP_EOL);
        });
    }
}
