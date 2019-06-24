<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit\Version;

use Brain\Assets\Context\WpContext;
use Brain\Assets\Tests\TestCase;
use Brain\Assets\Version\LastModifiedVersion;

class LastModifiedVersionTest extends TestCase
{
    private const URL = 'https://example.com/assets/';
    private const ALT_URL = 'https://example.com/alt/assets/';

    public function testCalculateNoAltNoDebugFileExist()
    {
        $version = $this->createVersion(false, false);

        $ver = $version->calculate(self::URL . 'foo.css');

        static::assertTrue(is_numeric($ver));
        static::assertTrue((int)$ver > mktime(0, 0, 0, 1, 1, 1970));
    }

    public function testCalculateNoAltNoDebugFileNotExist()
    {
        $version = $this->createVersion(false, false);

        $ver = $version->calculate('https://example.com/assets/xxx.css');

        static::assertNull($ver);
    }

    public function testCalculateAltNoDebugFileExist()
    {
        $version = $this->createVersion(true, false);
        $ver = $version->calculate(self::ALT_URL . 'bar.css');

        static::assertTrue(is_numeric($ver));
        static::assertTrue((int)$ver > mktime(0, 0, 0, 1, 1, 1970));
    }

    public function testCalculateAltNoDebugFileNotExist()
    {
        $version = $this->createVersion(true, false);

        $ver = $version->calculate(self::ALT_URL . 'xxx.css');

        static::assertNull($ver);
    }

    public function testCalculateNoAltNoDebugFileExistWrongUrl()
    {
        $version = $this->createVersion(false, false);

        $ver = $version->calculate('https://gmazzap.me/foo.css');

        static::assertNull($ver);
    }

    public function testCalculateNoAltDebugFileExist()
    {
        $version = $this->createVersion(false, true);

        $time = time();
        $ver = $version->calculate(self::URL . 'foo.css');

        static::assertTrue(is_numeric($ver));
        static::assertTrue(($time - (int)$ver) < 5);
    }

    public function testCalculateNoAltDebugFileNotExist()
    {
        $version = $this->createVersion(false, true);

        $time = time();
        $ver = $version->calculate(self::URL . 'xxx.css');

        static::assertTrue(is_numeric($ver));
        static::assertTrue(($time - (int)$ver) < 5);
    }

    /**
     * @param bool $alt
     * @param bool $debug
     * @return LastModifiedVersion
     */
    private function createVersion(bool $alt = false, bool $debug = false): LastModifiedVersion
    {
        $args = [getenv('FIXTURES_DIR'), self::URL];
        if ($alt) {
            $args[] = getenv('FIXTURES_DIR') . '/alt';
            $args[] = self::ALT_URL;
        }

        $context = $debug ? WpContext::createWithDebug(...$args) : WpContext::createWithNoDebug(...$args);

        return new LastModifiedVersion($context);
    }
}
