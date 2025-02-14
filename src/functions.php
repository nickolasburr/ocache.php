<?php
/**
 * functions.php
 *
 * @package     Ocache
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache;

use Ocache\Cache\Config;
use Psr\SimpleCache\CacheInterface;

use function Ocache\Cache\config;

const DIR_OCTAL = 0o700;
const LAX_OCTAL = 0o777;
const MAX_BYTES = 1024;
const READ_O = 'r';
const WRIT_B = 'wb';
const WRIT_O = 'w';

/**
 * @param Config|null $config
 * @return CacheInterface
 */
function cache(?Config $config = null): CacheInterface {
    static $caches;
    $caches ??= [];
    $config ??= config();

    /** @var string $index */
    $index = $config->getIndexKey();

    /** @var Cache|null $cache */
    $cache =& $caches[$index];
    $cache ??= new Cache($config);
    return $cache;
}

/**
 * @param Config|null $config
 * @return RequireProxy
 */
function requireProxy(?Config $config = null): RequireProxy {
    static $proxies;
    $proxies ??= [];
    $config ??= config();

    /** @var string $index */
    $index = $config->getIndexKey();

    /** @var RequireProxy|null $proxy */
    $proxy =& $proxies[$index];
    $proxy ??= new RequireProxy($config);
    return $proxy;
}
