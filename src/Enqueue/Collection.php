<?php

declare(strict_types=1);

namespace Brain\Assets\Enqueue;

use Brain\Assets\Assets;

/**
 * @template-implements \IteratorAggregate<int, Enqueue>
 * @psalm-consistent-constructor
 * @psalm-pure
 */
class Collection implements \IteratorAggregate, \Countable
{
    /** @var list<Enqueue> */
    private array $collection;

    /**
     * @param Assets $assets
     * @param Enqueue ...$enqueues
     * @return static
     *
     * @no-named-arguments
     */
    public static function new(Assets $assets, Enqueue ...$enqueues): static
    {
        return new static($assets, ...$enqueues);
    }

    /**
     * @param Assets $assets
     * @param Enqueue ...$enqueues
     *
     * @no-named-arguments
     */
    final protected function __construct(
        private Assets $assets,
        Enqueue ...$enqueues
    ) {

        $this->collection = $enqueues;
    }

    /**
     * @param string $pattern
     * @param 'css'|'js'|null $type
     * @return static
     */
    public function keep(string $pattern, ?string $type = null): static
    {
        $this->assertType($type, __METHOD__);
        if ($pattern === '') {
            return new static($this->assets);
        }

        return $this->filter($this->keepCallback($pattern, $type));
    }

