<?php
/**
 * StringUtils.php
 *
 * @package     Ocache\Utils
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Utils;

use InvalidArgumentException;
use Throwable;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_iterable;
use function is_scalar;
use function is_string;
use function str_pad;
use function str_replace;
use function strlen;
use function strval;
use function vsprintf;

use const STR_PAD_BOTH;

readonly class StringUtils
{
    public const LTRIM = 0x10;
    public const RTRIM = 0x20;
    public const TRIM = self::LTRIM | self::RTRIM;
    public const FUNC = [
        self::LTRIM => 'ltrim',
        self::RTRIM => 'rtrim',
        self::TRIM => 'trim',
    ];

    /**
     * @param array $pieces
     * @param string $delimiter
     * @param bool $filter
     * @return string
     */
    public function concat(
        array $pieces,
        string $delimiter = '',
        bool $filter = false
    ): string {
        if ($filter) {
            $pieces = array_filter(
                $pieces,
                strlen(...)
            );
        }

        return implode($delimiter, $pieces);
    }

    /**
     * @param array $pieces
     * @param callable|null $callback
     * @param bool $preserveKeys
     * @return array
     */
    public function filter(
        array $pieces,
        ?callable $callback = null,
        bool $preserveKeys = false
    ): array {
        $callback ??= strlen(...);

        /** @var array $result */
        $result = array_filter($pieces, $callback);
        return $preserveKeys
            ? $result : array_values($result);
    }

    /**
     * @param string|int|bool $value
     * @param int|null $length
     * @param string $padding
     * @param int $padType
     * @return string
     */
    public function pad(
        string|int|bool $value,
        ?int $length = null,
        string $padding = ' ',
        int $padType = STR_PAD_BOTH
    ): string {
        if (!is_string($value)) {
            $value = (string) $value;
        }

        $length ??= (
            strlen($value) + ($padType ?: 1)
        );
        return str_pad(
            $value,
            $length,
            $padding,
            $padType
        );
    }

    /**
     * @param string|iterable $search
     * @param string|iterable $replace
     * @param mixed[] $subject
     * @return string|array
     * @throws \InvalidArgumentException
     */
    public function replace(
        string|iterable $search,
        string|iterable $replace,
        mixed ...$subject
    ): string|array {
        if ((!is_scalar($search) && !is_iterable($search))
            || (!is_scalar($replace) && !is_iterable($replace))) {
            throw new InvalidArgumentException(
                'Search and replace parameters must ' .
                'be either scalar or iterable type.'
            );
        }

        /**
         * Support search/replace as any iterable type
         * (e.g. ArrayIterator, ArrayObject, etc.)
         */
        $search = !is_scalar($search)
            ? (array) $search : (string) $search;
        $replace = !is_scalar($replace)
            ? (array) $replace : (string) $replace;

        /** @var string[] $values */
        $values = array_map(
            strval(...),
            $subject
        );

        /** @var string|array $result */
        $result = str_replace(
            $search,
            $replace,
            $values
        );

        if (is_array($result) && count($result) === 1) {
            return (string)($result[0] ?? null);
        }

        return $result;
    }

    /**
     * @param string $subject
     * @param string $delimiter
     * @param bool $filter
     * @return string[]
     */
    public function split(
        string $subject,
        string $delimiter = ' ',
        bool $filter = false
    ): array {
        /** @var string[] $pieces */
        $pieces = explode($delimiter, $subject);

        if ($filter) {
            $pieces = array_filter(
                $pieces,
                strlen(...)
            );
        }

        return array_values($pieces);
    }

    /**
     * @param string $format
     * @param mixed[] $values
     * @return string|null
     */
    public function sprintf(
        string $format,
        mixed ...$values
    ): ?string {
        try {
            return vsprintf($format, $values);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param string $subject
     * @param string $delimiter
     * @param int|null $mode
     * @return string
     */
    public function trim(
        string $subject,
        string $delimiter = ' ',
        ?int $mode = null
    ): string {
        $mode ??= self::TRIM;

        /** @var string|null $callback */
        $callback = self::FUNC[$mode] ?? null;

        if ($callback === null) {
            return $subject;
        }

        /** @var array $result */
        $result = array_map(
            $callback,
            [$subject],
            [$delimiter]
        );
        return !empty($result) ? $result[0] : $subject;
    }
}
