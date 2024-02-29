<?php

declare(strict_types=1);

namespace Brain\Assets\UrlResolver;

use Brain\Assets\Context\Context;

class DirectUrlResolver implements UrlResolver
{
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
     */
    final protected function __construct(private Context $context)
    {
    }

    /**
     * @param string $relative
     * @param MinifyResolver|null $minifyResolver
     * @return string
     */
    public function resolve(string $relative, ?MinifyResolver $minifyResolver): string
    {
        $urlData = parse_url($relative);
        $path = trim(($urlData['path'] ?? ''), '/');

        $candidates = $this->findCandidates($path, $minifyResolver?->resolve($path));

        $fullUrl = '';
        /** @var list<list{string, string, string}> $candidates */
        foreach ($candidates as [$candidatePath, $pathBasePath, $pathBaseUrl]) {
            if (file_exists($pathBasePath . $candidatePath)) {
                $fullUrl = $pathBaseUrl . $candidatePath;
                break;
            }
        }

        $baseUrl = $this->context->baseUrl();
        if (($fullUrl === '') && ($relative !== '')) {
            $fullUrl = $baseUrl . ltrim($relative, '/');
        }

        $query = $urlData['query'] ?? '';
        if (($fullUrl !== '') && ($query !== '')) {
            $fullUrl .= "?{$query}";
        }

        return $fullUrl;
    }

    /**
     * @param string $path
     * @param string|null $minifiedPath
     * @return list<list{string, string, string}>
     */
    private function findCandidates(string $path, ?string $minifiedPath): array
    {
        $baseUrl = $this->context->baseUrl();
        $basePath = $this->context->basePath();

        $hasMin = ($minifiedPath !== '') && ($minifiedPath !== null);
        $hasPath = ($path !== '') && ($path !== $minifiedPath);

        $candidates = $hasMin ? [[$minifiedPath, $basePath, $baseUrl]] : [];
        $hasPath and $candidates[] = [$path, $basePath, $baseUrl];

        if (($hasMin || $hasPath) && $this->context->hasAlternative()) {
            $altBasePath = $this->context->altBasePath() ?? '';
            $altBaseUrl = $this->context->altBaseUrl() ?? '';
            if (($altBasePath !== '') && ($altBaseUrl !== '')) {
                $hasMin and $candidates[] = [$minifiedPath, $altBasePath, $altBaseUrl];
                $hasPath and $candidates[] = [$path, $altBasePath, $altBaseUrl];
            }
        }
        /** @var list<list{string, string, string}> $candidates */
        return $candidates;
    }
}
