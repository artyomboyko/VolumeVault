<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesBackupSizeRange
{
    /**
     * Ensure each item's backup_size_out_of_range min is not greater than its max.
     *
     * @param  iterable<int, array<string, mixed>>  $items  Config-bearing items keyed by index.
     * @param  callable(int): string  $errorKey  Builds the error key for a given index.
     */
    protected function validateBackupSizeRanges(Validator $validator, iterable $items, callable $errorKey): void
    {
        foreach ($items as $index => $item) {
            $config = $item['config'] ?? [];

            if (! array_key_exists('backup_size_out_of_range_min_bytes', $config) || ! array_key_exists('backup_size_out_of_range_max_bytes', $config)) {
                continue;
            }

            $min = $config['backup_size_out_of_range_min_bytes'];
            $max = $config['backup_size_out_of_range_max_bytes'];

            if ($min === null || $min === '' || $max === null || $max === '') {
                continue;
            }

            if ((int) $min > (int) $max) {
                $validator->errors()->add($errorKey($index), 'The maximum backup size must be greater than or equal to the minimum backup size.');
            }
        }
    }
}
