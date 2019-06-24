<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\UrlResolver;

use Brain\Assets\Context\Context;

final class DirectUrlResolver implements UrlResolver
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param string $relative
     * @param MinifyResolver $minifyResolver
     * @return string
     */
    public function resolve(string $relative, MinifyResolver $minifyResolver): string
    {
        $baseUrl = $this->context->baseUrl();
        $basePath = $this->context->basePath();

        $urlData = parse_url($relative);
        $path = trim((string)($urlData['path'] ?? ''), '/');
        $query = (string)($urlData['query'] ?? '');

        $minifiedPath = $minifyResolver->resolve($path);

        $hasMin = (bool)$minifiedPath;
        $hasPath =  $path && ($path !== $minifiedPath);

        $candidates = $hasMin ? [[$minifiedPath, $basePath, $baseUrl]] : [];
        $hasPath and $candidates[] = [$path, $basePath, $baseUrl];

        if (($hasMin || $hasPath) && $this->context->hasAlternative()) {
            $altBasePath = $this->context->altBasePath();
            $altBaseUrl = $this->context->altBaseUrl();
            $hasMin and $candidates[] = [$minifiedPath, $altBasePath, $altBaseUrl];
            $hasPath and $candidates[] = [$path, $altBasePath, $altBaseUrl];
        }

        $fullUrl = '';

        /** @psalm-suppress PossiblyNullOperand */
        foreach ($candidates as [$candidatePath, $pathBasePath, $pathBaseUrl]) {
            if (file_exists($pathBasePath . $candidatePath)) {
                $fullUrl = $pathBaseUrl . $candidatePath;
                break;
            }
        }

        if ($fullUrl && $query) {
            $fullUrl .= "?{$query}";
        }

        if (!$fullUrl && $baseUrl && $relative) {
            $fullUrl = $baseUrl . ltrim($relative, '/');
        }

        return $fullUrl;
    }

    /**
     * @param Context $context
     * @return UrlResolver
     */
    public function withContext(Context $context): UrlResolver
    {
        $this->context = $context;

        return $this;
    }
}
