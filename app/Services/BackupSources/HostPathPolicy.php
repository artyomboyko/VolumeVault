<?php

namespace App\Services\BackupSources;

use InvalidArgumentException;

class HostPathPolicy
{
    public function normalize(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        $path = preg_replace('#/+#', '/', $path) ?: $path;

        return $path === '/' ? $path : rtrim($path, '/');
    }

    public function assertValid(string $path): void
    {
        if ($message = $this->validationError($path)) {
            throw new InvalidArgumentException($message);
        }
    }

    public function validationError(string $path): ?string
    {
        if (! str_starts_with($path, '/')) {
            return 'Host path must be an absolute path.';
        }

        if ($path === '/') {
            return 'Host path cannot be the filesystem root.';
        }

        if (str_contains($path, ',')) {
            return 'Host paths containing commas are not supported.';
        }

        $segments = array_filter(explode('/', $path), fn (string $segment): bool => $segment !== '');

        if (collect($segments)->contains(fn (string $segment): bool => $segment === '.' || $segment === '..')) {
            return 'Host path cannot contain . or .. segments.';
        }

        if (! $this->isAllowed($path)) {
            return 'Host path is outside VOLUMEVAULT_HOST_PATH_ALLOWLIST. Allowed prefixes: '.implode(', ', $this->allowedPrefixes()).'.';
        }

        return null;
    }

    public function isAllowed(string $path): bool
    {
        $prefixes = $this->allowedPrefixes();

        if ($prefixes === []) {
            return true;
        }

        foreach ($prefixes as $prefix) {
            if ($prefix === '/' || $path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function allowedPrefixes(): array
    {
        $allowlist = config('volumevault.host_path_allowlist', []);

        if (is_string($allowlist)) {
            $allowlist = explode(',', $allowlist);
        }

        if (! is_array($allowlist)) {
            return [];
        }

        return collect($allowlist)
            ->map(fn (mixed $path): string => $this->normalize(is_string($path) ? $path : null))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
