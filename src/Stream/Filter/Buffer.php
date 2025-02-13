<?php
/**
 * Buffer.php
 *
 * @package     Ocache\Stream\Filter
 * @copyright   Copyright (C) 2025 Nickolas Burr <nickolasburr@gmail.com>
 */
declare(strict_types=1);

namespace Ocache\Stream\Filter;

use Fiber;
use Stringable;

use function array_pop;
use function array_reverse;
use function implode;
use function mb_strlen;

final class Buffer implements Stringable
{
    /** @var string[] $buffer */
    private array $buffer = [];

    /** @var int $length */
    private int $length = 0;

    /**
     * @return string[]
     */
    public function getBuffer(): array
    {
        return $this->buffer;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @param string $values
     * @return static
     */
    public function append(string ...$values): static
    {
        /** @var string $value */
        foreach ($values as $value) {
            $this->buffer[] = $value;
            $this->length += mb_strlen($value);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function flush(): string
    {
        /** @var string $result */
        $result = '';

        /** @var Fiber|null $fiber */
        $fiber = Fiber::getCurrent();

        /** @var string[] $buffer */
        $buffer = array_reverse($this->buffer);

        do {
            /** @var string|null $chunk */
            $chunk = array_pop($buffer);

            if ($chunk === null) {
                break;
            }

            $result .= (
                $fiber ? Fiber::suspend($chunk) : $chunk
            );
        } while (true);

        $this->reset();
        return $result;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->buffer = [];
        $this->length = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return implode('', $this->buffer);
    }
}
