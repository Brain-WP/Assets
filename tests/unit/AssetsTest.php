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

namespace Brain\Assets\Tests\Unit;

use Brain\Assets\Assets;
use Brain\Assets\Enqueue\Enqueue;
use Brain\Assets\Tests\AssetsTestCase;
use Brain\Assets\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * @ runTestsInSeparateProcesses
 */
class AssetsTest extends TestCase
{
    /**
     * @test
     */
    public function testPluginCssUrlWithMinAndVersion(): void
    {
        $assetsPath = '/wp-content/plugins/foo/assets';
        $url = $this->factoryPluginAssets()
            ->tryMinUrls()
            ->withCssFolder('/styles')
            ->cssUrl('foo');

        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/styles/foo.min.css", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        $this->assertTimestamp($parts['ver']);
    }

    /**
     * @test
     */
    public function testPluginCssUrlWithoutMinWithVersion(): void
    {
        $assetsPath = '/wp-content/plugins/foo/assets';
        $url = $this->factoryPluginAssets()
            ->withCssFolder('/styles')
            ->forceDebug()
            ->cssUrl('foo');

        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/styles/foo.css", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        $this->assertMicrotime($parts['ver']);
    }

    /**
     * @test
     */
    public function testPluginCssUrlWithMinWithoutVersion(): void
    {
        $assetsPath = '/wp-content/plugins/foo/assets';
        $url = $this->factoryPluginAssets()
            ->tryMinUrls()
            ->withCssFolder('/styles')
            ->dontAddVersion()
            ->cssUrl('foo');

        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/styles/foo.min.css", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        static::assertSame('', $parts['ver']);
    }

    /**
     * @test
     */
    public function testPluginCssUrlWithMinWithVersionButFileAlreadyHas(): void
    {
        $assetsPath = '/wp-content/plugins/foo/assets';
        $url = $this->factoryPluginAssets()
            ->withCssFolder('/styles')
            ->cssUrl('foo.min.css?v=1.0');

        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/styles/foo.min.css", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        static::assertSame('1.0', $parts['ver']);
    }

    /**
     * @test
     */
    public function testPluginRawCssUrlWithMin(): void
    {
        $assetsPath = '/wp-content/plugins/foo/assets';
        $url = $this->factoryPluginAssets()
            ->tryMinUrls()
            ->withCssFolder('/styles')
            ->rawCssUrl('foo');
        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/styles/foo.min.css", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        static::assertSame('', $parts['ver']);
    }

    /**
     * @test
     */
    public function testPluginRawCssUrlWithMinWithDisabledAutoHttps(): void
    {
        $assetsUrl = 'http://example.com/wp-content/plugins/foo/assets';

        $url = $this->factoryPluginAssets()
            ->withCssFolder('/styles')
            ->dontForceSecureUrls()
            ->tryMinUrls()
            ->rawCssUrl('foo');

        static::assertSame("{$assetsUrl}/styles/foo.min.css", $url);
    }

    /**
     * @test
     */
    public function testPluginForManifest(): void
    {
        $baseUrl = 'https://example.com/wp-content/plugins/foo';
        $manifestPath = static::fixturesPath('/plugins/foo/manifest.json');

        $url = Assets::forManifest('lib', $manifestPath, $baseUrl)
            ->withCssFolder('assets/styles')
            ->rawCssUrl('main');

        static::assertSame("{$baseUrl}/assets/styles/main.abcde.css", $url);
    }

    /**
     * @test
     */
    public function testPluginEnqueueCss(): void
    {
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('foo-admin', \Mockery::type('string'), ['jquery'], null, 'all')
            ->andReturnUsing(
                function (string $handle, string $url): void {
                    $assetsPath = '/wp-content/plugins/foo/assets';
                    $parts = $this->urlParts($url);

                    static::assertSame("{$assetsPath}/styles/admin.min.css", $parts['path']);
                    static::assertSame('https', $parts['scheme']);
                    $this->assertTimestamp($parts['ver']);
                }
            );

        $this->factoryPluginAssets()
            ->tryMinUrls()
            ->withCssFolder('/styles')
            ->enqueueStyle('admin', ['jquery']);
    }

