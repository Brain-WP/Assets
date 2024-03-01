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

namespace Brain\Assets;

class Assets
{
    public const CSS = 'css';
    public const JS = 'js';
    public const IMAGE = 'images';
    public const VIDEO = 'videos';
    public const FONT = 'fonts';

    private Enqueue\Collection $collection;
    private UrlResolver\MinifyResolver|null $minifyResolver = null;
    private string $handlePrefix;
    private bool $addVersion = true;
    private bool $forceSecureUrls = true;
    private bool $useDepExtractionData = false;
    /** @var array<string, list{string, bool}> */
    private array $subFolders = [];
    private bool $removalConfigured = false;

    /**
     * @param string $mainPluginFilePath
     * @param string $assetsDir
     * @return static
     */
    public static function forPlugin(string $mainPluginFilePath, string $assetsDir = '/'): static
    {
        $context = Factory::factoryContextForPlugin($mainPluginFilePath, $assetsDir);

        return new static(Factory::new($context));
    }

    /**
     * @param string $assetsDir
     * @return static
     */
    public static function forTheme(string $assetsDir = '/'): static
    {
        return new static(Factory::new(Factory::factoryContextForTheme($assetsDir)));
    }

    /**
     * @param string $assetsDir
     * @param string|null $parentAssetsDir
     * @return static
     */
    public static function forChildTheme(
        string $assetsDir = '/',
        ?string $parentAssetsDir = null
    ): static {

        $context = Factory::factoryContextForChildTheme($assetsDir, $parentAssetsDir);

        return new static(Factory::new($context));
    }

    /**
     * @param non-empty-string $name
     * @param non-falsy-string $baseDir
     * @param non-falsy-string $baseUrl
     * @return static
     */
    public static function forLibrary(string $name, string $baseDir, string $baseUrl): static
    {
        $context = Factory::factoryContextForLibrary($name, $baseDir, $baseUrl);

        return new static(Factory::new($context));
    }

    /**
     * @param non-empty-string $name
     * @param non-falsy-string $manifestJsonPath
     * @param non-falsy-string $baseUrl
     * @param non-falsy-string|null $basePath
     * @param non-falsy-string|null $altBasePath
     * @param non-falsy-string|null $altBaseUrl
     * @return static
     */
    public static function forManifest(
        string $name,
        string $manifestJsonPath,
        string $baseUrl,
        ?string $basePath = null,
        ?string $altBasePath = null,
        ?string $altBaseUrl = null
    ): static {

        $context = Factory::factoryContextForManifest(
            $name,
            $manifestJsonPath,
            $baseUrl,
            $basePath,
            $altBasePath,
            $altBaseUrl
        );

        return new static(Factory::new($context));
    }

    /**
     * @param Context\Context $context
     * @return static
     */
    public static function forContext(Context\Context $context): static
    {
        return new static(Factory::new($context));
    }

    /**
     * @param Factory $factory
     * @return static
     */
    public static function forFactory(Factory $factory): static
    {
        return new static($factory);
    }

    /**
     * @param Factory $factory
     */
    final protected function __construct(private Factory $factory)
    {
        // Store separately from name, so we can enable & disable as well as changing it.
        $this->handlePrefix = $this->context()->name();
        $this->collection = Enqueue\Collection::new($this);
    }

    /**
     * @return Enqueue\Collection
     */
    public function collection(): Enqueue\Collection
    {
        return clone $this->collection;
    }

    /**
     * @return string
     */
    public function handlePrefix(): string
    {
        return $this->handlePrefix;
    }

    /**
     * @return static
     */
    public function disableHandlePrefix(): static
    {
        $this->handlePrefix = '';

        return $this;
    }

    /**
     * @param string|null $prefix
     * @return static
     */
    public function enableHandlePrefix(?string $prefix = null): static
    {
        $this->handlePrefix = $prefix ?? $this->context()->name();

        return $this;
    }

    /**
     * @return static
     */
    public function useDependencyExtractionData(): static
    {
        $this->useDepExtractionData = true;

        return $this;
    }

    /**
     * @return static
     */
    public function dontUseDependencyExtractionData(): static
    {
        $this->useDepExtractionData = false;

        return $this;
    }

    /**
     * @return static
     */
    public function dontTryMinUrls(): static
    {
        $this->minifyResolver = null;

        return $this;
    }

