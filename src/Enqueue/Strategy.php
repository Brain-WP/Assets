<?php

/*
 * This file is part of the Brain Assets package.
 *
 * Licensed under MIT License (MIT)
 * Copyright (c) 2024 Giuseppe Mazzapica and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Brain\Assets\Enqueue;

/**
 * @template-covariant F of bool
 * @template-covariant S of 'async'|'defer'|null
 * @psalm-immutable
 */
final class Strategy
{
    public const ASYNC = 'async';
    public const DEFER = 'defer';

    /**
     * @param mixed $strategy
     * @return Strategy
     *
     * @psalm-pure
     */
    public static function new(mixed $strategy = null): Strategy
    {
        if ($strategy instanceof Strategy) {
            return $strategy;
        }

        if (is_string($strategy)) {
            $strategy = strtolower(trim($strategy));
            if (($strategy === self::ASYNC) || ($strategy === self::DEFER)) {
                return new static(false, $strategy);
            }
        }

        $isBool = is_bool($strategy);
        if ($isBool || !is_array($strategy)) {
            $isBool or $strategy = true;
            return new static((bool) $strategy, null);
        }

        $strategy = array_change_key_case($strategy, CASE_LOWER);

        $option = $strategy['strategy'] ?? null;
        is_string($option) and $option = strtolower(trim($option));
        if (($option !== self::ASYNC) && ($option !== self::DEFER)) {
            $option = null;
        }

        $inFooter = $strategy['in_footer'] ?? null;
        if (!is_bool($inFooter)) {
            $inFooter = ($option === null);
        }

        return new static($inFooter, $option);
    }

    /**
     * @template iF of bool
     * @param iF $inFooter
     * @return Strategy<iF, 'async'>
     *
     * @psalm-pure
     */
    public static function newAsync(bool $inFooter = false): Strategy
    {
        return new self($inFooter, self::ASYNC);
    }

    /**
     * @template iF of bool
     * @param iF $inFooter
     * @return Strategy<iF, 'defer'>
     *
     * @psalm-pure
     */
    public static function newDefer(bool $inFooter = false): Strategy
    {
        return new self($inFooter, self::DEFER);
    }

    /**
     * @return Strategy<true, 'async'>
     *
     * @psalm-pure
     */
    public static function newAsyncInFooter(): Strategy
    {
        return new self(true, self::ASYNC);
    }

    /**
     * @return Strategy<true, 'defer'>
     *
     * @psalm-pure
     */
    public static function newDeferInFooter(): Strategy
    {
        return new self(true, self::DEFER);
    }

    /**
     * @return Strategy<true, null>
     *
     * @psalm-pure
     */
    public static function newInFooter(): Strategy
    {
        return new self(true, null);
    }

    /**
     * @return Strategy<false, null>
     *
     * @psalm-pure
     */
    public static function newInHead(): Strategy
    {
        return new self(false, null);
    }

    /**
     * @param F $inFooter
     * @param S $strategy
     */
    private function __construct(
        private bool $inFooter,
        private string|null $strategy,
    ) {
    }

    /**
     * @return bool
     *
     * @psalm-assert-if-true true $this->inFooter
     * @psalm-assert-if-false false $this->inFooter
     */
    public function inFooter(): bool
    {
        return $this->inFooter;
    }

    /**
     * @return bool
     *
     * @psalm-assert-if-true 'async'|'defer' $this->strategy
     * @psalm-assert-if-false null $this->strategy
     */
    public function hasStrategy(): bool
    {
        return $this->strategy !== null;
    }

    /**
     * @return bool
     *
     * @psalm-assert-if-true 'async' $this->strategy
     * @psalm-assert-if-false 'defer'|null $this->strategy
     */
    public function isAsync(): bool
    {
        return $this->strategy === self::ASYNC;
    }

    /**
     * @return bool
     *
     * @psalm-assert-if-true 'defer' $this->strategy
     * @psalm-assert-if-false 'async'|null $this->strategy
     */
    public function isDefer(): bool
    {
        return $this->strategy === self::DEFER;
    }

    /**
     * @return static
     */
    public function removeStrategy(): static
    {
        return $this->inFooter ? static::newInFooter() : static::newInHead();
    }

    /**
     * @param Strategy $strategy
     * @return bool
     */
    public function equals(Strategy $strategy): bool
    {
        return $strategy->toArray() === $this->toArray();
    }

    /**
     * @return array{in_footer: bool, strategy?: 'async'|'defer'}
     */
    public function toArray(): array
    {
        $data = ['in_footer' => $this->inFooter];
        if ($this->strategy !== null) {
            $data['strategy'] = $this->strategy;
        }

        return $data;
    }
}
