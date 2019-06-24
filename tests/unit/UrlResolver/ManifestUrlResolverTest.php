<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit\UrlResolver;

use Brain\Assets\Context\WpContext;
use Brain\Assets\Tests\TestCase;
use Brain\Assets\UrlResolver\DirectUrlResolver;
use Brain\Assets\UrlResolver\ManifestUrlResolver;
use Brain\Assets\UrlResolver\MinifyResolver;

class ManifestUrlResolverTest extends TestCase
{
    public function testResolveWithDisabledMinifierAndNoAlt()
    {
        $solver = $this->createSolver();
        $solved = $solver->resolve('foo.css', MinifyResolver::createDisabled());

        static::assertSame('https://example.com/assets/foo.abcde.css', $solved);
    }

    public function testResolveWithEnabledMinifierButNoMinFoundAndNoAlt()
    {
        $solver = $this->createSolver();
        $solved = $solver->resolve('foo.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/foo.abcde.css', $solved);
    }

    public function testResolveWithEnabledMinifierAndNoAlt()
    {
        $solver = $this->createSolver();
        $solved = $solver->resolve('no-min.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/foo.min.css', $solved);
    }

    public function testResolveWithEnabledMinifierNoMinFoundAndNoAlt()
    {
        $solver = $this->createSolver();
        $solved = $solver->resolve('meh.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/meh.css', $solved);
    }

    public function testResolveWithDisabledMinifierAndAlt()
    {
        $solver = $this->createSolver(true);
        $solved = $solver->resolve('bar-hash.css', MinifyResolver::createDisabled());

        static::assertSame('https://example.com/alt/assets/bar.abcde.css', $solved);
    }

    public function testResolveWithDisabledMinifierAndAltPathNonInManifest()
    {
        $solver = $this->createSolver(true);
        $solved = $solver->resolve('bar.css', MinifyResolver::createDisabled());

        static::assertSame('https://example.com/alt/assets/bar.min.css', $solved);
    }

    public function testResolveWithEnabledMinifierAndAltPathNonInManifest()
    {
        $solver = $this->createSolver(true);
        $solved = $solver->resolve('bar.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/alt/assets/bar.min.css', $solved);
    }

    public function testResolveWithEnabledMinifierButNoMinFoundAndAlt()
    {
        $solver = $this->createSolver(true);
        $solved = $solver->resolve('foo.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/foo.abcde.css', $solved);
    }

    /**
     * @param bool $alt
     * @return ManifestUrlResolver
     */
    private function createSolver(bool $alt = false): ManifestUrlResolver
    {
        $args = [getenv('FIXTURES_DIR'), 'https://example.com/assets'];
        if ($alt) {
            $args[] = getenv('FIXTURES_DIR') . '/alt';
            $args[] = 'https://example.com/alt/assets';
        }

        $context = new WpContext(...$args);

        return new ManifestUrlResolver(
            new DirectUrlResolver($context),
            getenv('FIXTURES_DIR') . '/manifest.json'
        );
    }
}
