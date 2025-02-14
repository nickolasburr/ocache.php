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
use Ocache\Cache\Config;
use Ocache\Cache\StorageInterface;
use Ocache\Exception\InvalidArgumentException;
use Ocache\Utils\HashProvider;
use Ocache\VarExport\ObjectExporter;
use Psr\SimpleCache\CacheInterface;
use Throwable;

use function array_keys;
use function is_object;
use function Ocache\Cache\storage;
use function Ocache\Utils\hashProvider;
use function Ocache\VarExport\objectExporter;

use const Ocache\Exception\E_TYPE;

final class Cache implements CacheInterface
{
    /** @var mixed[] $cache */
    private array $cache = [];

    /** @var HashProvider $hashProvider */
    private readonly HashProvider $hashProvider;

    /** @var ObjectExporter $objectExporter */
    private readonly ObjectExporter $objectExporter;

    /** @var RequireProxy $requireProxy */
    private readonly RequireProxy $requireProxy;

    /** @var StorageInterface $storage */
    private readonly StorageInterface $storage;

    /**
     * @param Config $config
     * @return void
     */
    public function __construct(
        private readonly Config $config
    ) {
        $this->requireProxy = requireProxy($config);
        $this->objectExporter = objectExporter($config);
        $this->hashProvider = hashProvider($config);
        $this->storage = storage($config);
        $this->storage->init();
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
            /** @var string $hash */
            $hash = $this->hashProvider->hash($key);

            /** @var object|null $object */
            $object =& $this->cache[$hash];
            $object ??= $this->requireProxy->require($hash);
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
        /** @var string $hash */
        $hash = $this->hashProvider->hash($key);

        /** @var object|null $object */
        $object = $this->cache[$hash] ?? null;
        return $object !== null
            && $this->requireProxy->exists($hash);
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
            /** @var string $hash */
            $hash = $this->hashProvider->hash($key);
            $this->cache[$hash] = $value;
            return $this->objectExporter->export(
                $hash,
                $value
            );
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        /** @var string $hash */
        $hash = $this->hashProvider->hash($key);

        if (isset($this->cache[$hash])) {
            unset($this->cache[$hash]);
            $this->storage->unlink($hash);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        /** @var string[] $keys */
        $keys = array_keys($this->cache);

        /** @var string $key */
        foreach ($keys as $key) {
            /** @var string $hash */
            $hash = $this->hashProvider->hash($key);
            $this->storage->unlink($hash);
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
        /** @var string $index */
        /** @var mixed $value */
        foreach ($values as $index => $value) {
            $this->set($index, $value);
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
}
