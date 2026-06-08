<?php

namespace App\Services\Logging;

use Illuminate\Database\Eloquent\Model;

class AppendRunLog
{
    public function handle(Model $run, ?string $message): void
    {
        $message = trim((string) $message);

        if ($message === '') {
            return;
        }

        // Read the freshest value straight from the DB rather than the caller's
        // (possibly stale) in-memory instance, so an append never silently drops
        // output another instance already wrote — e.g. the catch block's error
        // message vs. the finally block's restart notice.
        $existing = (string) $run->newQuery()->whereKey($run->getKey())->value('logs');
        $combined = $this->cap(trim($existing."\n".$message));

        // Targeted single-column update; keep the caller's instance in sync so a
        // later save() can't resurrect the pre-append value.
        $run->newQuery()->whereKey($run->getKey())->update(['logs' => $combined]);
        $run->forceFill(['logs' => $combined])->syncOriginalAttribute('logs');
    }

    /**
     * Keep the log under the configured byte cap, preserving the most recent
     * output (errors usually surface last) and dropping the oldest lines.
     */
    private function cap(string $logs): string
    {
        $max = (int) config('volumevault.run_logs.max_bytes', 262144);

        if ($max <= 0 || strlen($logs) <= $max) {
            return $logs;
        }

        $marker = "[... earlier log output truncated ...]\n";
        $tail = substr($logs, -max(0, $max - strlen($marker)));

        // Avoid leaving a half line at the top of the kept tail.
        $newlinePos = strpos($tail, "\n");
        if ($newlinePos !== false && $newlinePos < strlen($tail) - 1) {
            $tail = substr($tail, $newlinePos + 1);
        }

        return $marker.$tail;
    }
}
