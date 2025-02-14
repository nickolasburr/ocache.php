<?php
/**
 * functions.php
 *
 * @package     Ocache\Index
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Index;

use Ocache\Cache\Config;

use function Ocache\Cache\config;

/**
 * @param Config|null $config
 * @return PathResolver
 */
function pathResolver(?Config $config = null): PathResolver {
    static $pathResolvers;
    $pathResolvers ??= [];
    $config ??= config();

    /** @var string $index */
    $index = $config->getIndexKey();

    /** @var HashProvider|null $pathResolver */
    $pathResolver =& $pathResolvers[$index];
    $pathResolver ??= new PathResolver($config);
    return $pathResolver;
}