    /**
     * @test
     */
    public function testPluginEnqueueCssNoVerNoMin(): void
    {
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('foo-admin', \Mockery::type('string'), ['jquery'], null, 'all')
            ->andReturnUsing(
                function (string $handle, string $url): void {
                    $parts = $this->urlParts($url);
                    $assetsPath = '/wp-content/plugins/foo/assets';
                    static::assertSame("{$assetsPath}/styles/admin.css", $parts['path']);
                    static::assertSame('https', $parts['scheme']);
                    static::assertSame('', $parts['ver']);
                }
            );

        $this->factoryPluginAssets()
            ->dontAddVersion()
            ->dontTryMinUrls()
            ->withCssFolder('/styles')
            ->enqueueStyle('admin', ['jquery']);
    }

    /**
     * @test
     */
    public function testPluginEnqueueCssMinNotFound(): void
    {
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('foo-no-min', \Mockery::type('string'), [], null, 'screen')
            ->andReturnUsing(
                function (string $handle, string $url): void {
                    $parts = $this->urlParts($url);
                    $assetsPath = '/wp-content/plugins/foo/assets';
                    static::assertSame("{$assetsPath}/styles/no-min.css", $parts['path']);
                    static::assertSame('https', $parts['scheme']);
                    static::assertSame('', $parts['ver']);
                }
            );

        $this->factoryPluginAssets()
            ->dontAddVersion()
            ->withCssFolder('/styles')
            ->enqueueStyle('no-min', [], 'screen');
    }

    /**
     * @test
     */
    public function testThemeJsUrlWithMinAndVersion(): void
    {
        $assetsPath = '/wp-content/themes/parent/assets';
        $url = $this->factoryThemeAssets()->withJsFolder('/js')->jsUrl('theme');
        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/js/theme.min.js", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        $this->assertTimestamp($parts['ver']);
    }

    /**
     * @test
     */
    public function testThemeJsUrlWithMinAndEmbeddedVersion(): void
    {
        $assetsPath = '/wp-content/themes/parent/assets';
        $url = $this->factoryThemeAssets()->withJsFolder('/js')->jsUrl('theme?v=1');
        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/js/theme.min.js", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        static::assertSame('1', $parts['ver']);
    }

    /**
     * @test
     */
    public function testThemeRawJsUrlWithMinAndVersion(): void
    {
        $assetsPath = '/wp-content/themes/parent/assets';
        $url = $this->factoryThemeAssets()->withJsFolder('/js')->rawJsUrl('theme');
        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/js/theme.min.js", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        static::assertSame('', $parts['ver']);
    }

    /**
     * @test
     */
    public function testThemeMediaUrls(): void
    {
        $assetsPath = '/wp-content/themes/parent/assets';
        $assets = $this->factoryThemeAssets()
            ->withImagesFolder('/images')
            ->withVideosFolder('/video')
            ->withFontsFolder('/font');

        $imageParts = $this->urlParts($assets->imgUrl('a.jpg'));
        $videoParts = $this->urlParts($assets->videoUrl('v.mp4'));
        $fontsParts = $this->urlParts($assets->fontsUrl('font.ttf'));

        static::assertSame("{$assetsPath}/images/a.jpg", $imageParts['path']);
        static::assertSame('https', $imageParts['scheme']);
        $this->assertTimestamp($imageParts['ver']);

        static::assertSame("{$assetsPath}/video/v.mp4", $videoParts['path']);
        static::assertSame('https', $videoParts['scheme']);
        $this->assertTimestamp($videoParts['ver']);

        static::assertSame("{$assetsPath}/font/font.ttf", $fontsParts['path']);
        static::assertSame('https', $fontsParts['scheme']);
        $this->assertTimestamp($fontsParts['ver']);
    }

