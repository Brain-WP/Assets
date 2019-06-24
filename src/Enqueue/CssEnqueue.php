<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Enqueue;

use Brain\Assets\Assets;

class CssEnqueue implements Enqueue
{
    /**
     * @var string
     */
    private $handle;

    /**
     * @var Filters|null
     */
    private $filters;

    /**
     * @param string $name
     * @param Assets $assets
     * @return CssEnqueue
     */
    public static function forFile(string $name, Assets $assets): CssEnqueue
    {
        return new static($assets->handleForName($name));
    }

    /**
     * @param string $handle
     * @return CssEnqueue
     */
    public static function create(string $handle): CssEnqueue
    {
        return new static($handle);
    }

    /**
     * @param string $handle
     */
    public function __construct(string $handle)
    {
        $this->handle = $handle;
    }

    /**
     * @return string
     */
    public function handle(): string
    {
        return $this->handle;
    }

    /**
     * @param string $condition
     * @return CssEnqueue
     */
    public function withCondition(string $condition): CssEnqueue
    {
        wp_styles()->add_data($this->handle, 'conditional', $condition);

        return $this;
    }

    /**
     * @return CssEnqueue
     */
    public function asAlternate(): CssEnqueue
    {
        wp_styles()->add_data($this->handle, 'alt', true);

        return $this;
    }

    /**
     * @param string $title
     * @return CssEnqueue
     */
    public function withTitle(string $title): CssEnqueue
    {
        wp_styles()->add_data($this->handle, 'title', $title);

        return $this;
    }

    /**
     * @param string $cssCode
     * @return CssEnqueue
     */
    public function appendInline(string $cssCode): CssEnqueue
    {
        wp_add_inline_style($this->handle, $cssCode);

        return $this;
    }

    /**
     * @param callable $callback
     * @return CssEnqueue
     */
    public function addFilter(callable $callback): CssEnqueue
    {
        $this->setupFilterHooks();

        /** @psalm-suppress PossiblyNullReference */
        $this->filters->add($callback);

        return $this;
    }

    /**
     * @param string $name
     * @param string|null $value
     * @return CssEnqueue
     */
    public function useAttribute(string $name, ?string $value): CssEnqueue
    {
        $normalizedName = trim(strtolower($name));

        if ($normalizedName === 'title') {
            return $value ? $this->withTitle($value) : $this;
        }

        if ($normalizedName === 'alternate') {
            return $value === null ? $this->asAlternate() : $this;
        }

        $this->setupFilterHooks();

        /** @psalm-suppress PossiblyNullReference */
        $this->filters->addAttribute($name, $value);

        return $this;
    }

    /**
     * @return void
     */
    private function setupFilterHooks(): void
    {
        if ($this->filters) {
            return;
        }

        $this->filters = Filters::forStyles();

        /**
         * @psalm-suppress MissingClosureReturnType
         * @psalm-suppress MissingClosureParamType
         */
        add_filter(
            'style_loader_tag',
            function ($tag, string $handle) {
                if ($tag && $handle === $this->handle && is_string($tag)) {
                    return $this->filters->apply($tag);
                }

                return $tag;
            },
            10,
            2
        );
    }
}
