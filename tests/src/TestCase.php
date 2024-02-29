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

namespace Brain\Assets\Tests;

use Brain\Assets\Context\WpContext;
use Brain\Assets\Utils\PathFinder;
use Brain\Assets\Version\Version;
use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var non-falsy-string */
    protected string $baseUrl = 'https://example.com/assets';
    /** @var non-falsy-string */
    protected string $altBaseUrl = 'https://example.com/alt/assets';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Monkey\Functions\stubs(
            [
                'is_ssl' => true,
                'esc_attr',
                'wp_normalize_path' => static function (string $string): string {
                    return str_replace('\\', '/', $string);
                },
                'sanitize_key' => static function (string $string): string {
                    return strtolower(preg_replace('~[^a-z0-9_-]~i', '', $string));
                },
            ]
        );

        Monkey\Functions\when('add_query_arg')->alias(
            static function (string $key, string $value, string $url): string {
                return "{$url}?{$key}={$value}";
            }
        );

        Monkey\Functions\when('set_url_scheme')->alias(
            static function (string $url, ?string $scheme = null): string {
                $scheme === null and $scheme = is_ssl() ? 'https' : 'http';
                if (str_starts_with($url, '//')) {
                    return "{$scheme}:{$url}";
                }

                return preg_replace('~^\w+://~', "{$scheme}://", $url);
            }
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();

        $this->baseUrl = 'https://example.com/assets';
        $this->altBaseUrl = 'https://example.com/alt/assets';
    }

    /**
     * @param bool $useAlt
     * @param bool|null $debug
     * @param string|null $name
     * @return WpContext
     */
    protected function factoryContext(bool $useAlt, ?bool $debug, ?string $name = null): WpContext
    {
        $basePath = static::fixturesPath();
        $altBasePath = static::fixturesPath('/alt');

        $baseUrl = $this->baseUrl;
        $altBaseUrl = $this->altBaseUrl;
        $name ??= 'test';

        /**
         * @var non-falsy-string $basePath
         * @var non-falsy-string $altBasePath
         */
        return $useAlt
            ? WpContext::new($name, $basePath, $baseUrl, $altBasePath, $altBaseUrl, isDebug: $debug)
            : WpContext::new($name, $basePath, $baseUrl, isDebug: $debug);
    }

    /**
     * @param bool $useAlt
     * @param bool $debug
     * @return PathFinder
     */
    protected function factoryPathFinder(bool $useAlt, bool $debug): PathFinder
    {
        return PathFinder::new($this->factoryContext($useAlt, $debug));
    }

    /**
     * @param string $url
     * @return array{path: string|null, scheme: string|null, ver: string|null}
     */
    protected function urlParts(string $url): array
    {
        $path = parse_url($url, PHP_URL_PATH);
        is_string($path) or $path = null;

        $scheme = parse_url($url, PHP_URL_SCHEME);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $vars);
        is_string($scheme) or $scheme = null;

        $ver = $vars[Version::QUERY_VAR] ?? '';
        is_string($ver) or $ver = null;

        return compact('path', 'scheme', 'ver');
    }

    /**
     * @param mixed $microtime
     * @return void
     */
    protected function assertMicrotime(mixed $microtime): void
    {
        static::assertTrue(is_string($microtime));
        $parts = explode('-', $microtime);
        static::assertTrue(count($parts) === 2);

        [$micro, $seconds] = $parts;
        static::assertTrue(is_numeric($micro));
        static::assertTrue(is_numeric($seconds));
        static::assertTrue($seconds > 946681200);
        static::assertTrue($micro < 1);
    }

    /**
     * @param mixed $timestamp
     * @return void
     */
    protected function assertTimestamp(mixed $timestamp): void
    {
        static::assertTrue(is_numeric($timestamp));
        $timestamp = (int) $timestamp;

        static::assertTrue($timestamp <= time());
        static::assertTrue($timestamp > 946681200); // 2000-01-01 00:00:00
    }

    /**
     * @param string $relative
     * @return non-falsy-string
     */
    protected static function fixturesPath(string $relative = ''): string
    {
        $basePath = getenv('FIXTURES_DIR');
        assert(is_string($basePath) && is_dir($basePath), "Ensure FIXTURES_DIR is configured.");

        $path = rtrim($basePath, '/');
        if ($relative !== '') {
            $path .= '/' . ltrim($relative, '/');
        }
        /** @var non-falsy-string */
        return str_replace('\\', '/', $path);
    }
}