    /**
     * @return static
     */
    public function tryMinUrls(): static
    {
        $this->minifyResolver = $this->factory->minifyResolver();

        return $this;
    }

    /**
     * @return static
     */
    public function dontAddVersion(): static
    {
        $this->addVersion = false;

        return $this;
    }

    /**
     * @return static
     */
    public function forceDebug(): static
    {
        $this->factory->context()->enableDebug();

        return $this;
    }

    /**
     * @return static
     */
    public function forceNoDebug(): static
    {
        $this->factory->context()->disableDebug();

        return $this;
    }

    /**
     * @return static
     */
    public function forceSecureUrls(): static
    {
        $this->forceSecureUrls = true;

        return $this;
    }

    /**
     * @return static
     */
    public function dontForceSecureUrls(): static
    {
        $this->forceSecureUrls = false;

        return $this;
    }

    /**
     * @return Context\Context
     */
    public function context(): Context\Context
    {
        return $this->factory->context();
    }

    /**
     * @param string $path
     * @return static
     */
    public function withCssFolder(string $path = 'css'): static
    {
        return $this->withSubfolder(self::CSS, $path, true);
    }

    /**
     * @param string $path
     * @return static
     */
    public function withJsFolder(string $path = 'js'): static
    {
        return $this->withSubfolder(self::JS, $path, true);
    }

    /**
     * @param string $path
     * @return static
     */
    public function withImagesFolder(string $path = 'images'): static
    {
        return $this->withSubfolder(self::IMAGE, $path, false);
    }

    /**
     * @param string $path
     * @return static
     */
    public function withVideosFolder(string $path = 'videos'): static
    {
        return $this->withSubfolder(self::VIDEO, $path, false);
    }

    /**
     * @param string $path
     * @return static
     */
    public function withFontsFolder(string $path = 'fonts'): static
    {
        return $this->withSubfolder(self::FONT, $path, false);
    }

