<?php
/**
 * Config.php
 *
 * @package     Ocache\Cache
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Cache;

readonly class Config
{
    /**
     * @param string $cacheDir
     * @param string $indexKey
     * @param string $hashAlgo
     * @return void
     */
    public function __construct(
        private string $cacheDir,
        private string $indexKey,
        private string $hashAlgo
    ) {}

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * @return string
     */
    public function getIndexKey(): string
    {
        return $this->indexKey;
    }

    /**
     * @return string
     */
    public function getHashAlgo(): string
    {
        return $this->hashAlgo;
    }
}
