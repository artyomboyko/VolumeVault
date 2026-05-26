<?php

return [
    'host_path_allowlist' => array_values(array_filter(array_map(
        fn (string $path): string => trim($path),
        explode(',', env('VOLUMEVAULT_HOST_PATH_ALLOWLIST', ''))
    ))),
];
