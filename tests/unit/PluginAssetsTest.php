<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit;

use Brain\Assets\Assets;
use Brain\Assets\Tests\AssetsTestCase;
use Brain\Monkey\Functions;

class PluginAssetsTest extends AssetsTestCase
{
    private const ASSETS_PATH = '/wp-content/plugins/foo/assets';
    private const ASSETS_URL = 'http://example.com' . self::ASSETS_PATH;

    public function testCssUrlWithMinAndVersion()
    {
        $url = $this->createInstance()
            ->withCssFolder('/styles')
            ->cssUrl('foo');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/styles/foo.min.css', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertTrue(is_numeric($parts->ver));
        static::assertTrue((int)$parts->ver > mktime(0, 0, 0, 1, 1, 1970));
    }

    public function testCssUrlWithoutMinWithVersion()
    {
        $url = $this->createInstance()
            ->withCssFolder('/styles')
            ->dontTryMinUrls()
            ->cssUrl('foo');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/styles/foo.css', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertTrue(is_numeric($parts->ver));
        static::assertTrue((int)$parts->ver > mktime(0, 0, 0, 1, 1, 1970));
    }

    public function testCssUrlWithMinWithoutVersion()
    {
        $url = $this->createInstance()
            ->withCssFolder('/styles')
            ->dontAddVersion()
            ->cssUrl('foo');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/styles/foo.min.css', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertSame('', $parts->ver);
    }

    public function testCssUrlWithMinWithVersionButFileAlreadyHas()
    {
        $url = $this->createInstance()
            ->withCssFolder('/styles')
            ->cssUrl('foo.min.css?v=1.0');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/styles/foo.min.css', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertSame('1.0', $parts->ver);
    }

    public function testRawCssUrlWithMin()
    {
        $url = $this->createInstance()
            ->withCssFolder('/styles')
            ->rawCssUrl('foo');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/styles/foo.min.css', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertSame('', $parts->ver);
    }

    public function testRawCssUrlWithMinWithDisabledAutoHttps()
    {
        $url = $this->createInstance()
            ->withCssFolder('/styles')
            ->dontForceSecureUrls()
            ->rawCssUrl('foo');

        static::assertSame(self::ASSETS_URL . '/styles/foo.min.css', $url);
    }

    public function testUseManifest()
    {
        $baseUrl = 'example.com/wp-content/plugins/foo';

        $url = $this->createInstance()
            ->useManifest(getenv('FIXTURES_DIR') . '/plugins/foo', "http://{$baseUrl}")
            ->dontAddVersion()
            ->withCssFolder('assets/styles')
            ->rawCssUrl('main');

        static::assertSame("https://{$baseUrl}/assets/styles/main.abcde.css", $url);
    }

    public function testForManifest()
    {
        $baseUrl = 'https://example.com/wp-content/plugins/foo';
        $manifestPath = getenv('FIXTURES_DIR') . '/plugins/foo/manifest.json';

        $url = Assets::forManifest('lib', $manifestPath, $baseUrl)
            ->withCssFolder('assets/styles')
            ->rawCssUrl('main');

        static::assertSame("{$baseUrl}/assets/styles/main.abcde.css", $url);
    }

    public function testEnqueueCss()
    {
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('foo-admin', \Mockery::type('string'), ['jquery'], null, 'all')
            ->andReturnUsing(
                function (string $handle, string $url): void {
                    $parts = $this->urlParts($url);

                    static::assertSame(self::ASSETS_PATH . '/styles/admin.min.css', $parts->path);
                    static::assertSame('https', $parts->scheme);
                    static::assertTrue(is_numeric($parts->ver));
                    static::assertTrue((int)$parts->ver > mktime(0, 0, 0, 1, 1, 1970));
                }
            );

        $this->createInstance()
            ->withCssFolder('/styles')
            ->enqueueStyle('admin', ['jquery']);
    }

    public function testEnqueueCssNoVerNoMin()
    {
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('foo-admin', \Mockery::type('string'), ['jquery'], null, 'all')
            ->andReturnUsing(
                function (string $handle, string $url): void {
                    $parts = $this->urlParts($url);

                    static::assertSame(self::ASSETS_PATH . '/styles/admin.css', $parts->path);
                    static::assertSame('https', $parts->scheme);
                    static::assertSame('', $parts->ver);
                }
            );

        $this->createInstance()
            ->dontAddVersion()
            ->dontTryMinUrls()
            ->withCssFolder('/styles')
            ->enqueueStyle('admin', ['jquery']);
    }

    public function testEnqueueCssMinNotFound()
    {
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('foo-no-min', \Mockery::type('string'), [], null, 'screen')
            ->andReturnUsing(
                function (string $handle, string $url): void {
                    $parts = $this->urlParts($url);

                    static::assertSame(self::ASSETS_PATH . '/styles/no-min.css', $parts->path);
                    static::assertSame('https', $parts->scheme);
                    static::assertSame('', $parts->ver);
                }
            );

        $this->createInstance()
            ->dontAddVersion()
            ->withCssFolder('/styles')
            ->enqueueStyle('no-min', [], 'screen');
    }

    /**
     * @return Assets
     */
    private function createInstance(): Assets
    {
        $pluginFilePath = getenv('FIXTURES_DIR') . '/plugins/foo/plugin.php';

        Functions\expect('plugin_basename')
            ->with($pluginFilePath)
            ->andReturn('foo/plugin.php');

        Functions\expect('plugins_url')
            ->with('/assets/', $pluginFilePath)
            ->andReturn(self::ASSETS_URL);

        return Assets::forPlugin($pluginFilePath, 'assets/');
    }
}
