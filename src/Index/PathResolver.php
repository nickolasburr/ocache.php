<?php
/**
 * PathResolver.php
 *
 * @package     Ocache\Index
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Index;

use Ocache\Cache\Config;
use Ocache\Index\PathBuilder;
use Ocache\Utils\StringUtils;

readonly class PathResolver
{
    /** @var PathBuilder $pathBuilder */
    private PathBuilder $pathBuilder;

    /** @var StringUtils $stringUtils */
    private StringUtils $stringUtils;

    /**
     * @param Config $config
     * @return void
     */
    public function __construct(
        private Config $config
    ) {
        $this->pathBuilder = new PathBuilder();
        $this->stringUtils = new StringUtils();
    }

    /**
     * @param string $file
     * @return string
     */
    public function resolve(string $file): string
    {
        return $this->pathBuilder->build(
            $this->config->getCacheDir(),
            $this->config->getIndexKey(),
            $this->stringUtils->sprintf('%s.php', $file)
        );
    }
}
