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

class Filters
{
    /** @var list<callable> */
    private array $filters = [];

    /** @var array<string, string|null> */
    private array $attributes = [];

    /**
     * @return static
     */
    public static function newForScripts(): static
    {
        return new static('script');
    }

    /**
     * @return static
     */
    public static function newForStyles(): static
    {
        return new static('link');
    }

    /**
     * @param 'link'|'script' $tag
     */
    final protected function __construct(
        private string $tag
    ) {
    }

    /**
     * @param callable $filter
     * @return static
     */
    public function add(callable $filter): static
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @param string $name
     * @param string|null $value
     * @return static
     */
    public function addAttribute(string $name, ?string $value): static
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $name = ($name !== '') ? (string) sanitize_key($name) : '';
        if ($name === '') {
            return $this;
        }

        if (($value !== null) && ($value !== '')) {
            $value = trim(esc_attr($value));
        }

        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param string $tag
     * @return string
     *
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel
     */
    public function apply(string $tag): string
    {
        // phpcs:enable Inpsyde.CodeQuality.NestingLevel

        foreach ($this->attributes as $name => $value) {
            if (preg_match('~\s+' . preg_quote($name, '~') . '(?:\s|=|>)~', $tag)) {
                continue;
            }

            $replace = ($value === null) ? $name : "{$name}=\"{$value}\"";
            $tag = str_replace("<{$this->tag}", "<{$this->tag} {$replace}", $tag);
        }

        foreach ($this->filters as $filter) {
            try {
                $maybeTag = $filter($tag);
                if (($maybeTag !== '') && is_string($maybeTag)) {
                    $tag = $maybeTag;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $tag;
    }
}
