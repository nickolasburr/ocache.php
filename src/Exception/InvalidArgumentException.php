<?php
/**
 * InvalidArgumentException.php
 *
 * @package     VfsCache\Exception
 * @copyright   Copyright (C) 2023 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace VfsCache\Exception;

use Psr\SimpleCache\InvalidArgumentException as InvalidArgumentExceptionInterface;

class InvalidArgumentException extends CacheException implements InvalidArgumentExceptionInterface
{
}
