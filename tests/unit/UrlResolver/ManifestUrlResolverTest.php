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

namespace Brain\Assets\Tests\Unit\UrlResolver;

use Brain\Assets\Tests\TestCase;
use Brain\Assets\UrlResolver\DirectUrlResolver;
use Brain\Assets\UrlResolver\ManifestUrlResolver;
use Brain\Assets\UrlResolver\MinifyResolver;

class ManifestUrlResolverTest extends TestCase
{
    /**
     * @test
     */
    public function testResolveWithDisabledMinifierAndNoAlt(): void
    {
        $solver = $this->factorySolver(useAlt: false);
        $solved = $solver->resolve('foo.css', null);

        static::assertSame("{$this->baseUrl}/foo.abcde.css", $solved);
    }

    /**
     * @test
     */
    public function testResolveWithEnabledMinifierButNoMinFoundAndNoAlt(): void
    {
        $solver = $this->factorySolver(useAlt: false);
        $solved = $solver->resolve('foo.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/foo.abcde.css", $solved);
    }

    /**
     * @test
     */
    public function testResolveWithEnabledMinifierAndNoAlt(): void
    {
        $solver = $this->factorySolver(useAlt: false);
        $solved = $solver->resolve('no-min.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/foo.min.css", $solved);
    }

    /**
     * @test
     */
    public function testResolveWithEnabledMinifierNoMinFoundAndNoAlt(): void
    {
        $solver = $this->factorySolver(useAlt: false);
        $solved = $solver->resolve('meh.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/meh.css", $solved);
    }

    /**
     * @test
     */
    public function testResolveWithDisabledMinifierAndAlt(): void
    {
        $solver = $this->factorySolver(useAlt: true);
        $solved = $solver->resolve('bar-hash.css', null);

        static::assertSame("{$this->altBaseUrl}/bar.abcde.css", $solved);
    }

    /**
     * @test
     */
    public function testResolveWithDisabledMinifierAndAltPathNonInManifest(): void
    {
        $solver = $this->factorySolver(useAlt: true);
        $solved = $solver->resolve('bar.css', null);

        static::assertSame("{$this->altBaseUrl}/bar.min.css", $solved);
    }

    /**
     * @test
     */
    public function testResolveWithEnabledMinifierAndAltPathNonInManifest(): void
    {
        $solver = $this->factorySolver(useAlt: true);
        $solved = $solver->resolve('bar.css', MinifyResolver::new());

        static::assertSame("{$this->altBaseUrl}/bar.min.css", $solved);
    }

    /**
     * @test
     */
    public function testResolveWithEnabledMinifierButNoMinFoundAndAlt(): void
    {
        $solver = $this->factorySolver(useAlt: true);
        $solved = $solver->resolve('foo.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/foo.abcde.css", $solved);
    }

    /**
     * @param bool $useAlt
     * @return ManifestUrlResolver
     */
    private function factorySolver(bool $useAlt): ManifestUrlResolver
    {
        return ManifestUrlResolver::new(
            DirectUrlResolver::new($this->factoryContext($useAlt, null)),
            static::fixturesPath('/manifest.json')
        );
    }
}
