<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit;

use Brain\Assets\Assets;
use Brain\Assets\Tests\AssetsTestCase;
use Brain\Monkey\Functions;

class ThemeAssetsTest extends AssetsTestCase
{
    private const THEME_PATH = '/wp-content/themes/parent';
    private const ASSETS_PATH = self::THEME_PATH . '/assets';

    public function testJsUrlWithMinAndVersion()
    {
        $url = $this->createInstance()
            ->withJsFolder('/js')
            ->jsUrl('theme');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/js/theme.min.js', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertTrue(is_numeric($parts->ver));
        static::assertTrue((int)$parts->ver > mktime(0, 0, 0, 1, 1, 1970));
    }

    public function testJsUrlWithMinAndEmbeddedVersion()
    {
        $url = $this->createInstance()
            ->withJsFolder('/js')
            ->jsUrl('theme?v=1');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/js/theme.min.js', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertSame('1', $parts->ver);
    }

    public function testRawJsUrlWithMinAndVersion()
    {
        $url = $this->createInstance()
            ->withJsFolder('/js')
            ->rawJsUrl('theme');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/js/theme.min.js', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertSame('', $parts->ver);
    }

    public function testMediaUrls()
    {
        $assets = $this->createInstance()
            ->withImagesFolder('/images')
            ->withVideosFolder('/video')
            ->withFontsFolder('/font');

        $imageParts = $this->urlParts($assets->imgUrl('a.jpg'));
        $videoParts = $this->urlParts($assets->videoUrl('v.mp4'));
        $fontsParts = $this->urlParts($assets->fontsUrl('font.ttf'));

        static::assertSame(self::ASSETS_PATH . '/images/a.jpg', $imageParts->path);
        static::assertSame('https', $imageParts->scheme);
        static::assertTrue(is_numeric($imageParts->ver));
        static::assertTrue((int)$imageParts->ver > mktime(0, 0, 0, 1, 1, 1970));

        static::assertSame(self::ASSETS_PATH . '/video/v.mp4', $videoParts->path);
        static::assertSame('https', $videoParts->scheme);
        static::assertTrue(is_numeric($videoParts->ver));
        static::assertTrue((int)$videoParts->ver > mktime(0, 0, 0, 1, 1, 1970));

        static::assertSame(self::ASSETS_PATH . '/font/font.ttf', $fontsParts->path);
        static::assertSame('https', $fontsParts->scheme);
        static::assertTrue(is_numeric($fontsParts->ver));
        static::assertTrue((int)$fontsParts->ver > mktime(0, 0, 0, 1, 1, 1970));
    }

    public function testRawMediaUrls()
    {
        $assets = $this->createInstance()
            ->withImagesFolder('/images')
            ->withVideosFolder('/video')
            ->withFontsFolder('/font');

        $imageParts = $this->urlParts($assets->rawImgUrl('a.jpg'));
        $videoParts = $this->urlParts($assets->rawVideoUrl('v.mp4'));
        $fontsParts = $this->urlParts($assets->rawFontsUrl('font.ttf'));

        static::assertSame(self::ASSETS_PATH . '/images/a.jpg', $imageParts->path);
        static::assertSame('https', $imageParts->scheme);
        static::assertSame('', $imageParts->ver);

        static::assertSame(self::ASSETS_PATH . '/video/v.mp4', $videoParts->path);
        static::assertSame('https', $videoParts->scheme);
        static::assertSame('', $videoParts->ver);

        static::assertSame(self::ASSETS_PATH . '/font/font.ttf', $fontsParts->path);
        static::assertSame('https', $fontsParts->scheme);
        static::assertSame('', $fontsParts->ver);
    }

    public function testImageUrlWithDebug()
    {
        $now = time();

        $url = $this->createInstance()
            ->withImagesFolder('/images')
            ->forceDebug()
            ->imgUrl('a.jpg');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/images/a.jpg', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertTrue(is_numeric($parts->ver));
        static::assertTrue(($now - (int)$parts->ver) < 5);
    }

    public function testJsUrlWithDebug()
    {
        $now = time();

        $url = $this->createInstance()
            ->withJsFolder('/js')
            ->forceDebug()
            ->jsUrl('theme');

        $parts = $this->urlParts($url);

        static::assertSame(self::ASSETS_PATH . '/js/theme.js', $parts->path);
        static::assertSame('https', $parts->scheme);
        static::assertTrue(is_numeric($parts->ver));
        static::assertTrue(($now - (int)$parts->ver) < 5);
    }

    public function testEnqueueScript()
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with('parent-theme', \Mockery::type('string'), ['jquery'], null, true)
            ->andReturnUsing(
                function (string $handle, string $url): void {
                    $parts = $this->urlParts($url);

                    static::assertSame(self::ASSETS_PATH . '/js/theme.min.js', $parts->path);
                    static::assertSame('https', $parts->scheme);
                    static::assertTrue(is_numeric($parts->ver));
                    static::assertTrue((int)$parts->ver > mktime(0, 0, 0, 1, 1, 1970));
                }
            );

        Functions\expect('wp_localize_script')
            ->once()
            ->with('parent-theme', 'ThemeData', ['foo' => 'bar']);

        $this->createInstance()
            ->withJsFolder('/js')
            ->enqueueScript('theme', ['jquery'])
            ->localize('ThemeData', ['foo' => 'bar']);
    }

    /**
     * @return Assets
     */
    private function createInstance(): Assets
    {
        $themePath = getenv('FIXTURES_DIR') . '/themes/parent';

        Functions\when('get_template')
            ->justReturn('parent');
        Functions\when('get_template_directory')
            ->justReturn($themePath);
        Functions\when('get_template_directory_uri')
            ->justReturn('http://example.com' . self::THEME_PATH);

        return Assets::forTheme('/assets');
    }
}
