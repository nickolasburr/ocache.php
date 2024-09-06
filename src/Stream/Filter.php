<?php
/**
 * Filter.php
 *
 * @package     Ocache\Stream
 * @copyright   Copyright (C) 2024 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Stream;

use Ocache\Stream\Filter\Buffer;
use php_user_filter as AbstractFilter;
use RuntimeException;

use function mb_substr;
use function stream_bucket_append;
use function stream_bucket_make_writeable;
use function stream_bucket_new;

use const PSFS_FEED_ME;
use const PSFS_PASS_ON;
use const Ocache\MAX_BYTES;

class Filter extends AbstractFilter
{
    public const FILTER_NAME = 'ocache.buffer';

    /** @var Buffer|null $buffer */
    private ?Buffer $buffer = null;

    /**
     * {@inheritdoc}
     */
    public function onCreate(): bool
    {
        return $this->filtername === static::FILTER_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(
        $in,
        $out,
        &$consumed,
        bool $closing
    ): int {
        /** @var int $result */
        $result = PSFS_FEED_ME;

        /** @var Buffer $buffer */
        $buffer = $this->getBuffer();

        do {
            /** @var object|null $bucket */
            $bucket = stream_bucket_make_writeable($in);

            if ($bucket === null) {
                break;
            }

            /** @var int $maxBytes */
            $maxBytes = MAX_BYTES - $buffer->getLength();

            if ($bucket->datalen >= $maxBytes) {
                /** @var string $substr */
                $substr = mb_substr(
                    $bucket->data,
                    0,
                    $maxBytes
                );
                $buffer->append($substr);

                /** @var string $overflow */
                $overflow = mb_substr($bucket->data, $maxBytes);
                $consumed += $bucket->datalen;

                if ($buffer->getLength() !== MAX_BYTES) {
                    throw new RuntimeException();
                }

                $bucket->data = (string) $buffer;
                $bucket->datalen = MAX_BYTES;
                stream_bucket_append($out, $bucket);

                $result = PSFS_PASS_ON;
                $buffer->flush();
                $buffer->append($overflow);
            } else {
                $buffer->append($bucket->data);
                $consumed += $buffer->getLength();
            }
        } while (true);

        if ($closing && $buffer->getLength() > 0) {
            $bucket = stream_bucket_new(
                $this->stream,
                $buffer->flush()
            );
            stream_bucket_append($out, $bucket);
            $result = PSFS_PASS_ON;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(): void
    {
        $this->buffer = null;
    }

    /**
     * @return Buffer
     */
    private function getBuffer(): Buffer
    {
        $this->buffer ??= new Buffer();
        return $this->buffer;
    }
}
