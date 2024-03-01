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

class JsEnqueue extends AbstractEnqueue
{
    private Filters|null $filters = null;

    /**
     * @param string $handle
     * @param Strategy|null $strategy
     * @return static
     */
    public static function newRegistration(string $handle, ?Strategy $strategy = null): static
    {
        return new static($handle, $strategy, false);
    }

    /**
     * @param string $handle
     * @param Strategy|null $strategy
     * @return static
     */
    public static function new(string $handle, ?Strategy $strategy = null): static
    {
        return new static($handle, $strategy, true);
    }

    /**
     * @param string $handle
     * @param Strategy|null $strategy
     * @param bool $isEnqueue
     */
    final protected function __construct(
        private string $handle,
        private ?Strategy $strategy,
        bool $isEnqueue
    ) {

        $this->isCss = false;
        $this->isEnqueue = $isEnqueue;
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
        wp_scripts()->add_data($this->handle, 'conditional', $condition);

        return $this;
    }

    /**
     * @param string $jsCode
     * @return static
     */
    public function prependInline(string $jsCode): static
    {
        wp_add_inline_script($this->handle, $jsCode, 'before');

        return $this;
    }

    /**
     * @param string $jsCode
     * @return static
     */
    public function appendInline(string $jsCode): static
    {
        wp_add_inline_script($this->handle, $jsCode, 'after');
        $this->removeStrategy();

        return $this;
    }

    /**
     * @param string $objectName
     * @param array $data
     * @return static
     */
    public function localize(string $objectName, array $data): static
    {
        wp_localize_script($this->handle, $objectName, $data);

        return $this;
    }

    /**
     * @param Strategy $strategy
     * @return static
     */
    public function useStrategy(Strategy $strategy): static
    {
        $this->strategy = $strategy;
        $strategyName = match (true) {
            $strategy->isDefer() => Strategy::DEFER,
            $strategy->isAsync() => Strategy::ASYNC,
            default => false,
        };

        wp_scripts()->add_data($this->handle, 'strategy', $strategyName);
        wp_scripts()->add_data($this->handle, 'group', $strategy->inFooter() ? 1 : false);

        return $this;
    }

    /**
     * @return static
     */
    public function useAsync(): static
    {
        return $this->useStrategy(Strategy::newAsync($this->strategy?->inFooter() ?? false));
    }

    /**
     * @return static
     */
    public function useDefer(): static
    {
        return $this->useStrategy(Strategy::newDefer($this->strategy?->inFooter() ?? false));
    }

    /**
     * @param string $name
     * @param string $value
     * @return static
     */
    public function useAttribute(string $name, ?string $value): static
    {
        $nameLower = strtolower($name);
        if (($nameLower !== 'async') && ($nameLower !== 'defer')) {
            $this->setupFilters()->addAttribute($name, $value);

            return $this;
        }

        if (strtolower($value ?? '') === 'false') {
            $this->removeStrategy();

            return $this;
        }

        return ($nameLower === 'async') ? $this->useAsync() : $this->useDefer();
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function addFilter(callable $callback): static
    {
        $this->setupFilters()->add($callback);

        return $this;
    }

    /**
     * @return void
     */
    private function removeStrategy(): void
    {
        if ($this->strategy) {
            $this->strategy = $this->strategy->removeStrategy();
            wp_scripts()->add_data($this->handle, 'strategy', false);
        }
    }

    /**
     * @return Filters
     */
    private function setupFilters(): Filters
    {
        if (!$this->filters) {
            $this->filters = Filters::newForScripts();
            $this->filterScriptLoaderSrc();
        }

        return $this->filters;
    }

    /**
     * @return void
     */
    private function filterScriptLoaderSrc(): void
    {
        add_filter(
            'script_loader_src',
            function (mixed $src, string $handle): mixed {
                if (($handle !== $this->handle) || !is_string($src) || ($src === '')) {
                    return $src;
                }

                return $this->filterScriptLoaderTag($src);
            },
            10,
            2
        );
    }

    /**
     * @param non-empty-string $src
     * @return non-empty-string
     */
    private function filterScriptLoaderTag(string $src): string
    {
        add_filter(
            'script_loader_tag',
            function (mixed $tag, string $handle) use ($src): mixed {
                if (($handle === $this->handle) && is_string($tag) && ($tag !== '')) {
                    return $this->filterTag($tag, $src);
                }

                return $tag;
            },
            10,
            2
        );

        return $src;
    }

    /**
     * @param non-empty-string $tag
     * @param non-empty-string $src
     * @return string
     */
    private function filterTag(string $tag, string $src): string
    {
        $regex = '(?P<before>.+)?'
            . '(?P<tag><script.+?src\s*=\s*[\'"]' . preg_quote($src, '~') . '[\'"].+?</script>)'
            . '(?P<after>.+)?';

        if (!preg_match("~{$regex}~is", $tag, $matches)) {
            return $tag;
        }

        $tagMatch = $matches['tag'] ?? '';
        $tag = $this->filters ? $this->filters->apply($tagMatch) : $tagMatch;
        $before = $matches['before'] ?? '';
        $after = $matches['after'] ?? '';

        return $before . $tag . $after;
    }
}
