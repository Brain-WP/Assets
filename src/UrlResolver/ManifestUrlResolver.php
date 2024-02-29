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

namespace Brain\Assets\UrlResolver;

class ManifestUrlResolver implements UrlResolver
{
    private array $paths = [];

    /**
     * @param DirectUrlResolver $direct
     * @param string $manifestPath
     * @return static
     */
    public static function new(DirectUrlResolver $direct, string $manifestPath): static
    {
        return new static($direct, $manifestPath);
    }

    /**
     * @param DirectUrlResolver $directResolver
     * @param string $manifestPath
     */
    final protected function __construct(
        private DirectUrlResolver $directResolver,
        string $manifestPath
    ) {

        $this->determinePaths($manifestPath);
    }

    /**
     * @param string $relative
     * @param MinifyResolver|null $minifyResolver
     * @return string
     */
    public function resolve(string $relative, ?MinifyResolver $minifyResolver): string
    {
        if (!$this->paths) {
            return $this->directResolver->resolve($relative, $minifyResolver);
        }

        $path = trim((string) parse_url($relative, PHP_URL_PATH), '/');
        $manifestPath = $this->paths[$path] ?? null;

        if (($manifestPath === '') || !is_string($manifestPath)) {
            $manifestPath = $path;
        }

        return $this->directResolver->resolve($manifestPath, $minifyResolver);
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     */
    public function resolveAll(): array
    {
        $found = [];
        foreach ($this->paths as $name => $path) {
            if (
                ($name === '')
                || ($path === '')
                || !is_string($name)
                || !is_string($path)
                || (preg_match('~^.+?\.(?:css|js)$~i', $path) === false)
            ) {
                continue;
            }
            $url = $this->directResolver->resolve($path, null);
            ($url !== '') and $found[$name] = $url;
        }

        return $found;
    }

    /**
     * @param string $manifestPath
     * @return void
     */
    private function determinePaths(string $manifestPath): void
    {
        if (!is_readable($manifestPath)) {
            return;
        }

        try {
            $content = @file_get_contents($manifestPath);
            $paths = (($content !== false) && ($content !== ''))
                ? json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR)
                : null;
            if (!is_array($paths)) {
                return;
            }
            foreach ($paths as $name => $path) {
                if (($name === '') || ($path === '') || !is_string($name) || !is_string($path)) {
                    continue;
                }
                $name = ltrim($name, './');
                $path = ltrim($path, './');
                if (($name !== '') && ($path !== '')) {
                    $this->paths[$name] = $path;
                }
            }
        } catch (\Throwable) {
            // silence
        }
    }
}
