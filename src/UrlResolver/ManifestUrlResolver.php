<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\UrlResolver;

use Brain\Assets\Context\Context;

class ManifestUrlResolver implements UrlResolver
{
    /**
     * @var UrlResolver
     */
    private $direct;

    /**
     * @var array
     */
    private $paths = [];

    /**
     * @param DirectUrlResolver $direct
     * @param string $manifestPath
     */
    public function __construct(DirectUrlResolver $direct, string $manifestPath)
    {
        $this->direct = $direct;

        if (is_readable($manifestPath)) {
            $content = @file_get_contents($manifestPath) ?: '';
            $paths = @json_decode($content, true);

            is_array($paths) and $this->paths = $paths;
        }
    }

    /**
     * @param string $relative
     * @param MinifyResolver $minifyResolver
     * @return string
     */
    public function resolve(string $relative, MinifyResolver $minifyResolver): string
    {
        if (!$this->paths) {
            return $this->direct->resolve($relative, $minifyResolver);
        }

        $path = trim((string)parse_url($relative, PHP_URL_PATH), '/');
        $manifestPath = $this->paths[$path] ?? null;

        if (!$manifestPath || !is_string($manifestPath)) {
            $manifestPath = $path;
        }

        return $this->direct->resolve($manifestPath, $minifyResolver);
    }

    /**
     * @param Context $context
     * @return UrlResolver
     */
    public function withContext(Context $context): UrlResolver
    {
        $this->direct = $this->direct->withContext($context);

        return $this;
    }
}
