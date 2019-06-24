<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Context;

final class WpContext implements Context
{
    /**
     * @var bool
     */
    private $isDebug;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string|null
     */
    private $altBasePath;

    /**
     * @var string|null
     */
    private $altBaseUrl;

    /**
     * @param string $basePath
     * @param string $baseUrl
     * @param string|null $altBasePath
     * @param string|null $altBaseUrl
     * @return WpContext
     */
    public static function createWithNoDebug(
        string $basePath,
        string $baseUrl,
        ?string $altBasePath = null,
        ?string $altBaseUrl = null
    ): WpContext {

        return new static($basePath, $baseUrl, $altBasePath, $altBaseUrl, false);
    }

    /**
     * @param string $basePath
     * @param string $baseUrl
     * @param string|null $altBasePath
     * @param string|null $altBaseUrl
     * @return WpContext
     */
    public static function createWithDebug(
        string $basePath,
        string $baseUrl,
        ?string $altBasePath = null,
        ?string $altBaseUrl = null
    ): WpContext {

        return new static($basePath, $baseUrl, $altBasePath, $altBaseUrl, true);
    }

    /**
     * @return bool
     */
    private static function globalDebug(): bool
    {
        return defined('SCRIPT_DEBUG')
            ? (bool)SCRIPT_DEBUG
            : (defined('WP_DEBUG') && WP_DEBUG);
    }

    /**
     * @param string $basePath
     * @param string $baseUrl
     * @param string|null $altBasePath
     * @param string|null $altBaseUrl
     * @param bool|null $isDebug
     */
    public function __construct(
        string $basePath,
        string $baseUrl,
        ?string $altBasePath = null,
        ?string $altBaseUrl = null,
        ?bool $isDebug = null
    ) {

        $this->basePath = trailingslashit($basePath);
        $this->baseUrl = trailingslashit($baseUrl);
        $this->altBasePath = $altBasePath ? trailingslashit($altBasePath) : null;
        $this->altBaseUrl = $altBaseUrl ? trailingslashit($altBaseUrl) : null;
        $this->isDebug = $isDebug ?? static::globalDebug();
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * @return Context
     */
    public function enableDebug(): Context
    {
        $this->isDebug = true;

        return $this;
    }

    /**
     * @return Context
     */
    public function disableDebug(): Context
    {
        $this->isDebug = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return (bool)is_ssl();
    }

    /**
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return string|null
     */
    public function altBasePath(): ?string
    {
        return $this->altBasePath;
    }

    /**
     * @return string|null
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
