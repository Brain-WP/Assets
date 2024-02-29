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

use Brain\Assets\Enqueue\Strategy;
use Brain\Assets\Tests\TestCase;

class StrategyTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideTestNewData
     */
    public function testNew(mixed $input, array $expected): void
    {
        $strategy = Strategy::new($input);

        static::assertSame($expected, $strategy->toArray());
        static::assertSame($expected['in_footer'], $strategy->inFooter());
        static::assertSame(isset($expected['strategy']), $strategy->hasStrategy());
        static::assertSame(($expected['strategy'] ?? '') === 'async', $strategy->isAsync());
        static::assertSame(($expected['strategy'] ?? '') === 'defer', $strategy->isDefer());
        static::assertTrue(Strategy::new($expected)->equals($strategy));
    }

    /**
     * @return \Generator
     */
    public static function provideTestNewData(): \Generator
    {
        return yield from [
            [
                null,
                ['in_footer' => true],
            ],
            [
                true,
                ['in_footer' => true],
            ],
            [
                false,
                ['in_footer' => false],
            ],
            [
                'ASYNC',
                ['in_footer' => false, 'strategy' => 'async'],
            ],
            [
                ' deFer ',
                ['in_footer' => false, 'strategy' => 'defer'],
            ],
            [
                32,
                ['in_footer' => true],
            ],
            [
                [],
                ['in_footer' => true],
            ],
            [
                ['strategy' => 'meh'],
                ['in_footer' => true],
            ],
            [
                new \stdClass(),
                ['in_footer' => true],
            ],
            [
                ['strategy' => 'DEFER', 'in_footer' => true],
                ['in_footer' => true, 'strategy' => 'defer'],
            ],
            [
                ['strategy' => 'DEFER'],
                ['in_footer' => false, 'strategy' => 'defer'],
            ],
            [
                ['strategy' => 'async'],
                ['in_footer' => false, 'strategy' => 'async'],
            ],
            [
                ['strategy' => 'async', 'in_footer' => true],
                ['in_footer' => true, 'strategy' => 'async'],
            ],
            [
                Strategy::newDefer(),
                ['in_footer' => false, 'strategy' => 'defer'],
            ],
            [
                Strategy::newDefer(true),
                ['in_footer' => true, 'strategy' => 'defer'],
            ],
            [
                Strategy::newDeferInFooter(),
                ['in_footer' => true, 'strategy' => 'defer'],
            ],
            [
                Strategy::newAsync(),
                ['in_footer' => false, 'strategy' => 'async'],
            ],
            [
                Strategy::newAsync(true),
                ['in_footer' => true, 'strategy' => 'async'],
            ],
            [
                Strategy::newAsyncInFooter(),
                ['in_footer' => true, 'strategy' => 'async'],
            ],
            [
                Strategy::newInFooter(),
                ['in_footer' => true],
            ],
            [
                Strategy::newInHead(),
                ['in_footer' => false],
            ],
        ];
    }

    /**
     * @return void
     */
    public function testRemoveStrategy(): void
    {
        $strategy = Strategy::newDefer();
        static::assertTrue($strategy->removeStrategy()->equals(Strategy::newInHead()));

        $strategy = Strategy::newAsyncInFooter();
        static::assertTrue($strategy->removeStrategy()->equals(Strategy::newInFooter()));

        $strategy = Strategy::newInFooter();
        static::assertTrue($strategy->removeStrategy()->equals(Strategy::newInFooter()));

        $strategy = Strategy::newInHead();
        static::assertTrue($strategy->removeStrategy()->equals(Strategy::newInHead()));
    }
}
