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

use Brain\Assets\Context\WpContext;
use Brain\Assets\Factory;
use Brain\Assets\Tests\TestCase;
use Brain\Assets\UrlResolver\ManifestUrlResolver;
use Brain\Assets\UrlResolver\UrlResolver;
use Brain\Assets\Version\LastModifiedVersion;
use Brain\Monkey;

class FactoryTest extends TestCase
{
    /**
     * @return void
     */
    public function testRegisterDoNothingAfterRetrieval(): void
    {
        $factory = Factory::new($this->factoryContext(useAlt: false, debug: false));
        $resolver1 = $factory->urlResolver();

        $factory->registerFactory(
            UrlResolver::class,
            static function (): UrlResolver {
                /** @var UrlResolver */
                return \Mockery::mock(UrlResolver::class);
            }
        );

        $resolver2 = $factory->urlResolver();

        static::assertInstanceOf(ManifestUrlResolver::class, $resolver1);
        static::assertSame($resolver1, $resolver2);
    }

    /**
     * @return void
     */
    public function testRegisterWorksBeforeRetrieval(): void
    {
        $factory = Factory::new($this->factoryContext(useAlt: false, debug: false));

        /** @var UrlResolver $resolver1 */
        $resolver1 = \Mockery::mock(UrlResolver::class);

        $factory->registerFactory(
            UrlResolver::class,
            static function () use ($resolver1): UrlResolver {
                return $resolver1;
            }
        );

        $resolver2 = $factory->urlResolver();

        static::assertSame($resolver1, $resolver2);
    }

    /**
     * @return void
     */
    public function testReplaceObjectByFilter(): void
    {
        $factory = Factory::new($this->factoryContext(useAlt: false, debug: false));

        /** @var UrlResolver $resolver1 */
        $resolver1 = \Mockery::mock(UrlResolver::class);

        Monkey\Filters\expectApplied("brain.assets.factory." . UrlResolver::class)
            ->once()
            ->with(\Mockery::type(ManifestUrlResolver::class), $factory)
            ->andReturn($resolver1);

        $resolver2 = $factory->urlResolver();
        $resolver3 = $factory->urlResolver();

        static::assertSame($resolver1, $resolver2);
        static::assertSame($resolver2, $resolver3);
    }

    /**
     * @return void
     */
    public function testReplaceObjectByFilterDoesNotWorkIfWrongType(): void
    {
        $factory = Factory::new($this->factoryContext(useAlt: false, debug: false));

        Monkey\Filters\expectApplied("brain.assets.factory." . UrlResolver::class)
            ->once()
            ->with(\Mockery::type(ManifestUrlResolver::class), $factory)
            ->andReturn(new \stdClass());

        $resolver1 = $factory->urlResolver();
        $resolver2 = $factory->urlResolver();

        static::assertInstanceOf(ManifestUrlResolver::class, $resolver1);
        static::assertSame($resolver1, $resolver2);
    }

    /**
     * @return void
     */
    public function testFactoryVersion(): void
    {
        $factory = Factory::new($this->factoryContext(useAlt: false, debug: false));

        $version1 = $factory->version();
        $version2 = $factory->lastModifiedVersion();
        $version3 = $factory->version();

        static::assertInstanceOf(LastModifiedVersion::class, $version1);
        static::assertInstanceOf(LastModifiedVersion::class, $version2);
        static::assertNotSame($version1, $version2);
        static::assertSame($version1, $version3);
    }

    /**
     * @return void
     */
    public function testHasManifest(): void
    {
        $factory1 = Factory::new($this->factoryContext(useAlt: false, debug: false));

        $context = WpContext::new('test', __DIR__, $this->baseUrl);
        $factory2 = Factory::new($context);

        static::assertTrue($factory1->hasManifest());
        static::assertFalse($factory2->hasManifest());
    }
}
