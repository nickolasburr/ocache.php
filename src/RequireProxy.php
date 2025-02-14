<?php
/**
 * RequireProxy.php
 *
 * @package     Ocache
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache;

use Ocache\Cache\Config;
use Ocache\Index\PathResolver;

use function clearstatcache;
use function is_file;

use const REQUIRE_PROXY_PATH;

final readonly class RequireProxy
{
    /** @var PathResolver $pathResolver */
    private PathResolver $pathResolver;

    /**
     * @param Config $config
     * @return void
     */
    public function __construct(
        private Config $config
    ) {
        $this->pathResolver = new PathResolver($config);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        /** @var string $path */
        $path = $this->pathResolver->resolve($key);

        /** @var bool $exists */
        $exists = is_file($path);

        if ($exists) {
            clearstatcache(true, $path);
        }

        return $exists;
    }

    /**
     * @param string $key
     * @return object|null
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function require(string $key): ?object
    {
        try {
            /** @var string $path */
            $path = $this->pathResolver->resolve($key);
            return (static function () use ($path) {
                return require REQUIRE_PROXY_PATH;
            })();
        } catch (Throwable) {
            return null;
        }
    }
}
