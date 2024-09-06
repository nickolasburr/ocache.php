<?php
/**
 * CacheException.php
 *
 * @package     Ocache\Exception
 * @copyright   Copyright (C) 2024 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Exception;

use Psr\SimpleCache\CacheException as CacheExceptionInterface;

class CacheException extends \Exception implements CacheExceptionInterface
{
}
