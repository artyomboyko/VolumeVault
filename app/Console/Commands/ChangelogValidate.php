<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Changelog\Changelog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ChangelogValidate extends Command
{
    protected $signature = 'changelog:validate
        {version? : Release version to validate}
        {--release : Enforce release/tag publishing rules}
        {--path= : Changelog config path}';

    protected $description = 'Validate the local VolumeVault changelog structure.';

    public function handle(Changelog $changelog): int
    {
        $path = (string) ($this->option('path') ?: config_path('changelog.php'));

        if (! File::exists($path)) {
            $this->error("Changelog file not found at {$path}.");

            return self::FAILURE;
        }

        $data = require $path;

        if (! is_array($data)) {
            $this->error('The changelog file must return an array.');

            return self::FAILURE;
        }

        $errors = $changelog->validateData(
            data: $data,
            version: $this->argument('version') ? (string) $this->argument('version') : null,
            release: (bool) $this->option('release'),
        );
        $errors = [
            ...$errors,
            ...$changelog->validateTranslationsForLocales(User::SUPPORTED_LOCALES, $data),
        ];

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Changelog is valid.');

        return self::SUCCESS;
    }
}
