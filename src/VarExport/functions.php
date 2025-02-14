<?php
/**
 * functions.php
 *
 * @package     Ocache\VarExport
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\VarExport;

use Ocache\Cache\Config;

use function Ocache\Cache\config;

/**
 * @param Config|null $config
 * @return ObjectExporter
 */
function objectExporter(?Config $config = null): ObjectExporter {
    static $objectExporters;
    $objectExporters ??= [];
    $config ??= config();

    /** @var string $index */
    $index = $config->getIndexKey();

    $objectExporter =& $objectExporters[$index];
    $objectExporter ??= new ObjectExporter($config);
    return $objectExporter;
}
