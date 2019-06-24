<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit\UrlResolver;

use Brain\Assets\Context\WpContext;
use Brain\Assets\Tests\TestCase;
use Brain\Assets\UrlResolver\DirectUrlResolver;
use Brain\Assets\UrlResolver\MinifyResolver;

class DirectUrlResolverTest extends TestCase
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $altBasePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = getenv('FIXTURES_DIR');
        $this->altBasePath = getenv('FIXTURES_DIR') . '/alt';
    }

    public function testResolveNotMinifiedPathWithoutAltAndEnabledMinifier()
    {
        $context = new WpContext($this->basePath, 'https://example.com/assets');
        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('foo.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/foo.min.css', $resolved);
    }

    public function testResolveNotMinifiedPathWithoutAltAndEnabledMinifierMinNotFound()
    {
        $context = new WpContext($this->basePath, 'https://example.com/assets');
        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('bar.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/bar.css', $resolved);
    }

    public function testResolveNotMinifiedPathWithoutAltAndDisabledMinifier()
    {
        $context = new WpContext($this->basePath, 'https://example.com/assets');
        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('foo.css', MinifyResolver::createDisabled());

        static::assertSame('https://example.com/assets/foo.css', $resolved);
    }

    public function testResolveMinifiedPathWithoutAltAndEnabledMinifier()
    {
        $context = new WpContext($this->basePath, 'https://example.com/assets');
        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('foo.min.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/foo.min.css', $resolved);
    }

    public function testResolveMinifiedPathWithoutAltAndEnabledMinifierMinNotFound()
    {
        $context = new WpContext($this->basePath, 'https://example.com/assets');
        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('foo.min.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/foo.min.css', $resolved);
    }

    public function testResolveNotMinifiedPathWithAltAndEnabledMinifierPathNotFoundInMainDir()
    {
        $context = new WpContext(
            $this->basePath,
            'https://example.com/assets',
            $this->altBasePath,
            'https://example.com/alt/assets'
        );

        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('bar.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/alt/assets/bar.min.css', $resolved);
    }

    public function testResolveNotMinifiedPathWithAltAndEnabledMinifierPathFoundInMainDir()
    {
        $context = new WpContext(
            $this->basePath,
            'https://example.com/assets',
            $this->altBasePath,
            'https://example.com/alt/assets'
        );

        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('foo.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/foo.min.css', $resolved);
    }

    public function testResolveNotMinifiedPathWithAltAndDisabledMinifierPathFoundInMainDir()
    {
        $context = new WpContext(
            $this->basePath,
            'https://example.com/assets',
            $this->altBasePath,
            'https://example.com/alt/assets'
        );

        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('foo.css', MinifyResolver::createDisabled());

        static::assertSame('https://example.com/assets/foo.css', $resolved);
    }

    public function testResolveMinifiedPathWithAltAndEnabledMinifierPathFoundInMainDir()
    {
        $context = new WpContext(
            $this->basePath,
            'https://example.com/assets',
            $this->altBasePath,
            'https://example.com/alt/assets'
        );

        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('foo.min.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/foo.min.css', $resolved);
    }

    public function testResolveNotMinifiedPathWithAltAndDisabledMinifierPathNotFoundInMainDir()
    {
        $context = new WpContext(
            $this->basePath,
            'https://example.com/assets',
            $this->altBasePath,
            'https://example.com/alt/assets'
        );

        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('bar.css', MinifyResolver::createDisabled());

        static::assertSame('https://example.com/alt/assets/bar.css', $resolved);
    }

    public function testResolveMinifiedPathWithAltAndEnabledMinifierPathNotFoundAnywhere()
    {
        $context = new WpContext(
            $this->basePath,
            'https://example.com/assets',
            $this->altBasePath,
            'https://example.com/alt/assets'
        );

        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('xxx.css', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/xxx.css', $resolved);
    }

    public function testResolveNotMinifiedPathWithoutAltAndEnabledMinifierAndQuery()
    {
        $context = new WpContext($this->basePath, 'https://example.com/assets');
        $resolver = new DirectUrlResolver($context);

        $resolved = $resolver->resolve('foo.css?v=123', MinifyResolver::createEnabled());

        static::assertSame('https://example.com/assets/foo.min.css?v=123', $resolved);
    }
}
