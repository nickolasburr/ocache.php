<?php
/**
 * require_proxy.php
 *
 * @package     Ocache
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache;

use const E_USER_WARNING;
use const PHP_EOL;

/**
 * @var string $path
 */

if (!isset($path)) {
    /** @var string $eol */
    $eol = PHP_EOL;

    /** @var string $file */
    $file = __FILE__;
    trigger_error(<<<EOS
Unable to require file via $file;$eol
Variable "\$path" is not defined;$eol
EOS
        ,
        E_USER_WARNING
    );
    return null;
}

return require $path;
