<?php

namespace App\Console\Commands;

use App\Services\Changelog\Changelog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ChangelogRelease extends Command
{
    protected $signature = 'changelog:release
        {version : Release version, for example v0.8.0}
        {--path= : Changelog config path}';

    protected $description = 'Move unreleased changelog entries into a tagged release section.';

    public function handle(Changelog $changelog): int
    {
        $version = (string) $this->argument('version');
        $path = $this->changelogPath();
        $data = $this->loadChangelog($path);

        if (! str_starts_with($version, 'v')) {
            $this->error('Release versions must use the tag format vX.Y.Z.');

            return self::FAILURE;
        }

        $errors = $changelog->validateData($data);

        if ($errors !== []) {
            $this->displayErrors($errors);

            return self::FAILURE;
        }

        $unreleased = $data['unreleased'] ?? [];

        if ($unreleased === []) {
            $this->error('There are no unreleased changelog entries to release.');

            return self::FAILURE;
        }

        if (isset($data['releases'][$version])) {
            $this->error("The changelog already contains a release entry for {$version}.");

            return self::FAILURE;
        }

        $data['unreleased'] = [];
        $data['releases'] = [
            $version => [
                'date' => now()->toDateString(),
                'url' => "https://github.com/Darkdragon14/VolumeVault/releases/tag/{$version}",
                'items' => $unreleased,
            ],
            ...($data['releases'] ?? []),
        ];

        File::put($path, $this->renderChangelog($data));

        $this->info("Prepared changelog release {$version}.");

        return self::SUCCESS;
    }

    private function changelogPath(): string
    {
        return (string) ($this->option('path') ?: config_path('changelog.php'));
    }

    /**
     * @return array<string, mixed>
     */
    private function loadChangelog(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $data = require $path;

        return is_array($data) ? $data : [];
    }

    /**
     * @param  list<string>  $errors
     */
    private function displayErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->error($error);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderChangelog(array $data): string
    {
        return "<?php\n\nreturn ".$this->exportValue($data).";\n";
    }

    private function exportValue(mixed $value, int $level = 0): string
    {
        if (! is_array($value)) {
            return var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $level);
        $itemIndent = str_repeat('    ', $level + 1);
        $isList = array_is_list($value);
        $lines = ['['];

        foreach ($value as $key => $item) {
            $keyPrefix = $isList ? '' : var_export($key, true).' => ';
            $lines[] = $itemIndent.$keyPrefix.$this->exportValue($item, $level + 1).',';
        }

        $lines[] = $indent.']';

        return implode("\n", $lines);
    }
}
