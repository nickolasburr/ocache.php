<?php
/**
 * functions.php
 *
 * @package     Fcache
 * @copyright   Copyright (C) 2023 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Fcache;

use function is_dir;
use function mkdir;

const CACHE_DIR = '/tmp/.fcache';
const DIR_OCTAL = 0o700;
const MAX_BYTES = 1024;
const READ_ONLY = 'r';
const WRITE_ONLY = 'w';

/**
 * @param string $cacheDir
 * @return Cache
 */
function cache(string $cacheDir = CACHE_DIR): Cache {
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, DIR_OCTAL);
    }

    return new Cache($cacheDir);
}
