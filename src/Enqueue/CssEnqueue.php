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

namespace Brain\Assets\Enqueue;

class CssEnqueue extends AbstractEnqueue
{
    private Filters|null $filters = null;

    /**
     * @param string $handle
     * @return static
     */
    public static function newRegistration(string $handle): static
    {
        return new static($handle, false);
    }

    /**
     * @param string $handle
     * @return static
     */
    public static function new(string $handle): static
    {
        return new static($handle, true);
    }

    /**
     * @param string $handle
     * @param bool $isEnqueue
     */
    final protected function __construct(
        private string $handle,
        bool $isEnqueue
    ) {

        $this->isEnqueue = $isEnqueue;
        $this->isCss = true;
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
     * @return static
     */
    public function withCondition(string $condition): static
    {
        wp_styles()->add_data($this->handle, 'conditional', $condition);

        return $this;
    }

    /**
     * @return static
     */
    public function asAlternate(): static
    {
        wp_styles()->add_data($this->handle, 'alt', true);

        return $this;
    }

    /**
     * @param string $title
     * @return static
     */
    public function withTitle(string $title): static
    {
        wp_styles()->add_data($this->handle, 'title', $title);

        return $this;
    }

    /**
     * @param string $cssCode
     * @return static
     */
    public function appendInline(string $cssCode): static
    {
        wp_add_inline_style($this->handle, $cssCode);

        return $this;
    }

    /**
     * @param string $name
     * @param string|null $value
     * @return static
     */
    public function useAttribute(string $name, ?string $value): static
    {
        $normalizedName = trim(strtolower($name));

        if ($normalizedName === 'title') {
            return (($value !== null) && ($value !== '')) ? $this->withTitle($value) : $this;
        }

        if ($normalizedName === 'alternate') {
            return ($value === null) ? $this->asAlternate() : $this;
        }

        $this->setupFilterHooks();
        $this->filters->addAttribute($name, $value);

        return $this;
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function addFilter(callable $callback): static
    {
        $this->setupFilterHooks();
        $this->filters->add($callback);

        return $this;
    }

    /**
     * @return void
     *
     * @psalm-assert Filters $this->filters
     */
    private function setupFilterHooks(): void
    {
        if ($this->filters) {
            return;
        }

        $this->filters = Filters::newForStyles();

        add_filter(
            'style_loader_tag',
            function (mixed $tag, string $handle): mixed {
                if (
                    $this->filters
                    && ($tag !== '')
                    && ($handle === $this->handle)
                    && is_string($tag)
                ) {
                    return $this->filters->apply($tag);
                }

                return $tag;
            },
            10,
            2
        );
    }
}
