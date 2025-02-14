<?php
/**
 * functions.php
 *
 * @package     Ocache\Utils
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Utils;

use Ocache\Cache\Config;

use function Ocache\Cache\config;

/**
 * @param Config|null $config
 * @return HashProvider
 */
function hashProvider(?Config $config = null): HashProvider {
    static $hashProviders;
    $hashProviders ??= [];
    $config ??= config();

    /** @var string $index */
    $index = $config->getIndexKey();

    /** @var HashProvider|null $hashProvider */
    $hashProvider =& $hashProviders[$index];
    $hashProvider ??= new HashProvider($config->getHashAlgo());
    return $hashProvider;
}
