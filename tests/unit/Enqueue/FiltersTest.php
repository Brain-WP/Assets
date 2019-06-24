<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Tests\Unit\Enqueue;

use Brain\Assets\Enqueue\Filters;
use Brain\Assets\Tests\TestCase;
use Brain\Monkey\Functions;

class FiltersTest extends TestCase
{
    public function testNoCallableDoNothing()
    {
        $filters = Filters::forStyles();

        static::assertSame('foo', $filters->apply('foo'));
    }

    public function testThrowableCauseEmptyString()
    {
        $filters = Filters::forStyles();

        $filters->add(
            function (string $str): string {
                return "{$str}-bar";
            }
        )->add(
            function (string $str): string {
                return "{$str}-baz";
            }
        );

        static::assertSame('foo-bar-baz', $filters->apply('foo'));

        $filters->add(
            function () {
                throw new \Error();
            }
        );

        static::assertSame('', $filters->apply('foo'));
    }

    public function testReturningNonStringCauseEmptyString()
    {
        $filters = Filters::forStyles();

        $filters->add(
            function (string $str): string {
                return "{$str}-bar";
            }
        )->add(
            function (string $str): array {
                return [$str];
            }
        )->add(
            function (string $str): string {
                return "{$str}-baz";
            }
        );

        static::assertSame('', $filters->apply('foo'));
    }

    public function testAddAttributeDoNothingIfAttributeExists()
    {
        Functions\when('esc_attr')->returnArg();

        $tag = '<script src="https://example.com"></script>';

        $filters = Filters::forStyles();

        $filters
            ->add(
                function (string $str): string {
                    return str_replace('<script', '<script async', $str);
                }
            )->add(
                function (string $str): string {
                    return str_replace('<script', '<script data-foo="bar"', $str);
                }
            );

        $filters->addAttribute('async', 'async');
        $filters->addAttribute('data-foo', 'x');

        $expected = '<script data-foo="bar" async src="https://example.com"></script>';

        static::assertSame($expected, $filters->apply($tag));
    }

    public function testRealWordTag()
    {
        Functions\when('esc_attr')->returnArg();

        $tag = '<script src="https://example.com"></script>';

        $filters = Filters::forScripts()
            ->addAttribute('defer', null)
            ->addAttribute('foo', 'bar')
            ->addAttribute('async', null)
            ->addAttribute('defer', null)
            ->addAttribute('foo', "baz")
            ->addAttribute('async', null)
            ->add(
                function (string $tag): string {
                    return str_replace('example.com', 'example.it', $tag);
                }
            );

        $expected = '<script async foo="bar" defer src="https://example.it"></script>';

        static::assertSame($expected, $filters->apply($tag));
    }
}
