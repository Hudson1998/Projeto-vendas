<?php

if (! function_exists('asset_v')) {
    function asset_v(string $path): string
    {
        $fullPath = public_path($path);

        $version = file_exists($fullPath) ? filemtime($fullPath) : time();

        return asset($path).'?v='.$version;
    }
}
