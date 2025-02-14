<?php
/**
 * File.php
 *
 * @package     Ocache\Cache\Storage
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Cache\Storage;

use Ocache\Cache\Config;
use Ocache\Cache\StorageInterface;
use Ocache\Index\PathBuilder;
use Ocache\Index\PathResolver;
use Ocache\Utils\StringUtils;
use RuntimeException;

use function decoct;
use function fileperms;
use function is_dir;
use function is_file;
use function mkdir;
use function touch;
use function unlink;
use function Ocache\Index\pathResolver;

use const E_USER_WARNING;
use const Ocache\DIR_OCTAL;
use const Ocache\LAX_OCTAL;

class File implements StorageInterface
{
    /** @var PathBuilder $pathBuilder */
    private readonly PathBuilder $pathBuilder;

    /** @var PathResolver $pathResolver */
    private readonly PathResolver $pathResolver;

    /** @var StringUtils $stringUtils */
    private readonly StringUtils $stringUtils;

    /**
     * @param Config $config
     * @return void
     */
    public function __construct(
        private readonly Config $config
    ) {
        $this->pathBuilder = new PathBuilder();
        $this->pathResolver = pathResolver($config);
        $this->stringUtils = new StringUtils();
    }

    /**
     * {@inheritdoc}
     */
    public function init(): bool
    {
        /** @var string $cacheDir */
        $cacheDir = $this->config->getCacheDir();

        if (!is_dir($cacheDir) && !mkdir($cacheDir, DIR_OCTAL)) {
            throw new RuntimeException(
                $this->stringUtils->sprintf(
                    'Unable to create cache storage directory "%s"',
                    $cacheDir
                )
            );
        }

        /** @var int $perms */
        $perms = fileperms($cacheDir) & LAX_OCTAL;

        if ($perms !== DIR_OCTAL) {
            trigger_error(
                $this->stringUtils->sprintf(
                    'Fix permissions for cache storage directory "%s". ' .
                    'Current permissions: "%s"; Required permissions: "%s"',
                    $cacheDir,
                    decoct($perms),
                    decoct(DIR_OCTAL)
                ),
                E_USER_WARNING
            );
        }

        /** @var string $indexDir */
        $indexDir = $this->pathBuilder->build(
            $cacheDir,
            $this->config->getIndexKey()
        );

        if (!is_dir($indexDir) && !mkdir($indexDir, DIR_OCTAL)) {
            throw new RuntimeException(
                $this->stringUtils->sprintf(
                    'Unable to create cache index directory "%s"',
                    $indexDir
                )
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function touch(
        string $file,
        ?int $mtime = null,
        ?int $atime = null
    ): bool {
        /** @var string $path */
        $path = $this->pathResolver->resolve($file);
        return is_file($path)
            ? touch(
                $path,
                $mtime,
                $atime
            ) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(string $file): bool
    {
        /** @var string $path */
        $path = $this->pathResolver->resolve($file);
        return is_file($path) ? unlink($path) : false;
    }
}
