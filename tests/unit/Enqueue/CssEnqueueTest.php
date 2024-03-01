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

namespace Brain\Assets\Tests\Unit\Enqueue;

use Brain\Assets\Enqueue\CssEnqueue;
use Brain\Assets\Tests\TestCase;
use Brain\Assets\Tests\WpAssetsStub;
use Brain\Monkey;

class CssEnqueueTest extends TestCase
{
    private WpAssetsStub|null $wpStyles = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->wpStyles = $this->mockWpDependencies('style');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->wpStyles = null;
        parent::tearDown();
    }

    /**
     * @test
     */
    public function testConditional(): void
    {
        CssEnqueue::new('h1')->withCondition('lt IE 9');

        static::assertSame('lt IE 9', $this->wpStyles?->data['h1']['conditional'] ?? null);
    }

    /**
     * @test
     */
    public function testAlternate(): void
    {
        CssEnqueue::new('h2')->asAlternate();

        static::assertSame(true, $this->wpStyles?->data['h2']['alt'] ?? null);
    }

    /**
     * @test
     */
    public function testTitle(): void
    {
        CssEnqueue::new('h3')->withTitle('Hello');

        static::assertSame('Hello', $this->wpStyles?->data['h3']['title'] ?? null);
    }

    /**
     * @test
     */
    public function testInline(): void
    {
        $inline = 'p { display:none }';

        Monkey\Functions\expect('wp_add_inline_style')->once()->with('h4', $inline);

        CssEnqueue::new('h4')->appendInline($inline);
    }

    /**
     * @test
     */
    public function testFilters(): void
    {
        $tag = '<link rel="stylesheet" href="https://example.com/style.css">';
        $handle = 'h5';
        /** @var callable|null $filterCallback */
        $filterCallback = null;

        Monkey\Filters\expectAdded('style_loader_tag')
            ->once()
            ->whenHappen(
                static function (callable $callback) use (&$filterCallback): void {
                    $filterCallback = $callback;
                }
            );

        Monkey\Filters\expectApplied('style_loader_tag')
            ->once()
            ->with($tag, $handle)
            ->andReturnUsing(
                static function (string $tag, string $handle) use (&$filterCallback): mixed {
                    /** @var callable $filterCallback */
                    return $filterCallback($tag, $handle);
                }
            );

        CssEnqueue::new($handle)
            ->useAttribute('data-foo', 'bar')
            ->useAttribute('meh', null)
            ->addFilter(static function (string $tag): string {
                return str_replace(' rel="stylesheet"', '', $tag);
            })->addFilter(static function (string $tag): string {
                return str_replace('style.css', 'style-1.css', $tag);
            })->addFilter(static function (string $tag): string {
                return str_replace('https', 'http', $tag);
            });

        $filtered = apply_filters('style_loader_tag', $tag, $handle);

        static::assertSame(
            '<link meh data-foo="bar" href="http://example.com/style-1.css">',
            $filtered
        );
    }
}
