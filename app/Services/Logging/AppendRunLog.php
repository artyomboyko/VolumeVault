<?php

namespace App\Services\Logging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AppendRunLog
{
    public function handle(Model $run, ?string $message): void
    {
        $message = trim((string) $message);

        if ($message === '') {
            return;
        }

        // Serialize the read-modify-write so two processes appending to the same
        // run never both read the same value and clobber each other's output —
        // e.g. the queue failed() hook firing while the in-process finally block
        // writes its restart notice. lockForUpdate takes a row lock on MySQL; on
        // SQLite the database-level write lock already serializes writers, so the
        // transaction is portable across both drivers.
        $combined = DB::transaction(function () use ($run, $message): string {
            // Read the freshest value straight from the DB rather than the
            // caller's (possibly stale) in-memory instance.
            $existing = (string) $run->newQuery()
                ->whereKey($run->getKey())
                ->lockForUpdate()
                ->value('logs');

            $combined = $this->cap(trim($existing."\n".$message));

            // Targeted single-column update inside the same locked transaction.
            $run->newQuery()->whereKey($run->getKey())->update(['logs' => $combined]);

            return $combined;
        });

        // Keep the caller's instance in sync so a later save() can't resurrect
        // the pre-append value.
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

        // Cut on character boundaries (mb_strcut respects the byte budget without
        // splitting a multibyte sequence) so the kept tail is always valid UTF-8
        // and never breaks json_encode when the API returns the log.
        $budget = max(0, $max - strlen($marker));
        $tail = mb_strcut($logs, max(0, strlen($logs) - $budget), null, 'UTF-8');

        // Avoid leaving a half line at the top of the kept tail.
        $newlinePos = strpos($tail, "\n");
        if ($newlinePos !== false && $newlinePos < strlen($tail) - 1) {
            $tail = substr($tail, $newlinePos + 1);
        }

        $result = $marker.$tail;

        // When the marker alone is longer than the cap, clamp the result so the
        // configured byte limit is never exceeded (still on a character boundary).
        if (strlen($result) > $max) {
            $result = mb_strcut($result, 0, $max, 'UTF-8');
        }

        return $result;
    }
}
