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
use Brain\Assets\Utils\DependencyInfoExtractor;
use Brain\Assets\Utils\PathFinder;

/**
 * @ runTestsInSeparateProcesses
 */
class DependencyInfoExtractorTest extends TestCase
{
    /**
     * @test
     */
    public function testInfoIsNullIfPathNotFound(): void
    {
        $url = "https://example.com/foo.js";
        $finder = \Mockery::mock(PathFinder::class);
        /** @psalm-suppress UndefinedInterfaceMethod, MixedMethodCall */
        $finder->expects('findPath')->twice()->with($url)->andReturnNull();

        /** @var PathFinder $finder */
        $depInfoExtractor = DependencyInfoExtractor::new($finder);

        static::assertNull($depInfoExtractor->readVersion($url));
        static::assertSame([], $depInfoExtractor->readDependencies($url));
    }

    /**
     * @test
     */
    public function testInfoRetrieved(): void
    {
        $finder = $this->factoryPathFinder(useAlt: false, debug: false);
        $depInfoExtractor = DependencyInfoExtractor::new($finder);

        $url = "{$this->baseUrl}/some-script.js";

        // See /tests/fixtures/some-script.asset.php
        static::assertSame('a29c9d677e174811e603', $depInfoExtractor->readVersion($url));
        static::assertSame(['wp-api-fetch'], $depInfoExtractor->readDependencies($url));
    }

    /**
     * @test
     */
    public function testInfoRetrievedWithDifferentScheme(): void
    {
        $finder = $this->factoryPathFinder(useAlt: false, debug: false);
        $depInfoExtractor = DependencyInfoExtractor::new($finder);

        $url = str_replace('https://', 'http://', "{$this->baseUrl}/some-script.js");

        // See /tests/fixtures/some-script.asset.php
        static::assertSame('a29c9d677e174811e603', $depInfoExtractor->readVersion($url));
        static::assertSame(['wp-api-fetch'], $depInfoExtractor->readDependencies($url));
    }
}
