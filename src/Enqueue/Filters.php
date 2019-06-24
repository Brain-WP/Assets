<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Enqueue;

class Filters
{
    /**
     * @var string
     */
    private $tag;
    
    /**
     * @var callable[]
     */
    private $filters = [];

    /**
     * @var array<string, string|null>
     */
    private $attributes = [];

    /**
     * @return Filters
     */
    public static function forScripts(): Filters
    {
        return new static('script');
    }

    /**
     * @return Filters
     */
    public static function forStyles(): Filters
    {
        return new static('link');
    }

    /**
     * @param string $tag
     */
    private function __construct(string $tag)
    {
        $this->tag = $tag;
    }

    /**
     * @param callable $filter
     * @return Filters
     */
    public function add(callable $filter): Filters
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @param string $name
     * @param string|null $value
     * @return Filters
     */
    public function addAttribute(string $name, ?string $value): Filters
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $name = $name ? (string)preg_replace('~[^a-z0-9_\-]~', '', strtolower($name)) : '';
        if (!$name) {
            return $this;
        }

        $value and $value = trim((string)esc_attr($value));

        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param string $tag
     * @return string
     *
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    public function apply(string $tag): string
    {
        // phpcs:enable

        try {
            foreach ($this->filters as $filter) {
                $tag = $filter($tag);
                if (!$tag || !is_string($tag)) {
                    return '';
                }
            }

            foreach ($this->attributes as $name => $value) {
                if (preg_match('~\s+' . preg_quote($name, '~') . '(?:\s|=|>)~', $tag)) {
                    continue;
                }

                $replace =  $value === null ? $name : "{$name}=\"{$value}\"";
                $tag = str_replace("<{$this->tag}", "<{$this->tag} {$replace}", $tag);
            }

            return $tag;
        } catch (\Throwable $exception) {
            return '';
        }
    }
}
