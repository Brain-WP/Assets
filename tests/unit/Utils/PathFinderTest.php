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

namespace Brain\Assets\Tests\Unit\Utils;

use Brain\Assets\Tests\TestCase;
use Brain\Assets\Utils\PathFinder;

class PathFinderTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideFindPathData
     */
    public function testFindPath(bool $useAlt, string $url, string|null $expected): void
    {
        $finder = PathFinder::new($this->factoryContext(useAlt: $useAlt, debug: null));

        static::assertSame($expected, $finder->findPath($url));
    }

    /**
     * @return \Generator
     */
    public static function provideFindPathData(): \Generator
    {
        static $baseUrl = 'https://example.com/assets';
        static $altBaseUrl = 'https://example.com/alt/assets';

        return yield from [
            [
                false,
                '',
                null,
                ],
            [
                false,
                $baseUrl,
                null,
            ],
            [
                false,
                "{$baseUrl}/foo.css",
                static::fixturesPath('/foo.css'),
            ],
            [
                false,
                str_replace('https:', 'http:', "{$baseUrl}/foo.css"),
                static::fixturesPath('/foo.css'),
            ],
            [
                false,
                "{$baseUrl}/xyz.css",
                static::fixturesPath('/xyz.css'),
            ],
            [
                false,
                "{$baseUrl}/bar.css",
                static::fixturesPath('/bar.css'),
            ],
            [
                false,
                "{$altBaseUrl}/bar.css",
                null,
            ],
            [
                true,
                "{$altBaseUrl}/bar.css",
                static::fixturesPath('/alt/bar.css'),
            ],
            [
                true,
                "{$altBaseUrl}/bar.css?x=y",
                static::fixturesPath('/alt/bar.css'),
            ],
            [
                true,
                str_replace('https:', 'http:', "{$altBaseUrl}/foo.css?v123456789"),
                static::fixturesPath('/alt/foo.css'),
            ],
            [
                true,
                "{$altBaseUrl}/bar.abcde.min.css",
                static::fixturesPath('/alt/bar.abcde.min.css'),
            ],
        ];
    }
}
