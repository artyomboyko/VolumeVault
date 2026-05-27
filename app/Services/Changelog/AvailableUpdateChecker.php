<?php

namespace App\Services\Changelog;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class AvailableUpdateChecker
{
    /**
     * @return array{version: string, url: string, published_at: ?string, body_excerpt: ?string}|null
     */
    public function forUser(User $user): ?array
    {
        $release = $this->latestRelease();

        if (! $release) {
            return null;
        }

        if ($user->last_dismissed_available_version === $release['version']) {
            return null;
        }

        return $release;
    }

    public function dismissForUser(User $user): void
    {
        $release = $this->latestRelease();

        if (! $release) {
            return;
        }

        $user->forceFill([
            'last_dismissed_available_version' => $release['version'],
        ])->save();
    }

    /**
     * @return array{version: string, url: string, published_at: ?string, body_excerpt: ?string}|null
     */
    public function latestRelease(): ?array
    {
        if (! $this->enabled() || ! $this->shouldCheckCurrentVersion()) {
            return null;
        }

        $cached = Cache::remember(
            'volumevault.available_update.latest_release',
            $this->cacheTtlSeconds(),
            fn (): array => ['release' => $this->fetchLatestRelease()],
        );
        $release = is_array($cached) && is_array($cached['release'] ?? null) ? $cached['release'] : null;

        if (! $release || version_compare($this->normalizeVersion($release['version']), $this->normalizeVersion($this->currentVersion()), '<=')) {
            return null;
        }

        return $release;
    }

    /**
     * @return array{version: string, url: string, published_at: ?string, body_excerpt: ?string}|null
     */
    private function fetchLatestRelease(): ?array
    {
        try {
            $response = Http::acceptJson()
                ->withUserAgent('VolumeVault update checker')
                ->timeout(3)
                ->get($this->githubApiUrl());
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data) || ! is_string($data['tag_name'] ?? null) || ! is_string($data['html_url'] ?? null)) {
            return null;
        }

        return [
            'version' => $data['tag_name'],
            'url' => $data['html_url'],
            'published_at' => is_string($data['published_at'] ?? null) ? $data['published_at'] : null,
            'body_excerpt' => $this->excerpt(is_string($data['body'] ?? null) ? $data['body'] : null),
        ];
    }

    private function enabled(): bool
    {
        return (bool) config('volumevault.update_check.enabled', true);
    }

    private function shouldCheckCurrentVersion(): bool
    {
        return ! in_array($this->currentVersion(), ['main', 'dev', 'development'], true)
            && str_starts_with($this->currentVersion(), 'v');
    }

    private function currentVersion(): string
    {
        return (string) config('app.version', 'main');
    }

    private function githubApiUrl(): string
    {
        return (string) config('volumevault.update_check.github_api_url');
    }

    private function cacheTtlSeconds(): int
    {
        return max(60, (int) config('volumevault.update_check.cache_ttl_seconds', 43200));
    }

    private function normalizeVersion(string $version): string
    {
        return preg_replace('/^v/i', '', $version) ?: $version;
    }

    private function excerpt(?string $body): ?string
    {
        if (! $body) {
            return null;
        }

        $text = trim(strip_tags($body));

        return str($text)->replaceMatches('/\s+/', ' ')->limit(220)->toString();
    }
}