    /**
     * @test
     */
    public function testThemeRawMediaUrls(): void
    {
        $assetsPath = '/wp-content/themes/parent/assets';
        $assets = $this->factoryThemeAssets()
            ->withImagesFolder('/images')
            ->withVideosFolder('/video')
            ->withFontsFolder('/font');

        $imageParts = $this->urlParts($assets->rawImgUrl('a.jpg'));
        $videoParts = $this->urlParts($assets->rawVideoUrl('v.mp4'));
        $fontsParts = $this->urlParts($assets->rawFontsUrl('font.ttf'));

        static::assertSame("{$assetsPath}/images/a.jpg", $imageParts['path']);
        static::assertSame('https', $imageParts['scheme']);
        static::assertSame('', $imageParts['ver']);

        static::assertSame("{$assetsPath}/video/v.mp4", $videoParts['path']);
        static::assertSame('https', $videoParts['scheme']);
        static::assertSame('', $videoParts['ver']);

        static::assertSame("{$assetsPath}/font/font.ttf", $fontsParts['path']);
        static::assertSame('https', $fontsParts['scheme']);
        static::assertSame('', $fontsParts['ver']);
    }

    /**
     * @test
     */
    public function testThemeImageUrlWithDebug(): void
    {
        $assetsPath = '/wp-content/themes/parent/assets';
        $url = $this->factoryThemeAssets()
            ->withImagesFolder('/images')
            ->forceDebug()
            ->imgUrl('a.jpg');

        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/images/a.jpg", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        $this->assertMicrotime($parts['ver']);
    }

    /**
     * @test
     */
    public function testThemeJsUrlWithDebug(): void
    {
        $assetsPath = '/wp-content/themes/parent/assets';
        $url = $this->factoryThemeAssets()
            ->withJsFolder('/js')
            ->forceDebug()
            ->jsUrl('theme');

        $parts = $this->urlParts($url);

        static::assertSame("{$assetsPath}/js/theme.js", $parts['path']);
        static::assertSame('https', $parts['scheme']);
        $this->assertMicrotime($parts['ver']);
    }

    /**
     * @test
     */
    public function testThemeEnqueueScript(): void
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with('parent-theme', \Mockery::type('string'), ['jquery'], null, true)
            ->andReturnUsing(
                function (string $handle, string $url): void {
                    $parts = $this->urlParts($url);
                    $assetsPath = '/wp-content/themes/parent/assets';
                    static::assertSame("{$assetsPath}/js/theme.min.js", $parts['path']);
                    static::assertSame('https', $parts['scheme']);
                    $this->assertTimestamp($parts['ver']);
                }
            );

        Functions\expect('wp_localize_script')
            ->once()
            ->with('parent-theme', 'ThemeData', ['foo' => 'bar']);

