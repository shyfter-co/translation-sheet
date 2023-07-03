<?php
declare(strict_types=1);

namespace Nikaia\TranslationSheet\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ShyfterCustomSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will do a setup for translations.';


    public function handle(): int
    {
        $translationsSourceDir = 'translation-sources';
        $this->addDirectoryIfDoesNotExist($translationsSourceDir);

        $directory = storage_path($translationsSourceDir);
        $repositories = config('translation_sheet.extra_sheets');
        $successMessages = [];

        if ($dir = opendir($directory)) {

            foreach ($repositories as $repository) {
                $repositoryName = $repository['name'];

                if (file_exists("$directory/$repositoryName")) {
                    $this->info("Skipping! The requested repository [$repositoryName] already exist in the translations folder.");
                }
                else {
                    $output = null;
                    $res = null;
                    $gitRepository = $repository['repo'];
                    exec("cd $directory && git clone $gitRepository", $output, $res);

                    if ($res === 0) {
                        $successMessages[] = "Repository [$repositoryName] cloned successfully";
                    }

                }
            }
        }

        closedir($dir);

        foreach ($successMessages as $message) {
            $this->info($message);
        }
        
        // TODO: exec(Prepare::class);

        return Command::SUCCESS;
    }

    /**
     * @param $directories
     * @param string $directory
     * @return void
     */
    protected function addDirectoryIfDoesNotExist(string $directory): void
    {
        if (!is_dir(storage_path($directory))) {
            mkdir(storage_path($directory));
            $gitignore = fopen(storage_path("$directory/.gitignore"), 'w+');
            fwrite($gitignore, "*\n");
            fclose($gitignore);

            $gitignore = fopen(storage_path("./.gitignore"), 'a+');
            // todo: check if needs to write
            fwrite($gitignore, "$directory/\n");
            fclose($gitignore);
        }
    }
}
