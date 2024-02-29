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

use Brain\Assets\Enqueue\JsEnqueue;
use Brain\Assets\Tests\TestCase;
use Brain\Assets\Tests\WpAssetsStub;
use Brain\Monkey;

class JsEnqueueTest extends TestCase
{
    private WpAssetsStub|null $wpScripts = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\Functions\when('wp_scripts')->alias(
            function (): WpAssetsStub {
                $this->wpScripts or $this->wpScripts = new WpAssetsStub();
                return $this->wpScripts;
            }
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->wpScripts = null;
        parent::tearDown();
    }

    /**
     * @test
     */
    public function testConditional(): void
    {
        JsEnqueue::new('h1')->withCondition('lt IE 9');

        static::assertSame(['conditional', 'lt IE 9'], $this->wpScripts?->data['h1']);
    }

    /**
     * @test
     */
    public function testBefore(): void
    {
        $code = 'alert("x");';
        Monkey\Functions\expect('wp_add_inline_script')
            ->once()
            ->with('h2', $code, 'before');

        JsEnqueue::new('h2')->prependInline($code);
    }

    /**
     * @test
     */
    public function testAfter(): void
    {
        $code = 'alert("x");';
        Monkey\Functions\expect('wp_add_inline_script')
            ->once()
            ->with('h3', $code, 'after');

        JsEnqueue::new('h3')->appendInline($code);
    }

    /**
     * @test
     */
    public function testLocalize(): void
    {
        $name = 'TestData';
        $data = ['foo' => 'bar'];
        Monkey\Functions\expect('wp_localize_script')->once()->with('h4', $name, $data);

        JsEnqueue::new('h4')->localize($name, $data);
    }

    /**
     * @test
     */
    public function testAttributes(): void
    {
        $handle = 'h5';
        $src = 'https://example.com/s1.js';

        /** @var callable|null $scrCb */
        $scrCb = null;
        /** @var callable|null $tagCb */
        $tagCb = null;

        $before = 'var Foo = ' . json_encode(['foo' => ['bar' => 'baz']], JSON_PRETTY_PRINT) . ';';
        $before .= PHP_EOL . '<script>alert("before!");</script>' . PHP_EOL;
        $after = PHP_EOL . '<script src="https://example.com/s2.js"></script>';

        $tag = $before . '<script src="' . $src . '"></script>' . $after;

        Monkey\Filters\expectAdded('script_loader_src')->once()->whenHappen(
            static function (callable $callback) use (&$scrCb): void {
                $scrCb = $callback;
            }
        );

        Monkey\Filters\expectAdded('script_loader_tag')->once()->whenHappen(
            static function (callable $callback) use (&$tagCb): void {
                $tagCb = $callback;
            }
        );

        Monkey\Filters\expectApplied('script_loader_src')->once()->andReturnUsing(
            static function (string $scr, string $handle) use (&$scrCb): mixed {
                /** @var callable $scrCb */
                return $scrCb($scr, $handle);
            }
        );

        Monkey\Filters\expectApplied('script_loader_tag')->once()->andReturnUsing(
            static function (string $tag, string $handle) use (&$tagCb): mixed {
                /** @var callable $tagCb */
                return $tagCb($tag, $handle);
            }
        );

        $newSrc = 'https://example.com/replaced.js';

        JsEnqueue::new($handle)
            ->useDefer()
            ->useAsync()
            ->useAttribute('data-test', 'Test me!')
            ->useAttribute('async', 'true')
            ->useAttribute('meh', null)
            ->addFilter(static function (string $tag) use ($src, $newSrc): string {
                return str_replace($src, $newSrc, $tag);
            });

        apply_filters('script_loader_src', $src, $handle);
        $filtered = apply_filters('script_loader_tag', $tag, $handle);

        $expected = $before
            . '<script meh data-test="Test me!" src="'
            . $newSrc
            . '"></script>'
            . $after;

        static::assertSame(['strategy', 'async'], $this->wpScripts?->data[$handle]);
        static::assertSame($expected, $filtered);
    }

    /**
     * @test
     */
    public function testAsyncDefer(): void
    {
        $enqueue = JsEnqueue::new('handle')->useDefer();
        static::assertSame(['strategy', 'defer'], $this->wpScripts?->data['handle']);

        $enqueue->useAsync();
        static::assertSame(['strategy', 'async'], $this->wpScripts?->data['handle']);

        $enqueue->useAttribute('defer', null);
        static::assertSame(['strategy', 'defer'], $this->wpScripts?->data['handle']);

        $enqueue->useAttribute('async', 'true');
        static::assertSame(['strategy', 'async'], $this->wpScripts?->data['handle']);

        $enqueue->useAttribute('async', 'false');
        static::assertSame(['strategy', false], $this->wpScripts?->data['handle']);

        $enqueue->useDefer();
        $enqueue->useDefer();
        static::assertSame(['strategy', 'defer'], $this->wpScripts?->data['handle']);

        $enqueue->useAsync();
        static::assertSame(['strategy', 'async'], $this->wpScripts?->data['handle']);
    }
}
