<?php

namespace App\Services\Changelog;

use App\Models\User;

class Changelog
{
    public const TYPE_FEATURE = 'feature';

    public const TYPE_CHANGE = 'change';

    public const TYPE_MIGRATION = 'migration';

    public const TYPE_BREAKING = 'breaking';

    public const UNRELEASED_VERSION = 'unreleased';

    public const ALLOWED_TYPES = [
        self::TYPE_FEATURE,
        self::TYPE_CHANGE,
        self::TYPE_MIGRATION,
        self::TYPE_BREAKING,
    ];

    /**
     * @return array{has_unread: true, current_version: string, last_seen_version: ?string, changelog_id: string, item_count: int, sections: list<array<string, mixed>>}|null
     */
    public function unreadForUser(User $user): ?array
    {
        $sections = $this->sectionsForUser($user);

        if ($sections === []) {
            return null;
        }

        $changelogId = $this->changelogIdForSections($sections);

        if ($user->last_seen_changelog_id === $changelogId) {
            return null;
        }

        return [
            'has_unread' => true,
            'current_version' => $this->currentVersion(),
            'last_seen_version' => $user->last_seen_app_version,
            'changelog_id' => $changelogId,
            'item_count' => $this->countItems($sections),
            'sections' => $this->localizedSections($sections, $this->localeForUser($user)),
        ];
    }

    /**
     * @return array{current_version: string, current_changelog_id: ?string, sections: list<array<string, mixed>>}
     */
    public function page(?User $user = null): array
    {
        return [
            'current_version' => $this->currentVersion(),
            'current_changelog_id' => $this->currentChangelogId(),
            'sections' => $this->localizedSections($this->allVisibleSections(), $this->localeForUser($user)),
        ];
    }

    public function markSeen(User $user): void
    {
        $user->forceFill([
            'last_seen_app_version' => $this->currentVersion(),
            'last_seen_changelog_id' => $this->currentChangelogId(),
        ])->save();
    }

