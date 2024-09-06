<?php
/**
 * functions.php
 *
 * @package     Ocache
 * @copyright   Copyright (C) 2024 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache;

use function is_dir;
use function mkdir;

const CACHE_DIR = '/tmp/.ocache';
const DIR_OCTAL = 0o700;
const MAX_BYTES = 1024;
const READ_ONLY = 'r';
const WRITE_BINARY = 'wb';
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