    /**
     * @param string $name
     * @param string|null $path
     * @param bool $supportMinify
     * @return static
     */
    public function withSubfolder(
        string $name,
        ?string $path = null,
        bool $supportMinify = false
    ): static {

        $name = trim($name, '/');
        $this->subFolders[$name] = [ltrim(trailingslashit($path ?? $name), '/'), $supportMinify];

        return $this;
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function imgUrl(string $relativePath): string
    {
        return $this->assetUrl($relativePath, self::IMAGE);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function rawImgUrl(string $relativePath): string
    {
        return $this->rawAssetUrl($relativePath, self::IMAGE);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function cssUrl(string $relativePath): string
    {
        return $this->assetUrl($relativePath, self::CSS);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function rawCssUrl(string $relativePath): string
    {
        return $this->rawAssetUrl($relativePath, self::CSS);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function jsUrl(string $relativePath): string
    {
        return $this->assetUrl($relativePath, self::JS);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function rawJsUrl(string $relativePath): string
    {
        return $this->rawAssetUrl($relativePath, self::JS);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function videoUrl(string $relativePath): string
    {
        return $this->assetUrl($relativePath, self::VIDEO);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function rawVideoUrl(string $relativePath): string
    {
        return $this->rawAssetUrl($relativePath, self::VIDEO);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function fontsUrl(string $relativePath): string
    {
        return $this->assetUrl($relativePath, self::FONT);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function rawFontsUrl(string $relativePath): string
    {
        return $this->rawAssetUrl($relativePath, self::FONT);
    }

    /**
     * @param string $relativePath
     * @param string $folder
     * @return string
     */
    public function assetUrl(string $relativePath, string $folder = ''): string
    {
        [$url] = $this->assetUrlForEnqueue($relativePath, $folder);

        return $url;
    }

    /**
     * @param string $filename
     * @param string $folder
     * @return string
     */
    public function rawAssetUrl(string $filename, string $folder = ''): string
    {
        [$url] = $this->prepareRawUrlData($filename, $folder);

        return $url;
    }

    /**
     * @param string $name
     * @param array $deps
     * @param string $media
     * @return Enqueue\CssEnqueue
     */
    public function registerStyle(
        string $name,
        array $deps = [],
        string $media = 'all'
    ): Enqueue\CssEnqueue {

        return $this->doEnqueueOrRegisterStyle('register', $name, null, $deps, $media);
    }

    /**
     * @param string $name
     * @param array $deps
     * @param string $media
     * @return Enqueue\CssEnqueue
     */
    public function enqueueStyle(
        string $name,
        array $deps = [],
        string $media = 'all'
    ): Enqueue\CssEnqueue {

        return $this->doEnqueueOrRegisterStyle('enqueue', $name, null, $deps, $media);
    }

    /**
     * @param string $name
     * @param string $url
     * @param array $deps
     * @param string $media
     * @return Enqueue\CssEnqueue
     */
    public function registerExternalStyle(
        string $name,
        string $url,
        array $deps = [],
        string $media = 'all'
    ): Enqueue\CssEnqueue {

        return $this->doEnqueueOrRegisterStyle('register', $name, $url, $deps, $media);
    }

    /**
     * @param string $name
     * @param string $url
     * @param array $deps
     * @param string $media
     * @return Enqueue\CssEnqueue
     */
    public function enqueueExternalStyle(
        string $name,
        string $url,
        array $deps = [],
        string $media = 'all'
    ): Enqueue\CssEnqueue {

        return $this->doEnqueueOrRegisterStyle('enqueue', $name, $url, $deps, $media);
    }

    /**
     * @param string $name
     * @param array $deps
     * @param Enqueue\Strategy|bool|array|string|null $strategy
     * @return Enqueue\JsEnqueue
     */
    public function registerScript(
        string $name,
        array $deps = [],
        Enqueue\Strategy|bool|array|string|null $strategy = null
    ): Enqueue\JsEnqueue {

        return $this->doEnqueueOrRegisterScript('register', $name, null, $deps, $strategy);
    }

    /**
     * @param string $name
     * @param array $deps
     * @param Enqueue\Strategy|bool|array|string|null $strategy
     * @return Enqueue\JsEnqueue
     */
    public function enqueueScript(
        string $name,
        array $deps = [],
        Enqueue\Strategy|bool|array|string|null $strategy = null
    ): Enqueue\JsEnqueue {

        return $this->doEnqueueOrRegisterScript('enqueue', $name, null, $deps, $strategy);
    }

    /**
     * @param string $name
     * @param string $url
     * @param array $deps
     * @param Enqueue\Strategy|bool|array|string|null $strategy
     * @return Enqueue\JsEnqueue
     */
    public function registerExternalScript(
        string $name,
        string $url,
        array $deps = [],
        Enqueue\Strategy|bool|array|string|null $strategy = null
    ): Enqueue\JsEnqueue {

        return $this->doEnqueueOrRegisterScript('register', $name, $url, $deps, $strategy);
    }

    /**
     * @param string $name
     * @param string $url
     * @param array $deps
     * @param Enqueue\Strategy|bool|array $strategy
     * @return Enqueue\JsEnqueue
     */
    public function enqueueExternalScript(
        string $name,
        string $url,
        array $deps = [],
        Enqueue\Strategy|bool|array $strategy = true
    ): Enqueue\JsEnqueue {

        return $this->doEnqueueOrRegisterScript('enqueue', $name, $url, $deps, $strategy);
    }

    /**
     * @param string $name
     * @return static
     */
    public function dequeueStyle(string $name): static
    {
        return $this->deregisterOrDequeue($name, self::CSS, deregister: false);
    }

    /**
     * @param string $name
     * @return static
     */
    public function deregisterStyle(string $name): static
    {
        return $this->deregisterOrDequeue($name, self::CSS, deregister: true);
    }

    /**
     * @param string $name
     * @return static
     */
    public function dequeueScript(string $name): static
    {
        return $this->deregisterOrDequeue($name, self::JS, deregister: false);
    }

    /**
     * @param string $name
     * @return static
     */
    public function deregisterScript(string $name): static
    {
        return $this->deregisterOrDequeue($name, self::JS, deregister: true);
    }

    /**
     * @param string $name
     * @return string
     */
    public function handleForName(string $name): string
    {
        if ($name === '') {
            return '';
        }

        $replaced = preg_replace(['~\.[a-z0-9_-]+$~i', '~[^a-z0-9\-]+~i'], ['', '-'], $name);
        $prepared = is_string($replaced) ? trim($replaced, '-') : trim($name, '-');

        return ($this->handlePrefix !== '') ? "{$this->handlePrefix}-{$prepared}" : $prepared;
    }

    /**
     * @param array $jsDeps
     * @param array $cssDeps
     * @return Enqueue\Collection
     */
    public function registerAllFromManifest(
        array $jsDeps = [],
        array $cssDeps = []
    ): Enqueue\Collection {

        $collection = [];
        $urls = $this->factory->manifestUrlResolver()->resolveAll();
        foreach ($urls as $name => $url) {
            $registered = str_ends_with(strtolower($name), '.css')
                ? $this->registerStyle($name, $cssDeps)
                : $this->registerScript($name, $jsDeps);
            do_action('brain.assets.registered-from-manifest', $registered);
            $collection[] = $registered;
        }

        $registeredAll = Enqueue\Collection::new($this, ...$collection);
        do_action('brain.assets.registered-all-from-manifest', $registeredAll);

        return $registeredAll;
    }

    /**
     * @param 'register'|'enqueue' $type
     * @param string $name
     * @param string|null $url
     * @param array $deps
     * @param string $media
     * @return Enqueue\CssEnqueue
     */
    private function doEnqueueOrRegisterStyle(
        string $type,
        string $name,
        ?string $url,
        array $deps,
        string $media
    ): Enqueue\CssEnqueue {

        $isEnqueue = $type === 'enqueue';
        $handle = $this->handleForName($name);

        $existing = $this->maybeRegistered($handle, $isEnqueue, self::CSS);
        if ($existing instanceof Enqueue\CssEnqueue) {
            return $existing;
        }

        [$url, $useMinify] = ($url === null)
            ? $this->assetUrlForEnqueue($name, self::CSS)
            : [$this->adjustAbsoluteUrl($url), false];

        $deps = $this->prepareDeps($deps, $url, $useMinify);

        /** @var callable $callback */
        $callback = $isEnqueue ? 'wp_enqueue_style' : 'wp_register_style';

        $callback($handle, $url, $deps, null, $media);

        $enqueued = $isEnqueue
            ? Enqueue\CssEnqueue::new($handle)
            : Enqueue\CssEnqueue::newRegistration($handle);
        $this->collection = $this->collection->append($enqueued);
        $this->setupRemoval();

        return $enqueued;
    }

    /**
     * @param 'register'|'enqueue' $type
     * @param string $name
     * @param string|null $url
     * @param array $deps
     * @param Enqueue\Strategy|bool|array|string|null $strategy
     * @return Enqueue\JsEnqueue
     */
    private function doEnqueueOrRegisterScript(
        string $type,
        string $name,
        ?string $url,
        array $deps,
        Enqueue\Strategy|bool|array|string|null $strategy
    ): Enqueue\JsEnqueue {

        $isEnqueue = $type === 'enqueue';
        $handle = $this->handleForName($name);

        $existing = $this->maybeRegistered($handle, $isEnqueue, self::JS);
        if ($existing instanceof Enqueue\JsEnqueue) {
            return $existing;
        }

        [$url, $useMinify] = ($url === null)
            ? $this->assetUrlForEnqueue($name, self::JS)
            : [$this->adjustAbsoluteUrl($url), false];

        $strategy = Enqueue\Strategy::new($strategy);
        $deps = $this->prepareDeps($deps, $url, $useMinify);

        /** @var callable $callback */
        $callback = $isEnqueue ? 'wp_enqueue_script' : 'wp_register_script';

        $callback($handle, $url, $deps, null, $strategy->toArray());

        $enqueued = $isEnqueue
            ? Enqueue\JsEnqueue::new($handle, $strategy)
            : Enqueue\JsEnqueue::newRegistration($handle, $strategy);
        $this->collection = $this->collection->append($enqueued);
        $this->setupRemoval();

        return $enqueued;
    }

    /**
     * @param string $name
     * @param 'css'|'js' $type
     * @param bool $deregister
     * @return static
     */
    private function deregisterOrDequeue(string $name, string $type, bool $deregister): static
    {
        $handle = $this->handleForName($name);
        $existing = $this->maybeRegistered($handle, null, $type);
        if ($existing instanceof Enqueue\Enqueue) {
            $deregister ? $existing->deregister() : $existing->dequeue();

            return $this;
        }

        match ($type) {
            'css' => $deregister ? wp_deregister_style($handle) : wp_dequeue_script($handle),
            'js' => $deregister ? wp_deregister_script($handle) : wp_dequeue_script($handle),
        };

        return $this;
    }

    /**
     * @param string $filename
     * @param string $folder
     * @return list{string, array, bool}
     */
    private function prepareRawUrlData(string $filename, string $folder = ''): array
    {
        $urlData = parse_url($filename);

        // Looks like an absolute URL
        if (isset($urlData['scheme']) || isset($urlData['host'])) {
            return [$this->rawUrlFromAbsolute($filename, $folder), $urlData, false];
        }

        $path = $urlData['path'] ?? null;
        if (($path === null) || ($path === '') || (trim($path, '/') === '')) {
            return ['', $urlData, false];
        }

        $relativeUrl = $this->buildRelativeUrl($folder, ltrim($path, '/'), $urlData['query'] ?? '');

        $supportMinify = $this->subFolders[$folder][1] ?? false;
        $minifyResolver = ($supportMinify && !$this->context()->isDebug())
            ? $this->minifyResolver
            : null;
        $url = $this->factory->urlResolver()->resolve($relativeUrl, $minifyResolver);

        return [$this->adjustAbsoluteUrl($url), $urlData, $minifyResolver !== null];
    }

    /**
     * @param string $relativePath
     * @param string $folder
     * @return list{string, bool}
     */
    private function assetUrlForEnqueue(string $relativePath, string $folder = ''): array
    {
        [$url, $urlData, $useMinify] = $this->prepareRawUrlData($relativePath, $folder);

        if (!$this->addVersion) {
            return [$url, false];
        }

        $query = $urlData['query'] ?? null;
        if (($query !== '') && ($query !== false) && ($query !== null)) {
            return [$url, false];
        }

        $version = $this->prepareVersion($url, $useMinify);

        if (($version !== null) && ($version !== '')) {
            $url = (string) add_query_arg(Version\Version::QUERY_VAR, $version, $url);
        }

        return [$url, $useMinify];
    }

    /**
     * @param string $filename
     * @param string $folder
     * @return string
     */
    private function rawUrlFromAbsolute(string $filename, string $folder): string
    {
        // Let's see if we can reduce it to a relative URL
        $maybeRelative = $this->tryRelativeUrl($filename, $folder);
        if ($maybeRelative === '') {
            // If not, we just return it
            return $this->adjustAbsoluteUrl($filename);
        }

        // If yes, we start over with relative URL
        return $this->rawAssetUrl($maybeRelative, $folder);
    }

    /**
     * @param string $folder
     * @param string $path
     * @param string $query
     * @return string
     */
    private function buildRelativeUrl(string $folder, string $path, string $query): string
    {
        $relativeUrl = $folder
            ? ltrim(trailingslashit(($this->subFolders[$folder][0] ?? '')) . $path, '/')
            : $path;

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        if (($ext === '') && in_array($folder, [self::CSS, self::JS], true)) {
            $ext = $folder === self::CSS ? 'css' : 'js';
            $relativeUrl .= ".{$ext}";
        }

        ($query !== '') and $relativeUrl .= "?{$query}";

        return $relativeUrl;
    }

    /**
     * @param string $url
     * @return string
     */
    private function adjustAbsoluteUrl(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return $url;
        }

        $forceHttps = is_ssl() && $this->forceSecureUrls;
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (
            ($scheme === '')
            || ($scheme === false)
            || ((strtolower($scheme) !== 'https') && $forceHttps)
        ) {
            return (string) set_url_scheme($url, $forceHttps ? 'https' : null);
        }

        return $url;
    }

    /**
     * @param string $url
     * @param string $folder
     * @param string|null $baseUrl
     * @return string
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    private function tryRelativeUrl(string $url, string $folder, ?string $baseUrl = null): string
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
        $urlData = parse_url($url);
        $baseData = parse_url($baseUrl ?? $this->context()->baseUrl());

        $urlHost = $urlData['host'] ?? '';
        $urlPath = trim($urlData['path'] ?? '', '/');
        $urlQuery = $urlData['query'] ?? '';

        $basePath = trim($baseData['path'] ?? '', '/');
        $baseHost = $baseData['host'] ?? '';

        $subFolder = trim(($this->subFolders[$folder][0] ?? ''));
        $subFolder and $basePath .= "/{$subFolder}";

        $urlComp = trailingslashit($urlHost) . $urlPath;
        $baseComp = trailingslashit($baseHost) . $basePath;

        if (str_starts_with($urlComp, $baseComp)) {
            $relative = trim(substr($urlComp, strlen($baseComp)), '/');

            return $urlQuery ? "{$relative}?{$urlQuery}" : $relative;
        }

        if (($baseUrl === null) && $this->context()->hasAlternative()) {
            return $this->tryRelativeUrl($url, $folder, $this->context()->altBaseUrl());
        }

        return '';
    }

    /**
     * @param string $url
     * @param bool $useMinify
     * @return string|null
     */
    private function prepareVersion(string $url, bool $useMinify): ?string
    {
        $factory = $this->factory;

        $versionStr = match (true) {
            $this->context()->isDebug() => str_replace(' ', '-', microtime()),
            $this->useDepExtractionData => $factory->dependencyInfoExtractor()->readVersion($url),
            default => null,
        };

        $urlNoMin = (($versionStr === null) && $this->useDepExtractionData && $useMinify)
            ? $this->unminifiedUrl($url)
            : null;
        if ($urlNoMin !== null) {
            $versionStr = $factory->dependencyInfoExtractor()->readVersion($urlNoMin);
        }

        $version = $factory->version();

        $versionStr ??= $version->calculate($url);
        if (($versionStr === null) && ($urlNoMin !== null)) {
            $versionStr = $version->calculate($urlNoMin);
        }

        return $versionStr;
    }

    /**
     * @param array $deps
     * @param string|null $url
     * @param bool $useMinify
     * @return list<non-empty-string>
     */
    private function prepareDeps(array $deps, ?string $url, bool $useMinify): array
    {
        $prepared = [];

        foreach ($deps as $dep) {
            $name = null;
            if (is_string($dep)) {
                $name = $dep;
            } elseif ($dep instanceof Enqueue\Enqueue) {
                $name = $dep->handle();
            }

            if (($name !== null) && ($name !== '')) {
                $prepared[] = $name;
            }
        }

        if (($url === null) || !$this->useDepExtractionData) {
            /** @var list<non-empty-string> */
            return array_unique($prepared);
        }

        $infoExtractor = $this->factory->dependencyInfoExtractor();
        $deps = $infoExtractor->readDependencies($url);
        if ($useMinify && ($deps === [])) {
            $urlNoMin = $this->unminifiedUrl($url);
            if ($urlNoMin !== null) {
                $deps = $infoExtractor->readDependencies($urlNoMin);
            }
        }

        foreach ($deps as $dep) {
            if (is_string($dep) && ($dep !== '')) {
                $prepared[] = $dep;
            }
        }

        /** @var list<non-empty-string> */
        return array_unique($prepared);
    }

    /**
     * @param string $url
     * @return string|null
     */
    private function unminifiedUrl(string $url): ?string
    {
        if (preg_match('~^(.+?)\.min\.(css|js)$~i', $url, $matches) === 1) {
            return "{$matches[1]}.{$matches[2]}";
        }

        return null;
    }

    /**
     * @param string $handle
     * @param bool|null $enqueue
     * @param 'css'|'js' $type
     * @return Enqueue\Enqueue|null
     */
    private function maybeRegistered(string $handle, ?bool $enqueue, string $type): ?Enqueue\Enqueue
    {
        $existing = $this->collection()->oneByHandle($handle, $type);
        if (($existing !== null) || ($enqueue === null)) {
            if (($existing !== null) && ($enqueue === true) && !$existing->isEnqueued()) {
                $existing->enqueue();
            }

            return $existing;
        }

        if (($type === 'css') && wp_styles()->query($handle)) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        } elseif (($type === 'js') && wp_scripts()->query($handle)) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }

        return null;
    }

    /**
     * @return void
     */
    private function setupRemoval(): void
    {
        $this->removalConfigured or $this->removalConfigured = add_action(
            'brain.assets.deregistered',
            function (Enqueue\Enqueue $enqueue): void {
                $this->collection = $this->collection->remove($enqueue);
            }
        );
    }
}
