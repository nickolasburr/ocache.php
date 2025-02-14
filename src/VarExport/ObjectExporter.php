<?php
/**
 * ObjectExporter.php
 *
 * @package     Ocache\Sync
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\VarExport;

use Ocache\Cache\Config;
use Ocache\Index\PathResolver;
use Ocache\Stream\Filter;
use Ocache\Stream\FilterRegistry;
use Ocache\Utils\StringUtils;
use RuntimeException;
use Symfony\Component\VarExporter\VarExporter;

use function fclose;
use function fopen;
use function fwrite;
use function mb_strlen;
use function mb_substr;
use function Ocache\Index\pathResolver;
use function Ocache\Stream\filterRegistry;

use const DIRECTORY_SEPARATOR;
use const STREAM_FILTER_WRITE;
use const Ocache\MAX_BYTES;
use const Ocache\WRIT_B;

final readonly class ObjectExporter
{
    /** @var FilterRegistry $filterRegistry */
    private FilterRegistry $filterRegistry;

    /** @var PathResolver $pathResolver */
    private PathResolver $pathResolver;

    /** @var StringUtils $stringUtils */
    private StringUtils $stringUtils;

    /**
     * @param Config $config
     * @return void
     */
    public function __construct(
        private Config $config
    ) {
        $this->filterRegistry = filterRegistry();
        $this->pathResolver = pathResolver($config);
        $this->stringUtils = new StringUtils();
    }

    /**
     * @param string $key
     * @param object|null $value
     * @return true
     * @throws RuntimeException
     */
    public function export(string $key, ?object $value): true
    {
        /** @var string $path */
        $path = $this->pathResolver->resolve($key);

        /** @var resource|false $handle */
        $handle = fopen($path, WRIT_B);

        if ($handle === false) {
            throw new RuntimeException(
                $this->stringUtils->sprintf(
                    'Unable to create object file "%s"',
                    $path
                )
            );
        }

        $this->filterRegistry->append(
            Filter::NAME,
            $handle,
            STREAM_FILTER_WRITE
        );

        /** @var string|null $export */
        $export = $this->getVarExport($value);

        /** @var int $index */
        $index = 0;

        /** @var int $length */
        $length = mb_strlen($export);

        do {
            /** @var int|false $result */
            $result = fwrite(
                $handle,
                mb_substr(
                    $export,
                    $index,
                    MAX_BYTES
                )
            );

            if ($result === false) {
                throw new RuntimeException(
                    $this->stringUtils->sprintf(
                        'Unable to export object file "%s"',
                        $path
                    )
                );
            }

            $index += $result;
        } while ($index < $length);

        $this->filterRegistry->remove(
            Filter::NAME,
            $handle
        );
        fclose($handle);
        return true;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function getVarExport(mixed $value): string
    {
        /** @var string $export */
        $export = VarExporter::export($value);
        return <<<EOS
<?php return $export;
EOS;
    }
}
