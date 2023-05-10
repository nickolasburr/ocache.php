<?php
/**
 * functions.php
 *
 * @package     VfsCache
 * @copyright   Copyright (C) 2023 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace VfsCache;

use function is_dir;
use function mkdir;

const CACHE_DIR = '/tmp/.vfscache';
const DIR_OCTAL = 0o700;
const READ_ONLY = 'r';
const WRITE_ONLY = 'w';

/**
 * @param string $cacheDir
 * @return Cache
 */
function cache(string $cacheDir = CACHE_DIR): Cache {
    static $caches;
    $caches ??= [];

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, DIR_OCTAL);
    }

    /** @var Cache|null $cache */
    $cache =& $caches[$cacheDir];
    $cache ??= new Cache($cacheDir);
    return $cache;
}
