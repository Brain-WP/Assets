<?php

declare(strict_types=1);

namespace Brain\Assets\Tests\Unit\Enqueue;

use Brain\Assets\Assets;
use Brain\Assets\Enqueue\Collection;
use Brain\Assets\Enqueue\CssEnqueue;
use Brain\Assets\Enqueue\Enqueue;
use Brain\Assets\Tests\TestCase;
use Brain\Monkey;

class CollectionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideKeepOrDiscardData
     */
    public function testKeep(string $pattern, ?string $type, int $expectedCount): void
    {
        $collection = $this->factoryCollection()->keep($pattern, $type);
        $handles = $collection->handles();

        static::assertCount(
            $expectedCount,
            $collection,
            sprintf(
                '$collection->keep("%s", %s) matched: [%s]',
                $pattern,
                ($type === null) ? 'null' : "\"{$type}\"",
                implode(", ", $handles)
            )
        );
    }

    /**
     * @test
     * @dataProvider provideKeepOrDiscardData
     */
    public function testDiscard(string $pattern, ?string $type, int $expectedDiscarded): void
    {
        $collection = $this->factoryCollection()->discard($pattern, $type);
        $handles = $collection->handles();

        static::assertCount(
            8 - $expectedDiscarded,
            $collection,
            sprintf(
                '$collection->discard("%s", %s) matched: [%s]',
                $pattern,
                ($type === null) ? 'null' : "\"{$type}\"",
                implode(", ", $handles)
            )
        );
    }

    /**
     * @return \Generator
     */
    public static function provideKeepOrDiscardData(): \Generator
    {
        yield from [
            ['-style', null, 4],
            ['*-style', null, 4],
            ['*k-a', null, 1],
            ['*k-a*', null, 2],
            ['block-[a|b]', null, 0],
            ['block-', null, 4],
            ['block-style', null, 0],
            ['*block-*-style', null, 2],
            ['*block-*', null, 4],
            ['/block-[a|b]/', null, 4],
            ['/block-[a|b]$/', null, 2],
            ['/block-[A|B]$/', null, 0],
            ['/block-[A|B]$/i', null, 2],
            ['-style', 'css', 4],
            ['*-style', 'css', 4],
            ['*-a', 'css', 0],
            ['block-[a|b]', 'css', 0],
            ['block-', 'css', 2],
            ['block-style', 'css', 0],
            ['*block-*-style', 'css', 2],
            ['*block-*', 'css', 2],
            ['/block-[a|b]/', 'css', 2],
            ['/block-[a|b]$/', 'css', 0],
            ['/block-[A|B]$/', 'css', 0],
            ['/block-[A|B]$/i', 'css', 0],
            ['-style', 'js', 0],
            ['*-style', 'js', 0],
            ['*-a', 'js', 1],
            ['block-[a|b]', 'js', 0],
            ['block-', 'js', 2],
            ['block-style', 'js', 0],
            ['block-*-style', 'js', 0],
            ['*block-*', 'js', 2],
            ['/block-[a|b]/', 'js', 2],
            ['/block-[a|b]$/', 'js', 2],
            ['/block-[A|B]$/', 'js', 0],
            ['/block-[A|B]$/i', 'js', 2],
        ];
    }

    /**
     * @test
     */
    public function testMergeDuplicate(): void
    {
        $collection = $this->factoryCollection();
        $merged = $collection->merge($this->factoryCollection());

        static::assertNotSame($collection, $merged);
        static::assertSame($collection->handles(), $merged->handles());
    }

    /**
     * @test
     */
    public function testMerge(): void
    {
        $collection = $this->factoryCollection();
        $collection2 = Collection::new($this->factoryAssets(), CssEnqueue::new('xyz'));
        $merged = $collection->merge($collection2);

        static::assertCount(8, $collection);
        static::assertCount(1, $collection2);
        static::assertCount(9, $merged);
        static::assertSame(
            array_merge($collection->handles(), $collection2->handles()),
            $merged->handles()
        );
    }

    /**
     * @test
     */
    public function testDiffDuplicate(): void
    {
        $collection = $this->factoryCollection();
        $diff = $collection->diff($this->factoryCollection());

        static::assertSame([], $diff->handles());
    }

    /**
     * @test
     */
    public function testDiffNotIntersection(): void
    {
        $collection = $this->factoryCollection();
        $collection2 = Collection::new($this->factoryAssets(), CssEnqueue::new('xyz'));
        $diff = $collection->diff($collection2);

        static::assertCount(8, $collection);
        static::assertCount(1, $collection2);
        static::assertCount(8, $diff);
        static::assertNotSame($collection, $diff);
    }

    /**
     * @test
     */
    public function testDiff(): void
    {
        $collection = $this->factoryCollection();

        $first = $collection->first();
        self::assertInstanceOf(Enqueue::class, $first);

        $collection2 = Collection::new($this->factoryAssets(), $first);
        $diff = $collection->diff($collection2);

        static::assertCount(8, $collection);
        static::assertCount(1, $collection2);
        static::assertCount(7, $diff);
    }

    /**
     * @test
     */
    public function testByName(): void
    {
        $collection = $this->factoryCollection();
        $enqueue1 = $collection->oneByName('block-a');
        $enqueue2 = $collection->oneByName('block-a.js');
        $enqueue3 = $collection->oneByName('block-a.abcde.js');
        $enqueue4 = $collection->oneByName('block-a', 'js');
        $enqueue5 = $collection->oneByName('block-a.js', 'js');
        $enqueue6 = $collection->oneByName('block-a.abcde.js', 'js');
        $enqueue7 = $collection->oneByName('block-a', 'css');
        $enqueue8 = $collection->oneByName('block-a.js', 'css');
        $enqueue9 = $collection->oneByName('block-a.abcde.js', 'css');

        self::assertInstanceOf(Enqueue::class, $enqueue1);
        self::assertSame($enqueue1, $enqueue2);
        self::assertSame($enqueue1, $enqueue3);
        self::assertSame($enqueue1, $enqueue4);
        self::assertSame($enqueue1, $enqueue5);
        self::assertSame($enqueue1, $enqueue6);
        self::assertNull($enqueue7);
        self::assertNull($enqueue8);
        self::assertNull($enqueue9);
    }

    /**
     * @test
     */
    public function testByHandle(): void
    {
        $collection = $this->factoryCollection();
        $enqueue1 = $collection->oneByHandle('block-a');
        $enqueue2 = $collection->oneByHandle('hello-world-block-a');
        $enqueue3 = $collection->oneByHandle('block-a', 'js');
        $enqueue4 = $collection->oneByHandle('hello-world-block-a', 'js');
        $enqueue5 = $collection->oneByHandle('block-a', 'css');
        $enqueue6 = $collection->oneByHandle('hello-world-block-a', 'css');

        self::assertInstanceOf(Enqueue::class, $enqueue1);
        self::assertSame($enqueue1, $enqueue2);
        self::assertSame($enqueue1, $enqueue3);
        self::assertSame($enqueue1, $enqueue4);
        self::assertNull($enqueue5);
        self::assertNull($enqueue6);
    }

    /**
     * @return Collection
     */
    private function factoryCollection(): Collection
    {
        Monkey\Functions\expect('wp_register_style')->times(4);
        Monkey\Functions\expect('wp_register_script')->times(4);

        $collection = $this->factoryAssets()->registerAllFromManifest();
        static::assertCount(8, $collection);

        return $collection;
    }

    /**
     * @return Assets
     */
    private function factoryAssets(): Assets
    {
        $path = static::fixturesPath('/manifest');

        return Assets::forManifest('hello-world', $path, $this->baseUrl);
    }
}
