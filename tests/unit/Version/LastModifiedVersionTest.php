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

namespace Brain\Assets\Tests\Unit\Version;

use Brain\Assets\Tests\TestCase;
use Brain\Assets\Version\LastModifiedVersion;

class LastModifiedVersionTest extends TestCase
{
    /**
     * @test
     */
    public function testCalculateNoAltNoDebugFileExist(): void
    {
        $version = $this->factoryLastModifiedVersion(useAlt: false, debug: false);

        $actual = $version->calculate("{$this->baseUrl}/foo.css");

        $this->assertTimestamp($actual);
    }

    /**
     * @test
     */
    public function testCalculateNoAltNoDebugFileNotExist(): void
    {
        $version = $this->factoryLastModifiedVersion(useAlt: false, debug: false);

        $actual = $version->calculate("{$this->baseUrl}/assets/xxx.css");

        static::assertNull($actual);
    }

    /**
     * @test
     */
    public function testCalculateAltNoDebugFileExist(): void
    {
        $version = $this->factoryLastModifiedVersion(useAlt: true, debug: false);

        $actual = $version->calculate("{$this->altBaseUrl}/bar.css");

        $this->assertTimestamp($actual);
    }

    /**
     * @test
     */
    public function testCalculateAltNoDebugFileNotExist(): void
    {
        $version = $this->factoryLastModifiedVersion(useAlt: true, debug: false);

        $actual = $version->calculate("{$this->altBaseUrl}/xxx.css");

        static::assertNull($actual);
    }

    /**
     * @test
     */
    public function testCalculateNoAltNoDebugFileExistWrongUrl(): void
    {
        $version = $this->factoryLastModifiedVersion(useAlt: false, debug: false);

        $actual = $version->calculate('https://gmazzap.me/foo.css');

        static::assertNull($actual);
    }

    /**
     * @test
     */
    public function testCalculateNoAltDebugFileExist(): void
    {
        $version = $this->factoryLastModifiedVersion(useAlt: false, debug: true);

        $actual = $version->calculate("{$this->baseUrl}/foo.css");

        $this->assertTimestamp($actual);
    }

    /**
     * @test
     */
    public function testCalculateNoAltDebugFileNotExist(): void
    {
        $version = $this->factoryLastModifiedVersion(useAlt: false, debug: true);

        $actual = $version->calculate("{$this->baseUrl}/xxx.css");

        $this->assertNull($actual);
    }

    /**
     * @param bool $useAlt
     * @param bool $debug
     * @return LastModifiedVersion
     */
    private function factoryLastModifiedVersion(bool $useAlt, bool $debug): LastModifiedVersion
    {
        return LastModifiedVersion::new($this->factoryPathFinder($useAlt, $debug));
    }
}