    public function currentChangelogId(): ?string
    {
        if ($this->shouldUseUnreleased()) {
            $sections = $this->unreleasedSections();

            return $sections === [] ? null : $this->changelogIdForSections($sections);
        }

        $currentRelease = $this->matchingReleaseVersion($this->currentVersion());

        return $currentRelease && isset($this->releases()[$currentRelease]) ? $currentRelease : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public function validateData(array $data, ?string $version = null, bool $release = false): array
    {
        $errors = [];
        $unreleased = $data['unreleased'] ?? null;
        $releases = $data['releases'] ?? null;

        if (! is_array($unreleased)) {
            $errors[] = 'The changelog must define an unreleased array.';
            $unreleased = [];
        }

        if (! is_array($releases)) {
            $errors[] = 'The changelog must define a releases array.';
            $releases = [];
        }

        $errors = [
            ...$errors,
            ...$this->validateItems($unreleased, 'unreleased'),
            ...$this->validateReleases($releases),
        ];

        if ($release) {
            if (! $version) {
                $errors[] = 'A release version is required.';
            } elseif (! isset($releases[$version])) {
                $errors[] = "The changelog does not contain a release entry for {$version}.";
            } elseif (empty($releases[$version]['items']) || ! is_array($releases[$version]['items'])) {
                $errors[] = "The release entry for {$version} must contain at least one item.";
            }

            if ($unreleased !== []) {
                $errors[] = 'The unreleased section must be empty before publishing a tag.';
            }
        }

        return $errors;
    }

    /**
     * @param  list<string>  $locales
     * @return list<string>
     */
    public function validateTranslationsForLocales(array $locales, ?array $data = null): array
    {
        $errors = [];
        $keys = $this->itemKeys($data ?? config('changelog'));

        foreach ($locales as $locale) {
            $translations = $this->translations($locale);

            foreach ($keys as $key) {
                $copy = $translations[$key] ?? null;

                if (! is_array($copy)) {
                    $errors[] = "Missing changelog translation for {$key} in {$locale}.";

                    continue;
                }

                foreach (['title', 'description'] as $field) {
                    if (! isset($copy[$field]) || ! is_string($copy[$field]) || trim($copy[$field]) === '') {
                        $errors[] = "Missing changelog {$field} for {$key} in {$locale}.";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sectionsForUser(User $user): array
    {
        if ($this->shouldUseUnreleased()) {
            return $this->unreleasedSections();
        }

        $currentRelease = $this->matchingReleaseVersion($this->currentVersion());

        if (! $currentRelease || ! isset($this->releases()[$currentRelease])) {
            return [];
        }

        if ($user->last_seen_app_version === null) {
            return [$this->releaseSection($currentRelease, $this->releases()[$currentRelease])];
        }

        $lastSeenRelease = $this->matchingReleaseVersion($user->last_seen_app_version);

        if ($lastSeenRelease === $currentRelease) {
            return [];
        }

        if (! $lastSeenRelease || ! isset($this->releases()[$lastSeenRelease])) {
            return [$this->releaseSection($currentRelease, $this->releases()[$currentRelease])];
        }

        return $this->releaseSectionsBetween($lastSeenRelease, $currentRelease);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allVisibleSections(): array
    {
        return [
            ...($this->shouldUseUnreleased() ? $this->unreleasedSections() : []),
            ...array_map(
                fn (string $version): array => $this->releaseSection($version, $this->releases()[$version]),
                $this->sortedReleaseVersions(),
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function unreleasedSections(): array
    {
        $items = $this->unreleasedItems();

        if ($items === []) {
            return [];
        }

        return [[
            'version' => self::UNRELEASED_VERSION,
            'date' => null,
            'url' => null,
            'is_unreleased' => true,
            'items' => $items,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function releaseSectionsBetween(string $lastSeenRelease, string $currentRelease): array
    {
        if (version_compare($this->normalizeVersion($currentRelease), $this->normalizeVersion($lastSeenRelease), '<=')) {
            return [];
        }

        $releases = $this->releases();
        $versions = array_values(array_filter(
            $this->sortedReleaseVersions(),
            fn (string $version): bool => version_compare($this->normalizeVersion($version), $this->normalizeVersion($lastSeenRelease), '>')
                && version_compare($this->normalizeVersion($version), $this->normalizeVersion($currentRelease), '<='),
        ));

        return array_map(fn (string $version): array => $this->releaseSection($version, $releases[$version]), $versions);
    }

    /**
     * @param  array<string, mixed>  $release
     * @return array<string, mixed>
     */
    private function releaseSection(string $version, array $release): array
    {
        return [
            'version' => $version,
            'date' => $release['date'] ?? null,
            'url' => $release['url'] ?? null,
            'is_unreleased' => false,
            'items' => array_values($release['items'] ?? []),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function localizedSections(array $sections, string $locale): array
    {
        return array_map(function (array $section) use ($locale): array {
            $section['items'] = array_map(
                fn (array $item): array => $this->localizedItem($item, $locale),
                $section['items'] ?? [],
            );

            return $section;
        }, $sections);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function localizedItem(array $item, string $locale): array
    {
        $key = (string) ($item['key'] ?? '');
        $copy = $this->translation($key, $locale);

        return [
            ...$item,
            'title' => $copy['title'] ?? $key,
            'description' => $copy['description'] ?? '',
        ];
    }

    /**
     * @return array{title?: string, description?: string}
     */
    private function translation(string $key, string $locale): array
    {
        $translations = $this->translations($locale);
        $fallbackTranslations = $locale === User::DEFAULT_LOCALE ? [] : $this->translations(User::DEFAULT_LOCALE);
        $copy = $translations[$key] ?? $fallbackTranslations[$key] ?? [];

        return is_array($copy) ? $copy : [];
    }

    /**
     * @return array<string, array{title?: string, description?: string}>
     */
    private function translations(string $locale): array
    {
        static $translations = [];

        if (isset($translations[$locale])) {
            return $translations[$locale];
        }

        $path = resource_path("changelog/{$locale}.php");
        $loaded = file_exists($path) ? require $path : [];

        return $translations[$locale] = is_array($loaded) ? $loaded : [];
    }

    private function localeForUser(?User $user): string
    {
        $locale = $user?->locale ?: app()->getLocale();

        return in_array($locale, User::SUPPORTED_LOCALES, true) ? $locale : User::DEFAULT_LOCALE;
    }

    private function shouldUseUnreleased(): bool
    {
        return in_array($this->currentVersion(), ['main', 'dev', 'development'], true)
            || in_array((string) config('app.env'), ['local', 'development'], true);
    }

    private function currentVersion(): string
    {
        return (string) config('app.version', 'main');
    }

    /**
     * @return list<array<string, string>>
     */
    private function unreleasedItems(): array
    {
        return array_values(config('changelog.unreleased', []));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function releases(): array
    {
        return config('changelog.releases', []);
    }

    /**
     * @return list<string>
     */
    private function sortedReleaseVersions(): array
    {
        $versions = array_keys($this->releases());

        usort($versions, function (string $left, string $right): int {
            $comparison = version_compare($this->normalizeVersion($right), $this->normalizeVersion($left));

            return $comparison === 0 ? strcmp($right, $left) : $comparison;
        });

        return $versions;
    }

    private function matchingReleaseVersion(?string $version): ?string
    {
        if (! $version) {
            return null;
        }

        foreach (array_keys($this->releases()) as $releaseVersion) {
            if ($releaseVersion === $version || $this->normalizeVersion($releaseVersion) === $this->normalizeVersion($version)) {
                return $releaseVersion;
            }
        }

        return null;
    }

    private function normalizeVersion(string $version): string
    {
        return preg_replace('/^v/i', '', $version) ?: $version;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     */
    private function changelogIdForSections(array $sections): string
    {
        if (count($sections) === 1 && ($sections[0]['is_unreleased'] ?? false) === true) {
            $encodedItems = json_encode($sections[0]['items']);

            return self::UNRELEASED_VERSION.':'.substr(hash('sha256', $encodedItems ?: ''), 0, 12);
        }

        return (string) $sections[0]['version'];
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     */
    private function countItems(array $sections): int
    {
        return array_reduce($sections, fn (int $count, array $section): int => $count + count($section['items'] ?? []), 0);
    }

    /**
     * @param  array<string, mixed>  $releases
     * @return list<string>
     */
    private function validateReleases(array $releases): array
    {
        $errors = [];

        foreach ($releases as $version => $release) {
            if (! is_string($version) || trim($version) === '') {
                $errors[] = 'Release versions must be non-empty strings.';
            }

            if (! is_array($release)) {
                $errors[] = "Release {$version} must be an array.";

                continue;
            }

            if (isset($release['date']) && ! is_string($release['date'])) {
                $errors[] = "Release {$version} date must be a string.";
            }

            if (isset($release['url']) && ! is_string($release['url'])) {
                $errors[] = "Release {$version} url must be a string.";
            }

            if (! isset($release['items']) || ! is_array($release['items'])) {
                $errors[] = "Release {$version} must contain an items array.";

                continue;
            }

            $errors = [...$errors, ...$this->validateItems($release['items'], "releases.{$version}.items")];
        }

        return $errors;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<string>
     */
    private function validateItems(array $items, string $path): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $errors[] = "{$path}.{$index} must be an array.";

                continue;
            }

            foreach (['type', 'key'] as $key) {
                if (! isset($item[$key]) || ! is_string($item[$key]) || trim($item[$key]) === '') {
                    $errors[] = "{$path}.{$index}.{$key} must be a non-empty string.";
                }
            }

            if (isset($item['type']) && is_string($item['type']) && ! in_array($item['type'], self::ALLOWED_TYPES, true)) {
                $errors[] = "{$path}.{$index}.type must be one of: ".implode(', ', self::ALLOWED_TYPES).'.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function itemKeys(array $data): array
    {
        $keys = [];

        foreach (($data['unreleased'] ?? []) as $item) {
            if (is_array($item) && is_string($item['key'] ?? null)) {
                $keys[] = $item['key'];
            }
        }

        foreach (($data['releases'] ?? []) as $release) {
            foreach (($release['items'] ?? []) as $item) {
                if (is_array($item) && is_string($item['key'] ?? null)) {
                    $keys[] = $item['key'];
                }
            }
        }

        return array_values(array_unique($keys));
    }
}
