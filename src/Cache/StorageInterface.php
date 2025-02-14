<?php
/**
 * StorageInterface.php
 *
 * @package     Ocache\Cache
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Cache;

use RuntimeException;

interface StorageInterface
{
    /**
     * @return bool
     * @throws RuntimeException
     */
    public function init(): bool;

    /**
     * @param string $file
     * @param int|null $mtime
     * @param int|null $atime
     * @return bool
     */
    public function touch(
        string $file,
        ?int $mtime,
        ?int $atime
    ): bool;

    /**
     * @param string $file
     * @return bool
     */
    public function unlink(string $file): bool;
}
