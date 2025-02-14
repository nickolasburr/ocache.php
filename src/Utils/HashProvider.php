<?php
/**
 * HashProvider.php
 *
 * @package     Ocache\Utils
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Utils;

use InvalidArgumentException;

use function hash;
use function hash_algos;
use function in_array;
use function sprintf;

use const Ocache\Cache\HASH_ALGO;

final readonly class HashProvider
{
    /**
     * @param string $hashAlgo
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(
        private string $hashAlgo = HASH_ALGO
    ) {
        /** @var string[] $hashAlgos */
        $hashAlgos = hash_algos();

        if (!in_array($hashAlgo, $hashAlgos)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid hashing algorithm "%s"',
                    $hashAlgo
                )
            );
        }
    }

    /**
     * @param string $value
     * @return string
     */
    public function hash(string $value): string
    {
        return hash($this->hashAlgo, $value);
    }
}
