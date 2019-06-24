<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit\Enqueue;

use Brain\Assets\Enqueue\JsEnqueue;
use Brain\Assets\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Filters;

class JsEnqueueTest extends TestCase
{
    public $scriptData = []; // phpcs:ignore

    protected function setUp(): void
    {
        parent::setUp();

        $scripts = new class($this)
        {
            private $test;

            public function __construct(JsEnqueueTest $test)
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
                $this->test->scriptData[$handle] = $args;
            }
        };

        Functions\when('wp_scripts')->alias(
            function () use ($scripts) {
                return $scripts;
            }
        );
    }

    protected function tearDown(): void
    {
        $this->scriptData = [];
        parent::tearDown();
    }

    public function testConditional()
    {
        $enqueue = new JsEnqueue('h1');
        $enqueue->withCondition('lt IE 9');

        static::assertSame(['conditional', 'lt IE 9'], $this->scriptData['h1']);
    }

    public function testBefore()
    {
        $handle = 'h2';
        $code = 'alert("x");';

        $enqueue = new JsEnqueue($handle);
        $enqueue->prependInline($code);

        static::assertSame(['before', $code], $this->scriptData[$handle]);
    }

    public function testAfter()
    {
        $handle = 'h3';
        $code = 'alert("x");';

        $enqueue = new JsEnqueue($handle);
        $enqueue->appendInline($code);

        static::assertSame(['after', $code], $this->scriptData[$handle]);
    }

    public function testLocalize()
    {
        $handle = 'h4';
        $name = 'TestData';
        $data = ['foo' => 'bar'];

        Functions\expect('wp_localize_script')
            ->once()
            ->with($handle, $name, $data);

        $enqueue = new JsEnqueue($handle);
        $enqueue->localize($name, $data);
    }

    public function testAttributes()
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

        Filters\expectAdded('script_loader_src')
            ->once()
            ->whenHappen(
                function (callable $cb) use (&$scrCb) {
                    $scrCb = $cb;
                }
            );

        Filters\expectAdded('script_loader_tag')
            ->once()
            ->whenHappen(
                function (callable $cb) use (&$tagCb) {
                    $tagCb = $cb;
                }
            );

        Filters\expectApplied('script_loader_src')
            ->once()
            ->andReturnUsing(
                function ($scr, $handle) use (&$scrCb) {
                    return $scrCb($scr, $handle);
                }
            );

        Filters\expectApplied('script_loader_tag')
            ->once()
            ->andReturnUsing(
                function ($tag, $handle) use (&$tagCb) {
                    return $tagCb($tag, $handle);
                }
            );

        Functions\when('esc_attr')->returnArg();

        $newSrc = 'https://example.com/replaced.js';

        $enqueue = new JsEnqueue($handle);
        $enqueue
            ->useDefer()
            ->useAsync()
            ->useAttribute('data-test', 'Test me!')
            ->useAttribute('async', 'true')
            ->useAttribute('meh', null)
            ->addFilter(
                function (string $tag) use ($src, $newSrc) {
                    return str_replace($src, $newSrc, $tag);
                }
            );

        apply_filters('script_loader_src', $src, $handle);
        $filtered = apply_filters('script_loader_tag', $tag, $handle);

        $expected = $before
            . '<script meh data-test="Test me!" async defer src="'
            . $newSrc
            . '"></script>'
            . $after;

        static::assertSame($expected, $filtered);
    }
}
