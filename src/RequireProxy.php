<?php
/**
 * RequireProxy.php
 *
 * @package     Ocache
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache;

use Closure;
use Ocache\Cache\Config;
use Ocache\Index\PathResolver;
use Throwable;

use function clearstatcache;
use function is_file;
use function restore_error_handler;
use function set_error_handler;
use function Ocache\Index\pathResolver;

use const REQUIRE_PROXY_PATH;

final readonly class RequireProxy
{
    /** @var Closure|null $errorHandler */
    private ?Closure $errorHandler;

    /** @var PathResolver $pathResolver */
    private PathResolver $pathResolver;

    /**
     * @param Config $config
     * @param callable|null $errorHandler
     * @return void
     */
    public function __construct(
        private Config $config,
        ?callable $errorHandler = null
    ) {
        $this->pathResolver = pathResolver($config);
        $this->errorHandler = $errorHandler !== null
            ? (
                !$errorHandler instanceof Closure
                    ? $errorHandler(...) : $errorHandler
            ) : null;
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
            set_error_handler($this->onError(...));

            /** @var string $path */
            $path = $this->pathResolver->resolve($key);
            return (static function () use ($path) {
                return require REQUIRE_PROXY_PATH;
            })();
        } catch (Throwable) {
            return null;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param string|null $errfile
     * @param int|null $errline
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function onError(
        int $errno,
        string $errstr,
        ?string $errfile = null,
        ?int $errline = null
    ): bool {
        /** @var bool|null $result */
        $result = $this->errorHandler
            ? $this->errorHandler(
                $errno,
                $errstr,
                $errfile,
                $errline
            ) : false;
        return (bool) $result;
    }
}
