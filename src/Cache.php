<?php
/**
 * Cache.php
 *
 * @package     VfsCache
 * @copyright   Copyright (C) 2023 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace VfsCache;

use DateInterval;
use DirectoryIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\VarExporter\VarExporter;
use Throwable;

use function decoct;
use function fclose;
use function fileperms;
use function fopen;
use function fwrite;
use function hash;
use function implode;
use function is_file;
use function is_object;
use function sprintf;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const E_USER_WARNING;
use const VfsCache\CACHE_DIR;
use const VfsCache\DIR_OCTAL;
use const VfsCache\WRITE_ONLY;
use const VfsCache\Exception\E_TYPE;

final class Cache
{
    /** @var mixed[] $cache */
    private array $cache = [];

    /**
     * @param string $cacheDir
     * @param string $fileType
     * @param string $hashAlgo
     * @return void
     */
    public function __construct(
        private readonly string $cacheDir = CACHE_DIR,
        private readonly string $fileType = 'php',
        private readonly string $hashAlgo = 'crc32b'
    ) {
        $this->initialize();
    }

    /**
     * @return void
     */
    private function initialize(): void
    {
        /** @var int $perms */
        $perms = fileperms($this->cacheDir) & 0o777;

        if ($perms !== DIR_OCTAL) {
            trigger_error(
                sprintf(
                    'Fix permissions for VFS cache directory "%s". ' .
                    'Current permissions: "%s"; Required permissions: "%s"',
                    $this->cacheDir,
                    decoct($perms),
                    decoct(DIR_OCTAL)
                ),
                E_USER_WARNING
            );
        }

        /** @var RecursiveIteratorIterator $iterator */
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->cacheDir,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
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
     * @param object|null $default
     * @return object|null
     * @throws InvalidArgumentException
     */
    public function get(
        string $key,
        $default = null
    ): ?object {
        if ($default !== null && !is_object($default)) {
            throw new InvalidArgumentException(E_TYPE);
        }

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
            return $default;
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        /** @var string $file */
        $file = sprintf(
            '%s.%s',
            hash($this->hashAlgo, $key),
            $this->fileType
        );

        /** @var string $filePath */
        $filePath = implode(
            DIRECTORY_SEPARATOR,
            [
                $this->cacheDir,
                $file,
            ]
        );
        return is_file($filePath);
    }

    /**
     * @param string $key
     * @param object|null $value
     * @param int|DateInterval|null $ttl
     * @return bool
     * @throws InvalidArgumentException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function set(
        string $key,
        $value = null,
        int|DateInterval|null $ttl = null
    ): bool {
        if ($value !== null && !is_object($value)) {
            throw new InvalidArgumentException(E_TYPE);
        }

        try {
            /** @var string $file */
            $file = sprintf(
                '%s.%s',
                hash($this->hashAlgo, $key),
                $this->fileType
            );
            $this->export($file, $value);
            $this->cache[$file] = $value;
            return true;
        } catch (Throwable) {
            return false;
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
        $export = $this->getVarExport($value);

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
        return is_file($filePath)
            ? include $filePath : null;
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

    /**
     * @param mixed $value
     * @return string
     */
    private function getVarExport(mixed $value): string
    {
        /** @var string $export */
        $export = VarExporter::export($value);
        return <<<EOS
<?php return $export;
EOS;
    }
}
