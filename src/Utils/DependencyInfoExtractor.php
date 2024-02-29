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

namespace Brain\Assets\Utils;

class DependencyInfoExtractor
{
    /** @var array<string, array{version?: non-empty-string, dependencies?: non-empty-array}> */
    private static array $cache = [];

    /**
     * @param PathFinder $pathFinder
     * @return static
     */
    public static function new(PathFinder $pathFinder): static
    {
        return new static($pathFinder);
    }

    /**
     * @param PathFinder $pathFinder
     */
    final protected function __construct(
        private PathFinder $pathFinder,
    ) {
    }

    /**
     * @param string $url
     * @return string|null
     */
    public function readVersion(string $url): ?string
    {
        $fullPath = $this->pathFinder->findPath($url);

        if ($fullPath === null) {
            return null;
        }

        return $this->loadDataForPath($fullPath)['version'] ?? null;
    }

    /**
     * @param string $url
     * @return array
     */
    public function readDependencies(string $url): array
    {
        $fullPath = $this->pathFinder->findPath($url);

        if ($fullPath === null) {
            return [];
        }

        return $this->loadDataForPath($fullPath)['dependencies'] ?? [];
    }

    /**
     * @param string $fullPath
     * @param bool $secondAttemp
     * @return array{version?: non-empty-string, dependencies?: non-empty-array}
     */
    private function loadDataForPath(string $fullPath, bool $secondAttempt = false): array
    {
        if (isset(static::$cache[$fullPath])) {
            return static::$cache[$fullPath];
        }

        $phpPath = preg_replace('~\.([a-z0-9_-]+)$~i', '.asset.php', $fullPath);
        if (!file_exists($phpPath) || !is_readable($phpPath)) {
            if (
                !$secondAttempt
                && preg_match('~^(.+?)\.[^\.]+(\.(?:css|js))$~i', $fullPath, $matches)
                && file_exists($fullPath)
            ) {
                $fullPath = $matches[1] . $matches[2];

                return $this->loadDataForPath($fullPath, true);
            }
            static::$cache[$fullPath] = [];

            return [];
        }

        try {
            $data = include $phpPath;
            if (!is_array($data)) {
                static::$cache[$fullPath] = [];

                return [];
            }
            $version = $data['version'] ?? null;
            $deps = $data['dependencies'] ?? null;
            $loaded = [];
            (is_string($version) && ($version !== '')) and $loaded['version'] = $version;
            (is_array($deps) && ($deps !== [])) and $loaded['dependencies'] = $deps;
            static::$cache[$fullPath] = $loaded;

            return $loaded;
        } catch (\Throwable) {
            return [];
        }
    }
}
