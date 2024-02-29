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

namespace Brain\Assets\Context;

final class WpContext implements Context
{
    /**
     * @param string $name
     * @param non-falsy-string $basePath
     * @param non-falsy-string $baseUrl
     * @param non-falsy-string|null $altBasePath
     * @param non-falsy-string|null $altBaseUrl
     * @param non-falsy-string|null $manifestPath
     * @param bool|null $isDebug
     * @return static
     */
    public static function new(
        string $name,
        string $basePath,
        string $baseUrl,
        ?string $altBasePath = null,
        ?string $altBaseUrl = null,
        ?string $manifestPath = null,
        ?bool $isDebug = null
    ): static {

        return new static(
            $name,
            static::normalizePathOrUrl($basePath, isUrl: false),
            static::normalizePathOrUrl($baseUrl, isUrl: true),
            static::normalizePathOrUrl($altBasePath, isUrl: false),
            static::normalizePathOrUrl($altBaseUrl, isUrl: true),
            static::normalizeManifestPath($manifestPath),
            $isDebug ?? static::globalDebug()
        );
    }

    /**
     * @param string $name
     * @param non-falsy-string $basePath
     * @param non-falsy-string $baseUrl
     * @param non-falsy-string|null $altBasePath
     * @param non-falsy-string|null $altBaseUrl
     * @param non-falsy-string|null $manifestPath
     * @return static
     */
    public static function newWithNoDebug(
        string $name,
        string $basePath,
        string $baseUrl,
        ?string $altBasePath = null,
        ?string $altBaseUrl = null,
        ?string $manifestPath = null
    ): static {

        return new static(
            $name,
            static::normalizePathOrUrl($basePath, isUrl: false),
            static::normalizePathOrUrl($baseUrl, isUrl: true),
            static::normalizePathOrUrl($altBasePath, isUrl: false),
            static::normalizePathOrUrl($altBaseUrl, isUrl: true),
            static::normalizeManifestPath($manifestPath),
            false
        );
    }

    /**
     * @param string $name,
     * @param non-falsy-string $basePath
     * @param non-falsy-string $baseUrl
     * @param non-falsy-string|null $altBasePath
     * @param non-falsy-string|null $altBaseUrl,
     * @param non-falsy-string|null $manifestPath,
     * @return static
     */
    public static function newWithDebug(
        string $name,
        string $basePath,
        string $baseUrl,
        ?string $altBasePath = null,
        ?string $altBaseUrl = null,
        ?string $manifestPath = null
    ): static {

        return new static(
            $name,
            static::normalizePathOrUrl($basePath, isUrl: false),
            static::normalizePathOrUrl($baseUrl, isUrl: true),
            static::normalizePathOrUrl($altBasePath, isUrl: false),
            static::normalizePathOrUrl($altBaseUrl, isUrl: true),
            static::normalizeManifestPath($manifestPath),
            true
        );
    }

    /**
     * @template T of string|null
     *
     * @param T $pathOrUrl
     * @param bool $isUrl
     * @return (T is null ? null : non-falsy-string)
     */
    protected static function normalizePathOrUrl(?string $pathOrUrl, bool $isUrl): ?string
    {
        if ($pathOrUrl === null) {
            return null;
        }

        assert(($pathOrUrl !== '') && ($pathOrUrl !== '0'));

        $isUrl or $pathOrUrl = wp_normalize_path($pathOrUrl);

        /** @var non-falsy-string */
        return trailingslashit($pathOrUrl);
    }

    /**
     * @param string|null $path
     * @return non-falsy-string|null
     */
    protected static function normalizeManifestPath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        if (str_ends_with($path, 'manifest.json')) {
            $path = dirname($path);
        }

        if (($path === '') || ($path === '0')) {
            return null;
        }

        return static::normalizePathOrUrl($path, isUrl: false);
    }

    /**
     * @return bool
     */
    protected static function globalDebug(): bool
    {
        /** @psalm-suppress RedundantCast, TypeDoesNotContainType */
        return defined('SCRIPT_DEBUG') ? (bool) \SCRIPT_DEBUG : (defined('WP_DEBUG') && \WP_DEBUG);
    }

    /**
     * @param string $name
     * @param non-falsy-string $basePath
     * @param non-falsy-string $baseUrl
     * @param non-falsy-string|null $altBasePath
     * @param non-falsy-string|null $altBaseUrl
     * @param non-falsy-string|null $manifestPath
     * @param bool $isDebug
     */
    final protected function __construct(
        private string $name,
        private string $basePath,
        private string $baseUrl,
        private ?string $altBasePath,
        private ?string $altBaseUrl,
        private ?string $manifestPath,
        private bool $isDebug
    ) {
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return static
     */
    public function withName(string $name): static
    {
        return new static(
            $name,
            $this->basePath,
            $this->baseUrl,
            $this->altBasePath,
            $this->altBaseUrl,
            $this->manifestPath,
            $this->isDebug
        );
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * @return static
     */
    public function enableDebug(): static
    {
        $this->isDebug = true;

        return $this;
    }

    /**
     * @return static
     */
    public function disableDebug(): static
    {
        $this->isDebug = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return (bool) is_ssl();
    }

    /**
     * @return non-falsy-string
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * @return non-falsy-string
     */
    public function manifestJsonPath(): string
    {
        return ($this->manifestPath ?? $this->basePath) . 'manifest.json';
    }

    /**
     * @return non-falsy-string
     */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return non-falsy-string|null
     */
    public function altBasePath(): ?string
    {
        return $this->altBasePath;
    }

    /**
     * @return non-falsy-string|null
     */
    public function altBaseUrl(): ?string
    {
        return $this->altBaseUrl;
    }

    /**
     * @return bool
     */
    public function hasAlternative(): bool
    {
        return $this->altBasePath && $this->altBaseUrl;
    }
}
