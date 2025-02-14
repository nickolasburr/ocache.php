<?php
/**
 * PathBuilder.php
 *
 * @package     Ocache\Index
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Index;

use Ocache\Utils\StringUtils;

use function array_merge;
use function array_pop;
use function array_shift;

use const DIRECTORY_SEPARATOR;

readonly class PathBuilder
{
    /** @var StringUtils $stringUtils */
    private StringUtils $stringUtils;

    /**
     * @param string $delimiter
     * @return void
     */
    public function __construct(
        private string $delimiter = DIRECTORY_SEPARATOR
    ) {
        $this->stringUtils = new StringUtils();
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @param string[] $values
     * @return string
     */
    public function build(string ...$values): string
    {
        /** @var string $dirsep */
        $dirsep = $this->getDelimiter();

        /** @var string $basePath */
        $basePath = $this->stringUtils->trim(
            (string) array_shift($values)
        );

        if (!empty($basePath) && $basePath[0] === $dirsep) {
            $basePath = $this->stringUtils->concat(
                [
                    '',
                    $this->stringUtils->trim(
                        $basePath,
                        $dirsep
                    ),
                ],
                $dirsep
            );
        }

        /** @var string $basename */
        $basename = $this->stringUtils->trim(
            (string) array_pop($values)
        );

        if (!empty($basename)) {
            $basename = $this->stringUtils->concat(
                $this->stringUtils->split(
                    $basename,
                    $dirsep,
                    true
                ),
                $dirsep
            );
        }

        /** @var string[] $dirs */
        $dirs = $this->stringUtils->filter($values);

        /** @var int|string $index */
        /** @var string $value */
        foreach ($dirs as $index => $value) {
            /** @var string[] $paths */
            $paths = $this->stringUtils->split(
                $value,
                $dirsep,
                true
            );

            /** @var string $path */
            $path = $this->stringUtils->concat(
                $paths,
                $dirsep
            );
            $dirs[$index] = $this->stringUtils->trim($path);
        }

        /** @var array $result */
        $result = array_merge(
            [$basePath],
            $dirs,
            [$basename]
        );
        return $this->stringUtils->concat(
            $result,
            $dirsep
        );
    }
}
