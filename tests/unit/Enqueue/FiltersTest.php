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

namespace Brain\Assets\Tests\Unit\Enqueue;

use Brain\Assets\Enqueue\Filters;
use Brain\Assets\Tests\TestCase;

class FiltersTest extends TestCase
{
    /**
     * @test
     */
    public function testNoCallableDoNothing(): void
    {
        $filters = Filters::newForStyles();

        static::assertSame('foo', $filters->apply('foo'));
    }

    /**
     * @test
     */
    public function testThrowableIgnored(): void
    {
        $filters = Filters::newForStyles()
            ->add(static fn (string $str): string => "{$str}-bar")
            ->add(static fn (string $str): string => "{$str}-baz");

        static::assertSame('foo-bar-baz', $filters->apply('foo'));

        $filters->add(static fn () => throw new \Error());

        static::assertSame('foo-bar-baz', $filters->apply('foo'));
    }

    /**
     * @test
     */
    public function testReturningNonStringIgnored(): void
    {
        $filters = Filters::newForStyles()
            ->add(static fn (string $str): string => "{$str}-bar")
            ->add(static fn (string $str): array => [$str])
            ->add(static fn (string $str): string => "{$str}-baz");

        static::assertSame('foo-bar-baz', $filters->apply('foo'));
    }

    /**
     * @test
     */
    public function testAddAttributeDoNothingIfAttributeExists(): void
    {
        $tag = '<script src="https://example.com"></script>';

        $filters = Filters::newForStyles()
            ->add(static fn (string $str): string
                => str_replace('<script', '<script async', $str))
            ->add(static fn (string $str): string
                => str_replace('<script', '<script data-foo="bar"', $str))
            ->addAttribute('async', 'async')
            ->addAttribute('data-foo', 'x');

        $expected = '<script data-foo="bar" async src="https://example.com"></script>';

        static::assertSame($expected, $filters->apply($tag));
    }

    /**
     * @test
     */
    public function testRealWordTag(): void
    {
        $tag = '<script src="https://example.com"></script>';

        $filters = Filters::newForScripts()
            ->addAttribute('defer', null)
            ->addAttribute('foo', 'bar')
            ->addAttribute('async', null)
            ->addAttribute('defer', null)
            ->addAttribute('foo', "baz")
            ->addAttribute('async', null)
            ->add(static fn (string $tag): string => str_replace('.com', '.it', $tag));

        $expected = '<script async foo="bar" defer src="https://example.it"></script>';

        static::assertSame($expected, $filters->apply($tag));
    }
}