        $this->factoryThemeAssets()
            ->withJsFolder('/js')
            ->enqueueScript('theme', ['jquery'])
            ->localize('ThemeData', ['foo' => 'bar']);
    }

    /**
     * @test
     */
    public function testChildThemeFallbackUrls(): void
    {
        $assets = $this->factoryChildThemeAssets()
            ->tryMinUrls()
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

        $themeAssetsPath = '/wp-content/themes/parent/assets';
        $childAssetsPath = '/wp-content/themes/child';

        static::assertSame("{$themeAssetsPath}/js/theme.min.js", $jsParts['path']);
        static::assertSame('https', $jsParts['scheme']);
        $this->assertTimestamp($jsParts['ver']);

        static::assertSame("{$childAssetsPath}/css/child.min.css", $cssChildParts['path']);
        static::assertSame('https', $cssChildParts['scheme']);
        $this->assertTimestamp($cssChildParts['ver']);

        static::assertSame("{$themeAssetsPath}/css/theme.min.css", $cssParentParts['path']);
        static::assertSame('https', $cssParentParts['scheme']);
        $this->assertTimestamp($cssParentParts['ver']);

        static::assertSame("{$childAssetsPath}/images/b.jpg", $imageChildParts['path']);
        static::assertSame('https', $imageChildParts['scheme']);
        $this->assertTimestamp($imageChildParts['ver']);

        static::assertSame("{$themeAssetsPath}/images/a.jpg", $imageParentParts['path']);
        static::assertSame('https', $imageParentParts['scheme']);
        $this->assertTimestamp($imageParentParts['ver']);
    }

    /**
     * @test
     */
    public function testChildThemeFromChildAbsoluteUrl(): void
    {
        $childAssetsPath = '/wp-content/themes/child';

        $assets = $this->factoryChildThemeAssets()->tryMinUrls()->withCssFolder('/css');

        $url = $assets->cssUrl("http://example.com/{$childAssetsPath}/css/child.css");
        $rawUrl = $assets->rawCssUrl("http://example.com/{$childAssetsPath}/css/child");

        $urlParts = $this->urlParts($url);
        $rawUrlParts = $this->urlParts($rawUrl);

        static::assertSame("{$childAssetsPath}/css/child.min.css", $urlParts['path']);
        static::assertSame('https', $urlParts['scheme']);
        $this->assertTimestamp($urlParts['ver']);

        static::assertSame("{$childAssetsPath}/css/child.min.css", $rawUrlParts['path']);
        static::assertSame('https', $rawUrlParts['scheme']);
        static::assertSame('', $rawUrlParts['ver']);
    }

    /**
     * @test
     */
    public function testChildThemeFromChildAbsoluteUrlNoVersion(): void
    {
        $childAssetsPath = '/wp-content/themes/child';
        $url = $this->factoryChildThemeAssets()
            ->tryMinUrls()
            ->dontAddVersion()
            ->withCssFolder('/css')
            ->cssUrl("http://example.com/{$childAssetsPath}/css/child.css");

        $urlParts = $this->urlParts($url);

        static::assertSame("{$childAssetsPath}/css/child.min.css", $urlParts['path']);
        static::assertSame('https', $urlParts['scheme']);
        static::assertSame('', $urlParts['ver']);
    }

    /**
     * @test
     */
    public function testChildThemeFromParentAbsoluteUrl(): void
    {
        $themeAssetsPath = '/wp-content/themes/parent/assets';
        $url = $this->factoryChildThemeAssets()
            ->tryMinUrls()
            ->withJsFolder('/js')
            ->jsUrl("http://example.com/{$themeAssetsPath}/js/theme.js");

        $urlParts = $this->urlParts($url);

        static::assertSame("{$themeAssetsPath}/js/theme.min.js", $urlParts['path']);
        static::assertSame('https', $urlParts['scheme']);
        $this->assertTimestamp($urlParts['ver']);
    }

    /**
     * @return void
     */
    public function testChildThemeFromExternalAbsoluteUrl(): void
    {
        $themeAssetsPath = 'wp-content/themes/parent/assets';
        $extUrl = "//cdn.example.com/{$themeAssetsPath}/js/theme.js";

        $url = $this->factoryChildThemeAssets()->withJsFolder('/js')->jsUrl($extUrl);

        static::assertSame($extUrl, $url);
    }

    /**
     * @return void
     */
    public function testChildThemeFromExternalAbsoluteUrlWithoutForceSecure(): void
    {
        $themeAssetsPath = 'wp-content/themes/parent/assets';
        $extUrl = "http://cdn.example.com/{$themeAssetsPath}/js/theme.js";

        $url = $this->factoryChildThemeAssets()
            ->dontForceSecureUrls()
            ->withJsFolder('/js')
            ->jsUrl($extUrl);

        static::assertSame($extUrl, $url);
    }

    /**
     * @test
     */
    public function testLibraryAutomaticManifest(): void
    {
        $assets = $this->factoryLibraryAssets()->dontAddVersion();

        $imgUrl = $assets->assetUrl('img.png');
        $cssUrl = $assets->assetUrl('foo.css');

        static::assertSame("{$this->baseUrl}/img.png", $imgUrl);
        static::assertSame("{$this->baseUrl}/foo.abcde.css", $cssUrl);
    }

    /**
     * @test
     */
    public function testLibraryEnqueue(): void
    {
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('foo', "{$this->baseUrl}/foo.abcde.css", [], null, 'screen');

        $this->factoryLibraryAssets()
            ->disableHandlePrefix()
            ->dontAddVersion()
            ->enqueueStyle('foo', [], 'screen');
    }

    /**
     * @test
     */
    public function testLibraryEnqueueFromDepInfo(): void
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'lib-some-script',
                "{$this->baseUrl}/some-script.js?v=a29c9d677e174811e603",
                ['wp-api-fetch'],
                null,
                ['in_footer' => true]
            );

        $this->factoryLibraryAssets()
            ->useDependencyExtractionData()
            ->enqueueScript('some-script');
    }

    /**
     * @test
     */
    public function testLibraryEnqueueFromDepInfoWithStrategy(): void
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'lib-some-script',
                "{$this->baseUrl}/some-script.js?v=a29c9d677e174811e603",
                ['wp-api-fetch'],
                null,
                ['in_footer' => false, 'strategy' => 'defer']
            );

        $this->factoryLibraryAssets()
            ->useDependencyExtractionData()
            ->enqueueScript('some-script', strategy: 'defer');
    }

    /**
     * @test
     */
    public function testRegisterAndEnqueueManifestPlusDependencyDataExtraction(): void
    {
        $assets = $this->factoryManifestsAssets()->useDependencyExtractionData();

        Functions\expect('wp_register_script')
            ->once()
            ->with(
                'hello-world-block-a',
                "{$this->baseUrl}/block-a.abcde.js?v=a29c9d677e174811e603",
                ['mhh-js-api', 'react', 'react-dom', 'wp-components', 'wp-element', 'wp-i18n'],
                null,
                ['in_footer' => true]
            );

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with('hello-world-block-a');

        $enqueue = $assets->registerScript('block-a');

        static::assertFalse($enqueue->isEnqueued());

        $enqueue->enqueue();

        static::assertTrue($enqueue->isEnqueued());
    }

    /**
     * @test
     */
    public function testRegisterAllFromManifestPlusDependencyDataExtraction(): void
    {
        $handles = [
            'hello-world-admin',
            'hello-world-admin-style',
            'hello-world-block-a',
            'hello-world-block-a-style',
            'hello-world-block-b',
            'hello-world-block-b-style',
            'hello-world-front',
            'hello-world-front-style',
        ];

        $aString = \Mockery::type('string');
        /** @psalm-suppress InvalidArgument */
        $aHandle = \Mockery::anyOf(...$handles);

        $deps = ['mhh-js-api', 'react', 'react-dom', 'wp-components', 'wp-element', 'wp-i18n'];
        $dataCheck = static function (string $handle, string $url): void {
            $filename = explode('?', array_slice(explode('/', $url), -1)[0])[0];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $query = parse_url($url, PHP_URL_QUERY);
            static::assertTrue(str_starts_with($handle, 'hello-world-'));
            static::assertFalse(str_contains($handle, '.abcde.'));
            static::assertTrue(in_array($ext, ['css', 'js'], true));
            static::assertTrue(str_ends_with($filename, ".abcde.{$ext}"));
            static::assertSame($url, filter_var($url, FILTER_VALIDATE_URL));
            static::assertSame($query, 'v=a29c9d677e174811e603');
        };

        Functions\expect('wp_register_script')
            ->twice()
            ->with($aHandle, $aString, [], null, ['in_footer' => true])
            ->andReturnUsing($dataCheck)
            ->andAlsoExpectIt()
            ->twice()
            ->with($aHandle, $aString, $deps, null, ['in_footer' => true])
            ->andReturnUsing($dataCheck);

        Functions\expect('wp_register_style')
            ->times(4)
            ->with($aHandle, $aString, [], null, 'all')
            ->andReturnUsing($dataCheck);

        Functions\expect('wp_enqueue_script')->times(4)->with($aHandle);
        Functions\expect('wp_enqueue_style')->times(4)->with($aHandle);

        $collection = $this
            ->factoryManifestsAssets()
            ->useDependencyExtractionData()
            ->registerAllFromManifest();

        static::assertCount(8, $collection);
        static::assertSame([], array_diff($collection->handles(), $handles));

        foreach ($handles as $handle) {
            $expectedCss = str_ends_with($handle, '-style');
            $expectedType = $expectedCss ? 'css' : 'js';
            $name = preg_replace('/^hello-world-/', '', $handle);
            $fullname = "{$name}.{$expectedType}";
            $byHandle = $collection->oneByHandle($handle);
            $byName = $collection->oneByName($name);
            $byFullname = $collection->oneByName($fullname);
            $byHandleType = $collection->oneByHandle($handle, $expectedType);
            $byNameType = $collection->oneByName($name, $expectedType);
            $byFullnameType = $collection->oneByName($fullname, $expectedType);
            static::assertInstanceOf(Enqueue::class, $byHandle);
            static::assertSame($byHandle, $byName);
            static::assertSame($byHandle, $byFullname);
            static::assertSame($byHandle, $byHandleType);
            static::assertSame($byHandle, $byNameType);
            static::assertSame($byHandle, $byFullnameType);
            static::assertFalse($byHandle->isEnqueued());
            static::assertSame($expectedCss, $byHandle->isCss());
        }

        $collection->enqueue();
    }

    /**
     * @return Assets
     */
    private function factoryPluginAssets(): Assets
    {
        $pluginFilePath = static::fixturesPath('/plugins/foo/plugin.php');

        Functions\expect('plugin_basename')
            ->with($pluginFilePath)
            ->andReturn('foo/plugin.php');

        Functions\expect('plugins_url')
            ->with('/assets/', $pluginFilePath)
            ->andReturn('http://example.com/wp-content/plugins/foo/assets');

        return Assets::forPlugin($pluginFilePath, 'assets/');
    }

    /**
     * @return Assets
     */
    private function factoryThemeAssets(): Assets
    {
        $themePath = static::fixturesPath('/themes/parent');
        $themeDir = '/wp-content/themes/parent';

        Functions\when('get_template')->justReturn('parent');
        Functions\when('get_template_directory')->justReturn($themePath);
        Functions\when('get_template_directory_uri')->justReturn('http://example.com' . $themeDir);

        return Assets::forTheme('/assets')->tryMinUrls();
    }

    /**
     * @return Assets
     */
    private function factoryChildThemeAssets(): Assets
    {
        $themeFolder = '/wp-content/themes/parent';
        $childThemeFolder = '/wp-content/themes/child';

        $baseUrl = 'https://example.com';
        $basePath = static::fixturesPath();

        Functions\when('get_template')->justReturn('parent');
        Functions\when('get_stylesheet')->justReturn('child');

        Functions\when('get_stylesheet_directory')->justReturn($basePath . '/themes/child');
        Functions\when('get_stylesheet_directory_uri')->justReturn($baseUrl . $childThemeFolder);

        Functions\when('get_template_directory')->justReturn($basePath . '/themes/parent');
        Functions\when('get_template_directory_uri')->justReturn($baseUrl . $themeFolder);

        return Assets::forChildTheme('/', '/assets');
    }

    /**
     * @return Assets
     */
    private function factoryLibraryAssets(): Assets
    {
        return Assets::forLibrary('lib', static::fixturesPath(), $this->baseUrl);
    }

    /**
     * @return Assets
     */
    private function factoryManifestsAssets(): Assets
    {
        return Assets::forManifest(
            'hello-world',
            static::fixturesPath('/manifest'),
            $this->baseUrl
        );
    }
}
