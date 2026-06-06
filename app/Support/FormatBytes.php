<?php

namespace App\Support;

class FormatBytes
{
    public static function format(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $index), 1).' '.$units[$index];
    }

    public static function formatSigned(int $bytes): string
    {
        $prefix = $bytes > 0 ? '+' : ($bytes < 0 ? '-' : '');

        return $prefix.self::format(abs($bytes));
    }
}
