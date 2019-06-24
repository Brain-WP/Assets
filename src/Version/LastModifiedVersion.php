<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Version;

use Brain\Assets\Context\Context;

final class LastModifiedVersion implements Version
{
    /**
     * @var array<string, array{0:string, 1:int}>
     */
    private $bases = [];

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        if ($context->isDebug()) {
            $this->debug = true;

            return;
        }

        $baseUrl = (string)$context->baseUrl();
        $basePath = (string)$context->basePath();

        $this->bases[$baseUrl] = [$basePath, strlen($baseUrl)];
        if ($context->hasAlternative()) {
            $altBaseUrl = (string)$context->altBaseUrl();
            $altBasePath = (string)$context->altBasePath();
            $this->bases[$altBaseUrl] = [$altBasePath, strlen($altBaseUrl)];
        }
    }

    /**
     * @param string $url
     * @return string
     */
    public function calculate(string $url): ?string
    {
        if (!$this->bases || $this->debug) {
            return $this->debug ? (string)time() : null;
        }

        $relativePath = null;
        $fullPath = null;

        foreach ($this->bases as $baseUrl => [$urlBasePath, $baseUrlLength]) {
            if (substr($this->matchScheme($baseUrl, $url), 0, $baseUrlLength) === $baseUrl) {
                $fullPath = $urlBasePath . (string)substr($url, $baseUrlLength);
                break;
            }
        }

        if (!$fullPath) {
            return null;
        }

        $content = @filemtime($fullPath);

        return $content ? (string)$content : null;
    }

    /**
     * @param string $sourceUrl
     * @param string $targetUrl
     * @return string
     */
    private function matchScheme(string $sourceUrl, string $targetUrl): string
    {
        $leftScheme = parse_url($sourceUrl, PHP_URL_SCHEME);
        $rightScheme = parse_url($targetUrl, PHP_URL_SCHEME);

        return $leftScheme !== $rightScheme
            ? (string)set_url_scheme($targetUrl, $leftScheme)
            : $targetUrl;
    }

    /**
     * @param Context $context
     * @return Version
     */
    public function withContext(Context $context): Version
    {
        $this->debug = $context->isDebug();

        return $this;
    }
}
