<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Enqueue;

use Brain\Assets\Assets;

class JsEnqueue implements Enqueue
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
     * @return JsEnqueue
     */
    public static function forFile(string $name, Assets $assets): JsEnqueue
    {
        return new static($assets->handleForName($name));
    }

    /**
     * @param string $handle
     * @return JsEnqueue
     */
    public static function create(string $handle): JsEnqueue
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
     * @return JsEnqueue
     */
    public function withCondition(string $condition): JsEnqueue
    {
        wp_scripts()->add_data($this->handle, 'conditional', $condition);

        return $this;
    }

    /**
     * @param string $jsCode
     * @return JsEnqueue
     */
    public function prependInline(string $jsCode): JsEnqueue
    {
        wp_scripts()->add_data($this->handle, 'before', $jsCode);

        return $this;
    }

    /**
     * @param string $jsCode
     * @return JsEnqueue
     */
    public function appendInline(string $jsCode): JsEnqueue
    {
        wp_scripts()->add_data($this->handle, 'after', $jsCode);

        return $this;
    }

    /**
     * @param string $objectName
     * @param array $data
     * @return JsEnqueue
     */
    public function localize(string $objectName, array $data): JsEnqueue
    {
        wp_localize_script($this->handle, $objectName, $data);

        return $this;
    }

    /**
     * @return JsEnqueue
     */
    public function useAsync(): JsEnqueue
    {
        $this->setupFilters()->addAttribute('async', null);

        return $this;
    }

    /**
     * @return JsEnqueue
     */
    public function useDefer(): JsEnqueue
    {
        $this->setupFilters()->addAttribute('defer', null);

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return JsEnqueue
     */
    public function useAttribute(string $name, ?string $value): JsEnqueue
    {
        $this->setupFilters()->addAttribute($name, $value);

        return $this;
    }

    /**
     * @param callable $callback
     * @return JsEnqueue
     */
    public function addFilter(callable $callback): JsEnqueue
    {
        $this->setupFilters()->add($callback);

        return $this;
    }

    /**
     * @return Filters
     */
    private function setupFilters(): Filters
    {
        if (!$this->filters) {
            $this->filters = Filters::forScripts();
            $this->addFilterHooks();
        }

        return $this->filters;
    }

    /**
     * @return void
     *
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    private function addFilterHooks(): void
    {
        // phpcs:enable

        /**
         * @psalm-suppress MissingClosureReturnType
         * @psalm-suppress MissingClosureParamType
         */
        add_filter(
            'script_loader_src',
            function ($src, string $handle) {
                if ($handle !== $this->handle || !is_string($src)) {
                    return $src;
                }

                add_filter(
                    'script_loader_tag',
                    function ($tag, string $handle) use ($src) {
                        if (is_string($tag) && $handle) {
                            return $this->filterTag($tag, $src, $handle);
                        }

                        return $tag;
                    },
                    10,
                    2
                );

                return $src;
            },
            10,
            2
        );
    }

    /**
     * @param string $tag
     * @param string $src
     * @param string $handle
     * @return string
     */
    private function filterTag(string $tag, string $src, string $handle): string
    {
        if (!$tag || $handle !== $this->handle) {
            return $tag;
        }

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
