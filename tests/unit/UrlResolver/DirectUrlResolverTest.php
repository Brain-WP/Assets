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
use Brain\Assets\UrlResolver\MinifyResolver;

class DirectUrlResolverTest extends TestCase
{
    /**
     * @test
     */
    public function testResolveNotMinifiedPathWithoutAltAndEnabledMinifier(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: false);
        $resolved = $resolver->resolve('foo.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/foo.min.css", $resolved);
    }

    /**
     * @return void
     */
    public function testResolveNotMinifiedPathWithoutAltAndEnabledMinifierMinNotFound(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: false);
        $resolved = $resolver->resolve('bar.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/bar.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveNotMinifiedPathWithoutAltAndDisabledMinifier(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: false);
        $resolved = $resolver->resolve('foo.css', null);

        static::assertSame("{$this->baseUrl}/foo.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveMinifiedPathWithoutAltAndEnabledMinifier(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: false);
        $resolved = $resolver->resolve('foo.min.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/foo.min.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveMinifiedPathWithoutAltAndEnabledMinifierMinNotFound(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: false);
        $resolved = $resolver->resolve('foo.min.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/foo.min.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveNotMinifiedPathWithAltAndEnabledMinifierPathNotFoundInMainDir(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: true);
        $resolved = $resolver->resolve('bar.css', MinifyResolver::new());

        static::assertSame("{$this->altBaseUrl}/bar.min.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveNotMinifiedPathWithAltAndEnabledMinifierPathFoundInMainDir(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: true);
        $resolved = $resolver->resolve('foo.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/foo.min.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveNotMinifiedPathWithAltAndDisabledMinifierPathFoundInMainDir(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: true);
        $resolved = $resolver->resolve('foo.css', null);

        static::assertSame("{$this->baseUrl}/foo.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveMinifiedPathWithAltAndEnabledMinifierPathFoundInMainDir(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: true);
        $resolved = $resolver->resolve('foo.min.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/foo.min.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveNotMinifiedPathWithAltAndNoMinifierPathNotFoundInMainDir(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: true);
        $resolved = $resolver->resolve('bar.css', null);

        static::assertSame("{$this->altBaseUrl}/bar.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveMinifiedPathWithAltAndEnabledMinifierPathNotFoundAnywhere(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: true);
        $resolved = $resolver->resolve('xxx.css', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/xxx.css", $resolved);
    }

    /**
     * @test
     */
    public function testResolveNotMinifiedPathWithoutAltAndEnabledMinifierAndQuery(): void
    {
        $resolver = $this->factoryUrlResolver(useAlt: false);
        $resolved = $resolver->resolve('foo.css?v=123', MinifyResolver::new());

        static::assertSame("{$this->baseUrl}/foo.min.css?v=123", $resolved);
    }

    /**
     * @param bool $useAlt
     * @return DirectUrlResolver
     */
    private function factoryUrlResolver(bool $useAlt): DirectUrlResolver
    {
        return DirectUrlResolver::new($this->factoryContext($useAlt, null));
    }
}
