<?php
/**
 * Cache.php
 *
 * @package     Ocache
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache;

use DateInterval;
use Ocache\Exception\InvalidArgumentException;
use Ocache\Stream\Filter;
use Ocache\Stream\FilterRegistry;
use Psr\SimpleCache\CacheInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\VarExporter\VarExporter;
use Throwable;

use function array_keys;
use function decoct;
use function fclose;
use function fileperms;
use function fopen;
use function fwrite;
use function hash;
use function implode;
use function is_file;
use function is_object;
use function mb_strlen;
use function mb_substr;
use function sprintf;
use function unlink;
use function Ocache\Stream\filterRegistry;

use const DIRECTORY_SEPARATOR;
use const E_USER_WARNING;
use const STREAM_FILTER_WRITE;
use const Ocache\Exception\E_TYPE;

final class Cache implements CacheInterface
{
    private const TMPL = '%s.%s';

    /** @var mixed[] $cache */
    private array $cache = [];

    /** @var FilterRegistry $filterRegistry */
    private readonly FilterRegistry $filterRegistry;

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
        $this->filterRegistry = filterRegistry();
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

        /** @var SplFileInfo $node */
        foreach ($iterator as $node) {
            /** @var string $file */
            $file = $node->getFilename();
            $this->cache[$file] = $this->import($file);
        }
    }

    /**
     * {@inheritdoc}
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
                self::TMPL,
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
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        /** @var string $file */
        $file = sprintf(
            self::TMPL,
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
     * {@inheritdoc}
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
                self::TMPL,
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
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        /** @var string $file */
        $file = sprintf(
            self::TMPL,
            hash($this->hashAlgo, $key),
            $this->fileType
        );

        if (isset($this->cache[$file])) {
            $this->unlink($file);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        /** @var string[] $files */
        $files = array_keys($this->cache);

        /** @var string $file */
        foreach ($files as $file) {
            $this->unlink($file);
        }

        $this->cache = [];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(
        iterable $keys,
        $default = null
    ): iterable {
        if ($default !== null && !is_object($default)) {
            throw new InvalidArgumentException(E_TYPE);
        }

        /** @var mixed[] $result */
        $result = [];

        /** @var string $key */
        foreach ($keys as $key) {
            $result[] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setMultiple(
        iterable $values,
        int|DateInterval|null $ttl = null
    ): bool {
        /** @var string $key */
        /** @var mixed $value */
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        /** @var string $key */
        foreach ($keys as $key) {
            $this->delete($key);
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
        $handle = fopen($filePath, WRITE_BINARY);

        if ($handle === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to create object file "%s"',
                    $filePath
                )
            );
        }

        $this->filterRegistry->append(
            Filter::FILTER_NAME,
            $handle,
            STREAM_FILTER_WRITE
        );

        /** @var string|null $export */
        $export = $this->getVarExport($value);

        /** @var int $index */
        $index = 0;

        /** @var int $length */
        $length = mb_strlen($export);

        do {
            /** @var int|false $result */
            $result = fwrite(
                $handle,
                mb_substr(
                    $export,
                    $index
                )
            );

            if ($result === false) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to export object file "%s"',
                        $filePath
                    )
                );
            }

            $index += $result;
        } while ($index < $length);

        $this->filterRegistry->remove(
            Filter::FILTER_NAME,
            $handle
        );
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
    private function unlink(string $file): bool
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
