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

namespace Brain\Assets\Tests\Unit\Context;

use Brain\Assets\Context\WpContext;
use Brain\Assets\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * @runTestsInSeparateProcesses
 */
class WpContextTest extends TestCase
{
    /**
     * @test
     */
    public function testDebugViaScriptDebug(): void
    {
        define('SCRIPT_DEBUG', true);
        define('WP_DEBUG', false);

        $context = WpContext::new('test', __DIR__, 'https://example.com');

        static::assertTrue($context->isDebug());
    }

    /**
     * @test
     */
    public function testDebugViaWpDebug(): void
    {
        define('WP_DEBUG', true);

        $context = WpContext::new('test', __DIR__, 'https://example.com');

        static::assertTrue($context->isDebug());
    }

    /**
     * @test
     */
    public function testNoDebugViaScriptDebug(): void
    {
        define('SCRIPT_DEBUG', false);
        define('WP_DEBUG', true);

        $context = WpContext::new('test', __DIR__, 'https://example.com');

        static::assertFalse($context->isDebug());
    }

    /**
     * @test
     */
    public function testForcedDebug(): void
    {
        $debug = WpContext::newWithDebug('test1', __DIR__, 'https://example.com');
        $noDebug = WpContext::newWithNoDebug('test2', __DIR__, 'https://example.com');

        static::assertTrue($debug->isDebug());
        static::assertFalse($noDebug->isDebug());
    }

    /**
     * @test
     */
    public function testBasePaths(): void
    {
        $context = WpContext::new('test', __DIR__, 'https://example.com');

        static::assertSame('https://example.com/', $context->baseUrl());
        static::assertSame(str_replace('\\', '/', __DIR__) . '/', $context->basePath());
        static::assertFalse($context->hasAlternative());
    }

    /**
     * @test
     */
    public function testAltPaths(): void
    {
        $context = WpContext::new(
            'test',
            __DIR__,
            'https://example.com',
            __DIR__ . '/foo/bar/',
            'https://example.com/foo/'
        );

        static::assertSame('https://example.com/', $context->baseUrl());
        static::assertSame(str_replace('\\', '/', __DIR__) . '/', $context->basePath());
        static::assertSame('https://example.com/foo/', $context->altBaseUrl());
        static::assertSame(str_replace('\\', '/', __DIR__) . '/foo/bar/', $context->altBasePath());
        static::assertTrue($context->hasAlternative());
    }

    /**
     * @test
     */
    public function testHasAlternativeRequireBoth(): void
    {
        $context1 = WpContext::new(
            'test',
            __DIR__,
            'https://example.com',
            null,
            'https://example.com/foo/'
        );

        $context2 = WpContext::new(
            __DIR__,
            'https://example.com',
            __DIR__ . '/foo/bar/'
        );

        static::assertFalse($context1->hasAlternative());
        static::assertFalse($context2->hasAlternative());
    }

    /**
     * @test
     */
    public function testIsSecure(): void
    {
        $context = WpContext::new('test', __DIR__, 'https://example.com');

        static::assertTrue($context->isSecure());
    }

    /**
     * @test
     */
    public function testIsNotSecure(): void
    {
        Functions\when('is_ssl')->justReturn('');

        $context = WpContext::new('test', __DIR__, 'https://example.com');

        static::assertFalse($context->isSecure());
    }
}
