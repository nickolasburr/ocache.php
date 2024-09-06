<?php
/**
 * FilterRegistry.php
 *
 * @package     Ocache\Stream
 * @copyright   Copyright (C) 2024 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Stream;

use Ocache\Exception\StreamFilterException;

use function array_keys;
use function array_map;
use function get_resource_id;
use function is_array;
use function is_object;
use function sprintf;
use function stream_filter_append;
use function stream_filter_register;
use function stream_filter_remove;

use const STREAM_FILTER_ALL;

class FilterRegistry
{
    /** @var string[] $filters */
    private array $filters = [];

    /** @var mixed[] $streams */
    private array $streams = [];

    /**
     * @return void
     */
    public function __destruct()
    {
        /** @var string $filter */
        foreach ($this->filters as $filter) {
            $this->remove($filter);
        }
    }

    /**
     * @param string $name
     * @param string|object $type
     * @return static
     * @throws StreamFilterException
     */
    public function register(
        string $name,
        string|object $type
    ): static {
        /** @var string|null $filter */
        $filter =& $this->filters[$name];

        if (isset($filter)) {
            throw new StreamFilterException(
                sprintf(
                    'Unable to register stream filter "%s"; ' .
                    'Filter is already registered with "%s"',
                    $name,
                    $filter
                )
            );
        }

        $filter = !is_object($type)
            ? $type : $type::class;

        if (!stream_filter_register($name, $filter)) {
            throw new StreamFilterException(
                sprintf(
                    'Unable to register stream filter "%s"; ' .
                    'Call to stream_filter_register() failed ' .
                    'when attempting to register class "%s"',
                    $name,
                    $filter
                )
            );
        }

        return $this;
    }

    /**
     * @param string $filter
     * @param resource|resource[] $resource
     * @param int $mode
     * @return static
     * @throws StreamFilterException
     */
    public function append(
        string $filter,
        $resource,
        int $mode = STREAM_FILTER_ALL
    ): static {
        if (!isset($this->filters[$filter])) {
            throw new StreamFilterException(
                sprintf(
                    'Unable to append stream filter "%s" to resource; ' .
                    'Stream filter is not registered. Call register() ' .
                    'to add the stream filter to the filter registry',
                    $filter
                )
            );
        }

        /** @var resource[]|null $streams */
        $streams =& $this->streams[$filter];
        $streams ??= [];

        if (!is_array($resource)) {
            $resource = [$resource];
        }

        /** @var resource $handle */
        foreach ($resource as $handle) {
            /** @var resource|false $stream */
            $stream = stream_filter_append($handle, $filter);

            if ($stream === false) {
                throw new StreamFilterException();
            }

            /** @var int $rid */
            $rid = get_resource_id($handle);
            $streams[$rid] = $stream;
        }

        return $this;
    }

    /**
     * @param string $filter
     * @param resource|resource[]|null $resource
     * @return static
     */
    public function remove(
        string $filter,
        $resource = null
    ): static {
        /** @var resource[]|null $streams */
        $streams =& $this->streams[$filter];
        $streams ??= [];

        /** @var int[] $rids */
        $rids = $resource !== null
            ? array_map(
                get_resource_id(...),
                !is_array($resource) ? [$resource] : $resource
            ) : array_keys($streams);

        /** @var int $rid */
        foreach ($rids as $rid) {
            /** @var resource $stream */
            $stream =& $streams[$rid];
            stream_filter_remove($stream);
            unset($stream);
        }

        return $this;
    }
}
