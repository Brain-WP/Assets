<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit\Context;

use Brain\Assets\Context\WpContext;
use Brain\Assets\Tests\TestCase;
use Brain\Monkey\Functions;

class WpContextTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testDebugViaScriptDebug()
    {
        define('SCRIPT_DEBUG', true);
        define('WP_DEBUG', false);

        $context = new WpContext(__DIR__, 'https://example.com');

        static::assertTrue($context->isDebug());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDebugViaWpDebug()
    {
        define('WP_DEBUG', true);

        $context = new WpContext(__DIR__, 'https://example.com');

        static::assertTrue($context->isDebug());
    }

    /**
     * @runInSeparateProcess
     */
    public function testNoDebugViaScriptDebug()
    {
        define('SCRIPT_DEBUG', false);
        define('WP_DEBUG', true);

        $context = new WpContext(__DIR__, 'https://example.com');

        static::assertFalse($context->isDebug());
    }

    public function testForcedDebug()
    {
        $debug = WpContext::createWithDebug(__DIR__, 'https://example.com');
        $noDebug = WpContext::createWithNoDebug(__DIR__, 'https://example.com');

        static::assertTrue($debug->isDebug());
        static::assertFalse($noDebug->isDebug());
    }

    public function testBasePaths()
    {
        $context = new WpContext(__DIR__, 'https://example.com');

        static::assertSame('https://example.com/', $context->baseUrl());
        static::assertSame(__DIR__ . '/', $context->basePath());
        static::assertFalse($context->hasAlternative());
    }

    public function testAltPaths()
    {
        $context = new WpContext(
            __DIR__,
            'https://example.com',
            __DIR__ . '/foo/bar/',
            'https://example.com/foo/'
        );

        static::assertSame('https://example.com/', $context->baseUrl());
        static::assertSame(__DIR__ . '/', $context->basePath());
        static::assertSame('https://example.com/foo/', $context->altBaseUrl());
        static::assertSame(__DIR__ . '/foo/bar/', $context->altBasePath());
        static::assertTrue($context->hasAlternative());
    }

    public function testHasAlternativeRequireBoth()
    {
        $context1 = new WpContext(
            __DIR__,
            'https://example.com',
            null,
            'https://example.com/foo/'
        );

        $context2 = new WpContext(
            __DIR__,
            'https://example.com',
            __DIR__ . '/foo/bar/'
        );

        static::assertFalse($context1->hasAlternative());
        static::assertFalse($context2->hasAlternative());
    }

    public function testIsSecure()
    {
        Functions\when('is_ssl')->justReturn(true);

        $context = new WpContext(__DIR__, 'https://example.com');

        static::assertTrue($context->isSecure());
    }

    public function testIsNotSecure()
    {
        Functions\when('is_ssl')->justReturn('');

        $context = new WpContext(__DIR__, 'https://example.com');

        static::assertFalse($context->isSecure());
    }
}
