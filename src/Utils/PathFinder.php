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

use Brain\Assets\Context\Context;
use Brain\Assets\Version\Version;

class PathFinder
{
    /** @var array<
     *  string,
     *  list{non-falsy-string|null, string|null, non-falsy-string|null, non-falsy-string|null}
     * >
     */
    private array $cache;

    /** @var array<non-falsy-string, list{non-falsy-string, int}> */
    private array $bases;

    /**
     * @param Context $context
     * @return static
     */
    public static function new(Context $context): static
    {
        return new static($context);
    }

    /**
     * @param Context $context
     * @param Version|null $version
     */
    final protected function __construct(Context $context)
    {
        $this->cache = [];
        $this->bases = [];

        /** @var non-falsy-string $baseUrl */
        $baseUrl = strtok($context->baseUrl(), '?');
        /** @var non-falsy-string $basePath */
        $basePath = wp_normalize_path($context->basePath());

        $this->bases[$baseUrl] = [$basePath, strlen($baseUrl)];
        if (!$context->hasAlternative()) {
            return;
        }

        $altBaseUrl = $context->altBaseUrl();
        $altBasePath = $context->altBasePath();
        if (($altBasePath !== null) && ($altBaseUrl !== null)) {
            $altBaseUrl = strtok($altBaseUrl, '?');
            /** @var non-falsy-string $altBaseUrl */
            $this->bases[$altBaseUrl] = [$altBasePath, strlen($altBaseUrl)];
        }
    }

    /**
     * @param string $url
     * @return non-falsy-string|null
     */
    public function findPath(string $url): ?string
    {
        return $this->findPathInfo($url)[0];
    }

    /**
     * @param string $url
     * @return list{
     *     non-falsy-string|null,
     *     string|null,
     *     non-falsy-string|null,
     *     non-falsy-string|null
     * }
     */
    public function findPathInfo(string $url): array
    {
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }

        $theBaseUrl = null;
        $theBasePath = null;
        $relPath = null;
        $fullPath = null;

        foreach ($this->bases as $baseUrl => [$urlBasePath, $baseUrlLength]) {
            $normUrl = strtok($this->matchUrlScheme($baseUrl, $url), '?');
            if (($normUrl !== false) && substr($normUrl, 0, $baseUrlLength) === $baseUrl) {
                $theBaseUrl = $baseUrl;
                $theBasePath = $urlBasePath;
                $relPath = substr($normUrl, $baseUrlLength);
                $fullPath = $urlBasePath . $relPath;
                break;
            }
        }

        $this->cache[$url] = [$fullPath, $relPath, $theBaseUrl, $theBasePath];

        return $this->cache[$url];
    }

    /**
     * @param string $sourceUrl
     * @param string $targetUrl
     * @return string
     */
    private function matchUrlScheme(string $sourceUrl, string $targetUrl): string
    {
        $leftScheme = parse_url($sourceUrl, PHP_URL_SCHEME);
        $rightScheme = parse_url($targetUrl, PHP_URL_SCHEME);

        return ($leftScheme !== $rightScheme)
            ? (string) set_url_scheme($targetUrl, $leftScheme)
            : $targetUrl;
    }
}