    /**
     * @param string $pattern
     * @param 'css'|'js'|null $type
     * @return static
     */
    public function discard(string $pattern, ?string $type = null): static
    {
        $this->assertType($type, __METHOD__);
        if ($pattern === '') {
            return new static($this->assets, ...$this->collection);
        }

        return $this->filterOut($this->keepCallback($pattern, $type));
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $collection = [];
        foreach ($this->collection as $enqueue) {
            try {
                $callback($enqueue) and $collection[] = $enqueue;
            } catch (\Throwable) {
                continue;
            }
        }

        return new static($this->assets, ...$collection);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function filterOut(callable $callback): static
    {
        $collection = [];
        foreach ($this->collection as $enqueue) {
            try {
                $callback($enqueue) or $collection[] = $enqueue;
            } catch (\Throwable) {
                continue;
            }
        }

        return new static($this->assets, ...$collection);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function apply(callable $callback): static
    {
        foreach ($this->collection as $enqueue) {
            try {
                $callback($enqueue);
            } catch (\Throwable) {
                continue;
            }
        }

        return new static($this->assets, ...$this->collection);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        $collection = [];
        foreach ($this->collection as $enqueue) {
            try {
                $newEnqueue = $callback($enqueue);
                ($newEnqueue instanceof Enqueue) and $collection[] = $newEnqueue;
            } catch (\Throwable) {
                continue;
            }
        }

        return new static($this->assets, ...$collection);
    }

    /**
     * @param Collection $collection
     * @return $this
     */
    public function merge(Collection $collection): static
    {
        $merged = [];
        foreach ($this->collection as $enqueue) {
            $id = $enqueue->handle() . ($enqueue->isJs() ? '--js' : '--css');
            $merged[$id] = $enqueue;
        }

        foreach ($collection->collection as $enqueue) {
            $id = $enqueue->handle() . ($enqueue->isJs() ? '--js' : '--css');
            $merged[$id] = $enqueue;
        }

        return static::new($this->assets, ...array_values($merged));
    }

    /**
     * @param Collection $collection
     * @return $this
     */
    public function diff(Collection $collection): static
    {
        $diff = [];
        foreach ($this->collection as $enqueue) {
            $id = $enqueue->handle() . ($enqueue->isJs() ? '--js' : '--css');
            $diff[$id] = $enqueue;
        }

        foreach ($collection->collection as $enqueue) {
            $id = $enqueue->handle() . ($enqueue->isJs() ? '--js' : '--css');
            unset($diff[$id]);
        }

        return static::new($this->assets, ...array_values($diff));
    }

    /**
     * @return static
     */
    public function cssOnly(): static
    {
        $collection = [];
        foreach ($this->collection as $enqueue) {
            $enqueue->isCss() and $collection[] = $enqueue;
        }

        return new static($this->assets, ...$collection);
    }

    /**
     * @return static
     */
    public function jsOnly(): static
    {
        $collection = [];
        foreach ($this->collection as $enqueue) {
            $enqueue->isJs() and $collection[] = $enqueue;
        }

        return new static($this->assets, ...$collection);
    }

    /**
     * @return static
     */
    public function enqueuedOnly(): static
    {
        $collection = [];
        foreach ($this->collection as $enqueue) {
            $enqueue->isEnqueued() and $collection[] = $enqueue;
        }

        return new static($this->assets, ...$collection);
    }

    /**
     * @return static
     */
    public function notEnqueuedOnly(): static
    {
        $collection = [];
        foreach ($this->collection as $enqueue) {
            $enqueue->isEnqueued() or $collection[] = $enqueue;
        }

        return new static($this->assets, ...$collection);
    }

    /**
     * @param 'css'|'js'|null $type
     * @return Enqueue|null
     */
    public function first(?string $type = null): ?Enqueue
    {
        $this->assertType($type, __METHOD__, 1);

        foreach ($this->collection as $enqueue) {
            if ($this->matchType($enqueue, $type)) {
                return $enqueue;
            }
        }

        return null;
    }

    /**
     * @param 'css'|'js'|null $type
     * @return Enqueue|null
     */
    public function last(?string $type = null): ?Enqueue
    {
        $this->assertType($type, __METHOD__, 1);

        $collection = $this->collection;
        while ($collection) {
            $enqueue = array_pop($collection);
            if ($this->matchType($enqueue, $type)) {
                return $enqueue;
            }
        }

        return null;
    }

    /**
     * @param callable $callback
     * @return Enqueue|null
     *
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel
     */
    public function firstOf(callable $callback): ?Enqueue
    {
        // phpcs:enable Inpsyde.CodeQuality.NestingLevel
        foreach ($this->collection as $enqueue) {
            try {
                if ($callback($enqueue)) {
                    return $enqueue;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param callable $callback
     * @return Enqueue|null
     *
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel
     */
    public function lastOf(callable $callback): ?Enqueue
    {
        // phpcs:enable Inpsyde.CodeQuality.NestingLevel
        $collection = $this->collection;
        while ($collection) {
            $enqueue = array_pop($collection);
            try {
                if ($callback($enqueue)) {
                    return $enqueue;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param 'css'|'js'|null $type
     * @return Enqueue|null
     */
    public function oneByName(string $name, ?string $type = null): ?Enqueue
    {
        $this->assertNonEmptyString($name, 'name');
        $this->assertType($type, __METHOD__);

        $data = $this->collection ? $this->parseNameType($name, $type) : null;
        if ($data === null) {
            return null;
        }

        [$name, $noExtName, $type] = $data;
        foreach ($this->findNameCandidates($name, $noExtName, $type) as $candidate) {
            $handle = $this->assets->handleForName($candidate);
            $item = ($handle !== '') ? $this->findByHandle(strtolower($handle), $type) : null;
            if ($item !== null) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param string $handle
     * @param 'css'|'js'|null $type
     * @return Enqueue|null
     */
    public function oneByHandle(string $handle, ?string $type = null): ?Enqueue
    {
        $this->assertNonEmptyString($handle, 'handle');
        $this->assertType($type, __METHOD__);

        return $this->findByHandle(strtolower($handle), $type);
    }

    /**
     * @return \Iterator<int, Enqueue>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->collection);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->collection === [];
    }

    /**
     * @param 'css'|'js'|null $type
     * @return list<string>
     */
    public function handles(?string $type = null): array
    {
        $this->assertType($type, __METHOD__, 1);

        $handles = [];
        foreach ($this->collection as $enqueue) {
            if ($this->matchType($enqueue, $type)) {
                $handles[] = $enqueue->handle();
            }
        }

        return $handles;
    }

    /**
     * @return void
     */
    public function enqueue(): void
    {
        foreach ($this->collection as $enqueue) {
            $enqueue->enqueue();
        }
    }

    /**
     * @param non-empty-string $name
     * @param 'css'|'js'|null $type
     * @return null|list{
     *     non-empty-lowercase-string,
     *     non-empty-lowercase-string|null,
     *     'css'|'js'|null
     * }
     */
    private function parseNameType(string $name, ?string $type): ?array
    {
        $name = strtolower($name);
        $ext = $type;
        $noExtName = null;
        if (preg_match('~(.+?)\.(css|js)$~', $name, $matches)) {
            /** @var non-empty-lowercase-string $noExtName */
            $noExtName = $matches[1];
            /** @var 'css'|'js' $ext */
            $ext = $matches[2];
        }
        if (($type !== null) && ($ext !== $type)) {
            return null;
        }

        return [$name, $noExtName, $ext];
    }

    /**
     * @param non-empty-lowercase-string $name
     * @param non-empty-lowercase-string|null $noExtName
     * @param 'css'|'js'|null $type
     * @return list<non-empty-lowercase-string>
     */
    private function findNameCandidates(string $name, ?string $noExtName, ?string $type): array
    {
        /** @var non-empty-lowercase-string|null $fullName */
        $fullName = ($type !== null) && ($noExtName !== null)
            ? "{$noExtName}.{$type}"
            : null;

        $candidates = [];
        ($fullName !== null) and $candidates[] = $fullName;
        ($name !== $fullName) and $candidates[] = $name;
        (($noExtName !== null) && ($noExtName !== $name)) and $candidates[] = $noExtName;

        return $candidates;
    }

    /**
     * @param non-empty-lowercase-string $handle
     * @param 'css'|'js'|null $type
     * @return Enqueue|null
     */
    private function findByHandle(string $handle, ?string $type): ?Enqueue
    {
        if (!$this->collection) {
            return null;
        }

        $prefix = strtolower($this->assets->handlePrefix());
        $prefixedHandle = str_starts_with($handle, $prefix) ? null : "{$prefix}-{$handle}";
        foreach ($this->collection as $enqueue) {
            if (!$this->matchType($enqueue, $type)) {
                continue;
            }

            /** @var non-empty-lowercase-string $itemHandle */
            $itemHandle = strtolower($enqueue->handle());
            if (($itemHandle === $handle) || ($itemHandle === $prefixedHandle)) {
                return $enqueue;
            }
        }

        return null;
    }

    /**
     * @param Enqueue $enqueue
     * @param 'css'|'js'|null $type
     * @return bool
     */
    private function matchType(Enqueue $enqueue, ?string $type): bool
    {
        return ($type === null) || (($type === 'css') === $enqueue->isCss());
    }

    /**
     * @param non-empty-string $pattern
     * @param 'css'|'js'|null $type
     * @return callable
     */
    private function keepCallback(string $pattern, ?string $type): callable
    {
        $isRegex = $this->isRegex($pattern);
        $isGlob = !$isRegex && str_contains($pattern, '*');
        $isRegex or $pattern = strtolower($pattern);

        return function (Enqueue $item) use ($pattern, $type, $isRegex, $isGlob): bool {
            if (!$this->matchType($item, $type)) {
                return false;
            }
            $handle = $item->handle();

            return match (true) {
                $isGlob => fnmatch($pattern, strtolower($handle)),
                $isRegex => preg_match($pattern, $handle) === 1,
                default => str_contains(strtolower($handle), $pattern),
            };
        };
    }

    /**
     * @param non-empty-string $pattern
     * @return bool
     */
    private function isRegex(string $pattern): bool
    {
        if ((strlen($pattern) < 3) || (trim($pattern) !== $pattern)) {
            return false;
        }

        $first = $pattern[0];
        $last = substr($pattern, -1);
        if (($first === '{') && ($last === '}')) {
            return true;
        }

        // Formally, `-` is a valid regex delimiter, but we don't allow it as it is an allowed
        // character in handles. It means that a string like "-foo-" could be seen as a regex
        // instead of a basic string search. But `-` is not a commonly used delimiter, so it should
        // not be a big deal.
        if (($first === '\\') || ($first === '-') || preg_match('~[\w-]~i', $first) === 1) {
            return false;
        }

        if ($first === $last) {
            return true;
        }

        $sep1 = preg_quote($first, '{');
        $sep2 = ($first === '{') ? preg_quote('}', '{') : $sep1;

        if (preg_match("{^{$sep1}.+?{$sep2}([inmsuxADJSXU]+)$}", $pattern, $matches) !== 1) {
            return false;
        }

        $flags = str_split($matches[1]);

        return array_unique($flags) === $flags;
    }

    /**
     * @param string $str
     * @param 'handle'|'name' $varName
     * @return void
     *
     * @psalm-assert non-empty-string $str
     */
    private function assertNonEmptyString(string $str, string $varName): void
    {
        if ($str === '') {
            throw new \TypeError(
                sprintf(
                    '%s::%s() Argument 1 ($%s) bust be of type "non-empty-string", "" provided',
                    __CLASS__,
                    'oneBy' . ucfirst($varName), // phpcs:disable
                    $varName
                )
            );
        }
    }

    /**
     * @param string|null $type
     * @param string $method
     * @param int $num
     * @return void
     *
     * @psalm-assert 'css'|'js'|null $type
     */
    private function assertType(?string $type, string $method, int $num = 2): void
    {
        if (($type !== null) && ($type !== Assets::CSS) && ($type !== Assets::JS)) {
            throw new \TypeError(
                sprintf(
                    '%s() Argument %d ($type) bust be of type "null|css|js" %s provided',
                    $method, // phpcs:ignore
                    $num,
                    esc_html($type)
                )
            );
        }
    }
}
