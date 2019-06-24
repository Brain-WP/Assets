<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit;

use Brain\Assets\Assets;
use Brain\Assets\Tests\AssetsTestCase;
use Brain\Monkey\Functions;

class LibraryAssetsTest extends AssetsTestCase
{
    public function testAutomaticManifest()
    {
        $assets = $this->createInstance()->dontAddVersion();

        $imgUrl = $assets->assetUrl('img.png');
        $cssUrl = $assets->assetUrl('foo.css');

        static::assertSame('https://example.com/img.png', $imgUrl);
        static::assertSame('https://example.com/foo.abcde.css', $cssUrl);
    }

    public function testEnqueue()
    {
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('foo', 'https://example.com/foo.abcde.css', [], null, 'screen');

        $this->createInstance()
            ->disableHandlePrefix()
            ->dontAddVersion()
            ->enqueueStyle('foo', [], 'screen');
    }

    /**
     * @return Assets
     */
    private function createInstance(): Assets
    {
        return Assets::forLibrary('lib', getenv('FIXTURES_DIR'), 'http://example.com');
    }
}
