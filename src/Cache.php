<?php
/**
 * Cache.php
 *
 * @package     Fcache
 * @copyright   Copyright (C) 2023 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Fcache;

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

use function decoct;
use function fclose;
use function feof;
use function fileperms;
use function fopen;
use function fread;
use function fwrite;
use function hash;
use function implode;
use function is_file;
use function serialize;
use function sprintf;
use function unlink;
use function unserialize;

use const DIRECTORY_SEPARATOR;
use const E_USER_WARNING;
use const Fcache\CACHE_DIR;
use const Fcache\DIR_OCTAL;
use const Fcache\MAX_BYTES;
use const Fcache\READ_ONLY;
use const Fcache\WRITE_ONLY;

final class Cache
{
    /** @var mixed[] $cache */
    private array $cache = [];

    /**
     * @param string $cacheDir
     * @param string $fileType
     * @param string $hashAlgo
     * @param int $maxBytes
     * @return void
     */
    public function __construct(
        private readonly string $cacheDir = CACHE_DIR,
        private readonly string $fileType = 'fcache',
        private readonly string $hashAlgo = 'crc32b',
        private readonly int $maxBytes = MAX_BYTES
    ) {
        $this->initialize();
    }

    /**
     * @return void
     */
    private function initialize(): void
    {
        /** @var int $octal */
        $octal = (int) decoct(
            fileperms($this->cacheDir) & 0777
        );

        if ($octal !== DIR_OCTAL) {
            trigger_error(
                sprintf(
                    'Fix permissions for cache directory "%s". ' .
                    'Current permissions: "%s"; Required permissions: "%s"',
                    $this->cacheDir,
                    $octal,
                    DIR_OCTAL
                ),
                E_USER_WARNING
            );
        }

        /** @var RecursiveIteratorIterator $iterator */
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->cacheDir,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var DirectoryIterator $inode */
        foreach ($iterator as $inode) {
            /** @var string $file */
            $file = $inode->getFilename();
            $this->cache[$file] = $this->import($file);
        }
    }

    /**
     * @param string $key
     * @return object|null
     */
    public function get(string $key): ?object
    {
        try {
            /** @var string $file */
            $file = sprintf(
                '%s.%s',
                hash($this->hashAlgo, $key),
                $this->fileType
            );

            /** @var object|null $object */
            $object =& $this->cache[$file];
            $object ??= $this->import($file);
            return $object;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param string $key
     * @param object|null $value
     * @return static
     */
    public function set(
        string $key,
        ?object $value = null
    ): static {
        try {
            /** @var string $file */
            $file = sprintf(
                '%s.%s',
                hash($this->hashAlgo, $key),
                $this->fileType
            );

            $this->export($file, $value);
            $this->cache[$file] = $value;
        } finally {
            return $this;
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function purge(string $key): bool
    {
        /** @var string $file */
        $file = sprintf(
            '%s.%s',
            hash($this->hashAlgo, $key),
            $this->fileType
        );

        if (isset($this->cache[$file])) {
            $this->delete($file);
        }

        return true;
    }

    /**
     * @param string $file
     * @param object|null $value
     * @return string
     * @throws RuntimeException
     */
    private function export(
        string $file,
        ?object $value
    ): string {
        /** @var string $filePath */
        $filePath = implode(
            DIRECTORY_SEPARATOR,
            [
                $this->cacheDir,
                $file,
            ]
        );

        /** @var resource|false $handle */
        $handle = fopen($filePath, WRITE_ONLY);

        if ($handle === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to create object file "%s"',
                    $filePath
                )
            );
        }

        /** @var string|null $export */
        $export = serialize($value);

        /** @var int|false $result */
        $result = fwrite($handle, $export);

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to export object file "%s"',
                    $filePath
                )
            );
        }

        fclose($handle);
        return $export;
    }

    /**
     * @param string $key
     * @return object|null
     * @throws RuntimeException
     */
    private function import(string $file): ?object
    {
        /** @var string $filePath */
        $filePath = implode(
            DIRECTORY_SEPARATOR,
            [
                $this->cacheDir,
                $file,
            ]
        );

        if (!is_file($filePath)) {
            return null;
        }

        /** @var resource|false $handle */
        $handle = fopen($filePath, READ_ONLY);

        if ($handle === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to import object file "%s"',
                    $filePath
                )
            );
        }

        /** @var string $result */
        $result = '';

        while (!feof($handle)) {
            /** @var string|false $data */
            $data = fread($handle, $this->maxBytes);

            if ($data === false) {
                break;
            }

            $result .= $data;
        }

        fclose($handle);
        return unserialize($result);
    }

    /**
     * @param string $file
     * @return bool
     */
    private function delete(string $file): bool
    {
        /** @var string $filePath */
        $filePath = implode(
            DIRECTORY_SEPARATOR,
            [
                $this->cacheDir,
                $file,
            ]
        );
        return unlink($filePath);
    }
}
