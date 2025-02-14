<?php
/**
 * constants.php
 *
 * @package     Ocache
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache;

/** @var mixed[] $consts */
$consts = [
    'REQUIRE_PROXY_PATH' => __DIR__ . '/require_proxy.php',
];

/** @var string $const */
/** @var mixed $value */
foreach ($consts as $const => $value) {
    if (!defined($const)) {
        define($const, $value);
    }
}
