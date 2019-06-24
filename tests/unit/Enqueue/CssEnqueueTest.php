<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit\Enqueue;

use Brain\Assets\Enqueue\CssEnqueue;
use Brain\Assets\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Filters;

class CssEnqueueTest extends TestCase
{
    public $stylesData = []; // phpcs:ignore

    protected function setUp(): void
    {
        parent::setUp();

        $styles = new class($this)
        {
            private $test;

            public function __construct(CssEnqueueTest $test)
            {
                $this->test = $test;
            }

            /**
             * @param $handle
             * @param mixed ...$args
             *
             * @phpcs:disable
             */
            public function add_data($handle, ...$args)
            {
                $this->test->stylesData[$handle] = $args;
            }
        };

        Functions\when('wp_styles')->alias(
            function () use ($styles) {
                return $styles;
            }
        );
    }

    protected function tearDown(): void
    {
        $this->stylesData = [];
        parent::tearDown();
    }

    public function testConditional()
    {
        $enqueue = new CssEnqueue('h1');
        $enqueue->withCondition('lt IE 9');

        static::assertSame(['conditional', 'lt IE 9'], $this->stylesData['h1']);
    }

    public function testAlternate()
    {
        $enqueue = new CssEnqueue('h2');
        $enqueue->asAlternate();

        static::assertSame(['alt', true], $this->stylesData['h2']);
    }

    public function testTitle()
    {
        $enqueue = new CssEnqueue('h3');
        $enqueue->withTitle('Hello');

        static::assertSame(['title', 'Hello'], $this->stylesData['h3']);
    }

    public function testInline()
    {
        $inline = 'p { display:none }';

        Functions\expect('wp_add_inline_style')
            ->once()
            ->with('h4', $inline);

        $enqueue = new CssEnqueue('h4');
        $enqueue->appendInline($inline);
    }

    public function testFilters()
    {
        Functions\when('esc_attr')->returnArg();

        $tag = '<link rel="stylesheet" href="https://example.com/style.css">';
        $handle = 'h5';
        /** @var callable|null $filterCallback */
        $filterCallback = null;

        Filters\expectAdded('style_loader_tag')
            ->once()
            ->whenHappen(
                function (callable $callback) use (&$filterCallback): void {
                    $filterCallback = $callback;
                }
            );

        Filters\expectApplied('style_loader_tag')
            ->once()
            ->with($tag, $handle)
            ->andReturnUsing(
                function (string $tag, string $handle) use (&$filterCallback): string {
                    return $filterCallback($tag, $handle);
                }
            );

        $enqueue = new CssEnqueue($handle);

        $enqueue
            ->useAttribute('data-foo', 'bar')
            ->useAttribute('meh', null)
            ->addFilter(
                function ($tag) {
                    return str_replace(' rel="stylesheet"', '', $tag);
                }
            )->addFilter(
                function ($tag) {
                    return str_replace('style.css', 'style-1.css', $tag);
                }
            )->addFilter(
                function ($tag) {
                    return str_replace('https', 'http', $tag);
                }
            );

        $filtered = apply_filters('style_loader_tag', $tag, $handle);

        static::assertSame(
            '<link meh data-foo="bar" href="http://example.com/style-1.css">',
            $filtered
        );
    }
}
