<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit;

use Brain\Assets\Assets;
use Brain\Assets\Tests\AssetsTestCase;
use Brain\Monkey\Functions;

class ChildThemeAssetsTest extends AssetsTestCase
{
    private const THEME_PATH = '/wp-content/themes/parent';
    private const CHILD_PATH = '/wp-content/themes/child';
    private const THEME_ASSETS_PATH = self::THEME_PATH . '/assets';
    private const CHILD_ASSETS_PATH = self::CHILD_PATH;

    public function testFallbackUrls()
    {
        $assets = $this->createInstance()
            ->withJsFolder('/js')
            ->withCssFolder('/css')
            ->withFontsFolder('/font')
            ->withImagesFolder('/images')
            ->withVideosFolder('/video');

        $jsParts = $this->urlParts($assets->jsUrl('theme'));
        $cssChildParts = $this->urlParts($assets->cssUrl('child'));
        $cssParentParts = $this->urlParts($assets->cssUrl('theme'));
        $imageChildParts = $this->urlParts($assets->imgUrl('b.jpg'));
        $imageParentParts = $this->urlParts($assets->imgUrl('a.jpg'));

        static::assertSame(self::THEME_ASSETS_PATH . '/js/theme.min.js', $jsParts->path);
        static::assertSame('https', $jsParts->scheme);
        static::assertTrue(is_numeric($jsParts->ver));
        static::assertTrue((int)$jsParts->ver > mktime(0, 0, 0, 1, 1, 1970));

        static::assertSame(self::CHILD_ASSETS_PATH . '/css/child.min.css', $cssChildParts->path);
        static::assertSame('https', $cssChildParts->scheme);
        static::assertTrue(is_numeric($cssChildParts->ver));
        static::assertTrue((int)$cssChildParts->ver > mktime(0, 0, 0, 1, 1, 1970));

        static::assertSame(self::THEME_ASSETS_PATH . '/css/theme.min.css', $cssParentParts->path);
        static::assertSame('https', $cssParentParts->scheme);
        static::assertTrue(is_numeric($cssParentParts->ver));
        static::assertTrue((int)$cssParentParts->ver > mktime(0, 0, 0, 1, 1, 1970));

        static::assertSame(self::CHILD_ASSETS_PATH . '/images/b.jpg', $imageChildParts->path);
        static::assertSame('https', $imageChildParts->scheme);
        static::assertTrue(is_numeric($imageChildParts->ver));
        static::assertTrue((int)$imageChildParts->ver > mktime(0, 0, 0, 1, 1, 1970));

        static::assertSame(self::THEME_ASSETS_PATH . '/images/a.jpg', $imageParentParts->path);
        static::assertSame('https', $imageParentParts->scheme);
        static::assertTrue(is_numeric($imageParentParts->ver));
        static::assertTrue((int)$imageParentParts->ver > mktime(0, 0, 0, 1, 1, 1970));
    }

    public function testFromChildAbsoluteUrl()
    {
        $url = $this->createInstance()
            ->withCssFolder('/css')
            ->cssUrl('http://example.com' . self::CHILD_ASSETS_PATH . '/css/child.css');

        $urlParts = $this->urlParts($url);

        static::assertSame(self::CHILD_ASSETS_PATH . '/css/child.min.css', $urlParts->path);
        static::assertSame('https', $urlParts->scheme);
        static::assertTrue(is_numeric($urlParts->ver));
        static::assertTrue((int)$urlParts->ver > mktime(0, 0, 0, 1, 1, 1970));
    }

    public function testFromParentAbsoluteUrl()
    {
        $url = $this->createInstance()
            ->withJsFolder('/js')
            ->cssUrl('http://example.com' . self::THEME_ASSETS_PATH . '/js/theme.js');

        $urlParts = $this->urlParts($url);

        static::assertSame(self::THEME_ASSETS_PATH . '/js/theme.min.js', $urlParts->path);
        static::assertSame('https', $urlParts->scheme);
        static::assertTrue(is_numeric($urlParts->ver));
        static::assertTrue((int)$urlParts->ver > mktime(0, 0, 0, 1, 1, 1970));
    }

    public function testFromExternalAbsoluteUrl()
    {
        $extUrl = '//cdn.example.com' . self::THEME_ASSETS_PATH . '/js/theme.js';

        $url = $this->createInstance()->withJsFolder('/js')->jsUrl($extUrl);

        static::assertSame($extUrl, $url);
    }

    public function testFromExternalAbsoluteUrlWithoutForceSecure()
    {
        $extUrl = 'http://cdn.example.com' . self::THEME_ASSETS_PATH . '/js/theme.js';

        $url = $this->createInstance()
            ->dontForceSecureUrls()
            ->withJsFolder('/js')
            ->jsUrl($extUrl);

        static::assertSame($extUrl, $url);
    }

    /**
     * @return Assets
     */
    private function createInstance(): Assets
    {
        $themePath = getenv('FIXTURES_DIR') . '/themes/parent';
        $childPath = getenv('FIXTURES_DIR') . '/themes/child';

        Functions\when('get_template')
            ->justReturn('parent');
        Functions\when('get_stylesheet')
            ->justReturn('child');

        Functions\when('get_stylesheet_directory')
            ->justReturn($childPath);
        Functions\when('get_stylesheet_directory_uri')
            ->justReturn('http://example.com' . self::CHILD_PATH);

        Functions\when('get_template_directory')
            ->justReturn($themePath);
        Functions\when('get_template_directory_uri')
            ->justReturn('http://example.com' . self::THEME_PATH);

        return Assets::forChildTheme('/', '/assets');
    }
}
