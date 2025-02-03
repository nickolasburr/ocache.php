<?php
/**
 * functions.php
 *
 * @package     Ocache\Stream
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Stream;

const PROTO_VFS = 'vfs';

/**
 * @param string $name
 * @param string|object $type
 * @return FilterRegistry
 */
function filterRegistry(
    string $name = Filter::FILTER_NAME,
    string|object $type = Filter::class
): FilterRegistry {
    static $registries;
    $registries ??= [];

    /** @var FilterRegistry|null $registry */
    $registry =& $registries[$name];
    $registry ??= (new FilterRegistry())->register($name, $type);
    return $registry;
}
