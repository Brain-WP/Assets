<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\UrlResolver;

class MinifyResolver
{
    /**
     * @var bool
     */
    private $shouldMinify;

    /**
     * @return MinifyResolver
     */
    public static function createEnabled(): MinifyResolver
    {
        return new static(true);
    }

    /**
     * @return MinifyResolver
     */
    public static function createDisabled(): MinifyResolver
    {
        return new static(false);
    }

    /**
     * @param bool $shouldMinify
     */
    private function __construct(bool $shouldMinify)
    {
        $this->shouldMinify = $shouldMinify;
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function resolve(string $path): ?string
    {
        if (!$this->shouldMinify) {
            return null;
        }

        if (!preg_match("~(?P<file>.+?)(?P<min>\.min)?\.(?P<ext>js|css)\$~i", $path, $matches)) {
            return null;
        }

        if ($matches['min'] ?? null) {
            return null;
        }

        return "{$matches['file']}.min.{$matches['ext']}";
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->shouldMinify;
    }
}
