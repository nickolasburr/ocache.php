<?php
/**
 * functions.php
 *
 * @package     Ocache\Cache
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Cache;

use Ocache\Cache\Storage\File;

const CACHE_DIR = '/tmp/.ocache';
const DEF_INDEX = 'def';
const HASH_ALGO = 'crc32b';

/**
 * @param string $cacheDir
 * @param string $indexKey
 * @param string $hashAlgo
 * @return Config
 */
function config(
    string $cacheDir = CACHE_DIR,
    string $indexKey = DEF_INDEX,
    string $hashAlgo = HASH_ALGO
): Config {
    static $configs;
    $configs ??= [];

    /** @var Config|null $config */
    $config =& $configs[$indexKey];
    $config ??= new Config(
        $cacheDir,
        $indexKey,
        $hashAlgo
    );
    return $config;
}

/**
 * @param Config|null $config
 * @return StorageInterface
 */
function storage(?Config $config = null): StorageInterface {
    static $storages;
    $storages ??= [];
    $config ??= config();

    /** @var string $index */
    $index = $config->getIndexKey();

    /** @var StorageInterface|null $storage */
    $storage =& $storages[$index];
    $storage ??= new File($config);
    return $storage;
}
