<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets;

use Brain\Assets\Enqueue;
use Brain\Assets\UrlResolver\DirectUrlResolver;
use Brain\Assets\Version\NoVersion;

class Assets
{
    public const CSS = 'css';
    public const JS = 'js';
    public const IMAGE = 'images';
    public const VIDEO = 'videos';
    public const FONT = 'fonts';

    /**
     * @var string
     */
    private $name;

    /**
     * @var Context\Context
     */
    private $context;

    /**
     * @var Version\Version
     */
    private $version;

    /**
     * @var array<string, string>
     */
    private $subFolders = [];

    /**
     * @var UrlResolver\UrlResolver
     */
    private $urlResolver;

    /**
     * @var UrlResolver\MinifyResolver
     */
    private $minifyResolver;

    /**
     * @var string
     */
    private $prefixHandle = '';

    /**
     * @var bool
     */
    private $forceSecureUrls = true;

    /**
     * @param string $mainPluginFilePath
     * @param string $assetsDir
     * @return Assets
     */
    public static function forPlugin(string $mainPluginFilePath, string $assetsDir = '/'): Assets
    {
        $name = (string)plugin_basename($mainPluginFilePath);
        if (substr_count($name, '/')) {
            $name = explode('/', $name)[0];
        }

        $assetsDir = '/' . trailingslashit(ltrim($assetsDir, '/'));
        $baseDir = dirname($mainPluginFilePath) . $assetsDir;
        $baseUrl = (string)plugins_url($assetsDir, $mainPluginFilePath);

        /**
         * @var Context\Context $context
         * @var Version\Version $version
         * @var UrlResolver\UrlResolver $urlResolver
         * @var UrlResolver\MinifyResolver $minifyResolver
         */
        [$context, $version, $urlResolver, $minifyResolver] = static::factoryDependencies(
            $baseDir,
            $baseUrl
        );

        return new static($name, $context, $version, $urlResolver, $minifyResolver);
    }

    /**
     * @param string $assetsDir
     * @return Assets
     */
    public static function forTheme(string $assetsDir = '/'): Assets
    {
        $name = (string)get_template();
        $dir = trailingslashit(ltrim($assetsDir, '/'));
        $baseDir = trailingslashit((string)get_template_directory()) . $dir;
        $baseUrl = trailingslashit((string)get_template_directory_uri()) . $dir;

        /**
         * @var Context\Context $context
         * @var Version\Version $version
         * @var UrlResolver\UrlResolver $urlResolver
         * @var UrlResolver\MinifyResolver $minifyResolver
         */
        [$context, $version, $urlResolver, $minifyResolver] = static::factoryDependencies(
            $baseDir,
            $baseUrl
        );

        return new static($name, $context, $version, $urlResolver, $minifyResolver);
    }

    /**
     * @param string $assetsDir
     * @param string|null $parentAssetsDir
     * @return Assets
     */
    public static function forChildTheme(
        string $assetsDir = '/',
        ?string $parentAssetsDir = null
    ): Assets {

        $name = (string)get_stylesheet();
        $dir = ltrim($assetsDir, '/');
        $baseDir = trailingslashit((string)get_stylesheet_directory()) . $dir;
        $baseUrl = trailingslashit((string)get_stylesheet_directory_uri()) . $dir;

        $altBaseDir = null;
        $altBaseUrl = null;
        if ($name !== (string)get_template()) {
            $parentDir = ltrim($parentAssetsDir ?? $assetsDir, '/');
            $altBaseDir = trailingslashit((string)get_template_directory()) . $parentDir;
            $altBaseUrl = trailingslashit((string)get_template_directory_uri()) . $parentDir;
        }

        /**
         * @var Context\Context $context
         * @var Version\Version $version
         * @var UrlResolver\UrlResolver $urlResolver
         * @var UrlResolver\MinifyResolver $minifyResolver
         */
        [$context, $version, $urlResolver, $minifyResolver] = static::factoryDependencies(
            $baseDir,
            $baseUrl,
            $altBaseDir,
            $altBaseUrl
        );

        return new static($name, $context, $version, $urlResolver, $minifyResolver);
    }

    /**
     * @param string $name
     * @param string $baseDir
     * @param string $baseUrl
     * @return Assets
     */
    public static function forLibrary(string $name, string $baseDir, string $baseUrl): Assets
    {
        /**
         * @var Context\Context $context
         * @var Version\Version $version
         * @var UrlResolver\UrlResolver $urlResolver
         * @var UrlResolver\MinifyResolver $minifyResolver
         */
        [$context, $version, $urlResolver, $minifyResolver] = static::factoryDependencies(
            $baseDir,
            $baseUrl
        );

        return new static($name, $context, $version, $urlResolver, $minifyResolver);
    }

    /**
     * @param string $name
     * @param string $manifestJsonPath
     * @param string $baseUrl
     * @param Version\Version|null $version
     * @param UrlResolver\MinifyResolver|null $minifyResolver
     * @return Assets
     */
    public static function forManifest(
        string $name,
        string $manifestJsonPath,
        string $baseUrl,
        ?Version\Version $version = null,
        ?UrlResolver\MinifyResolver $minifyResolver = null
    ): Assets {

        $isDir = is_dir($manifestJsonPath);
        $basePath = $isDir ? untrailingslashit($manifestJsonPath) : dirname($manifestJsonPath);
        $context = new Context\WpContext($basePath, $baseUrl);
        $version and $version = $version->withContext($context);

        return new static(
            $name,
            $context,
            $version ?? new NoVersion(),
            new UrlResolver\ManifestUrlResolver(
                new DirectUrlResolver($context),
                $isDir ? "{$basePath}/manifest.json" : $manifestJsonPath
            ),
            $minifyResolver ?? UrlResolver\MinifyResolver::createDisabled()
        );
    }

    /**
     * @param string $basePath
     * @param string $baseUrl
     * @param string|null $altBasePath
     * @param string|null $altBaseUrl
     * @return array
     */
    private static function factoryDependencies(
        string $basePath,
        string $baseUrl,
        ?string $altBasePath = null,
        ?string $altBaseUrl = null
    ): array {

        $context = new Context\WpContext($basePath, $baseUrl, $altBasePath, $altBaseUrl);
        $version = new Version\LastModifiedVersion($context);

        $manifestPath = trailingslashit($basePath) . 'manifest.json';
        $resolver = new UrlResolver\DirectUrlResolver($context);
        $useManifest = file_exists($manifestPath);

        $urlResolver = $useManifest
            ? new UrlResolver\ManifestUrlResolver($resolver, $manifestPath)
            : $resolver;

        $minifyResolver = ($context->isDebug() || $useManifest)
            ? UrlResolver\MinifyResolver::createDisabled()
            : UrlResolver\MinifyResolver::createEnabled();

        return [$context, $version, $urlResolver, $minifyResolver];
    }

    /**
     * @param string $name
     * @param Context\Context $context
     * @param Version\Version|null $version
     * @param UrlResolver\UrlResolver|null $urlResolver
     * @param UrlResolver\MinifyResolver|null $minifyResolver
     */
    private function __construct(
        string $name,
        Context\Context $context,
        ?Version\Version $version = null,
        ?UrlResolver\UrlResolver $urlResolver = null,
        ?UrlResolver\MinifyResolver $minifyResolver = null
    ) {

        $this->name = $name;
        $this->prefixHandle = $name;
        $this->context = $context;
        $this->version = $version ?? new Version\NoVersion();
        $this->urlResolver = $urlResolver ?? new UrlResolver\DirectUrlResolver($context);
        $this->minifyResolver = $minifyResolver ?? UrlResolver\MinifyResolver::createEnabled();
    }

    /**
     * @param string $manifestJsonPath
     * @param string|null $baseUrl
     * @param UrlResolver\MinifyResolver|null $minifyResolver
     * @return Assets
     */
    public function useManifest(
        string $manifestJsonPath,
        ?string $baseUrl = null,
        ?UrlResolver\MinifyResolver $minifyResolver = null
    ): Assets {

        $isDir = is_dir($manifestJsonPath);
        $basePath = $isDir ? untrailingslashit($manifestJsonPath) : dirname($manifestJsonPath);
        $context = new Context\WpContext($basePath, $baseUrl ?? $this->context->baseUrl());
        $this->context = $context;
        $this->minifyResolver = $minifyResolver ?? UrlResolver\MinifyResolver::createDisabled();
        $this->urlResolver = new UrlResolver\ManifestUrlResolver(
            new DirectUrlResolver($context),
            $isDir ? "{$basePath}/manifest.json" : $manifestJsonPath
        );

        return $this;
    }

    /**
     * @return Assets
     */
    public function disableHandlePrefix(): Assets
    {
        $this->prefixHandle = '';

        return $this;
    }

    /**
     * @param string|null $prefix
     * @return Assets
     */
    public function enableHandlePrefix(?string $prefix = null): Assets
    {
        $this->prefixHandle = $prefix ?? $this->name;

        return $this;
    }

    /**
     * @return Assets
     */
    public function dontTryMinUrls(): Assets
    {
        $this->minifyResolver = UrlResolver\MinifyResolver::createDisabled();

        return $this;
    }

    /**
     * @return Assets
     */
    public function tryMinUrls(): Assets
    {
        $this->minifyResolver = UrlResolver\MinifyResolver::createEnabled();

        return $this;
    }

    /**
     * @return Assets
     */
    public function dontAddVersion(): Assets
    {
        $this->version = new Version\NoVersion();

        return $this;
    }

    /**
     * @param Version\Version $version
     * @return Assets
     */
    public function addVersionUsing(Version\Version $version): Assets
    {
        $this->version = $version->withContext($this->context);

        return $this;
    }

    /**
     * @param UrlResolver\UrlResolver $urlResolver
     * @return Assets
     */
    public function resolveUrlsUsing(UrlResolver\UrlResolver $urlResolver): Assets
    {
        $this->urlResolver = $urlResolver->withContext($this->context);

        return $this;
    }

    /**
     * @param Context\Context $context
     * @return Assets
     */
    public function replaceContext(Context\Context $context): Assets
    {
        $this->context = $context;
        $this->version = $this->version->withContext($context);
        $this->urlResolver = $this->urlResolver->withContext($context);

        return $this;
    }

    /**
     * @return Assets
     */
    public function forceDebug(): Assets
    {
        $this->minifyResolver = UrlResolver\MinifyResolver::createDisabled();

        return $this->replaceContext($this->context->enableDebug());
    }

    /**
     * @return Assets
     */
    public function forceNoDebug(): Assets
    {
        $this->minifyResolver = UrlResolver\MinifyResolver::createDisabled();

        return $this->replaceContext($this->context->disableDebug());
    }

    /**
     * @return Assets
     */
    public function forceSecureUrls(): Assets
    {
        $this->forceSecureUrls = true;

        return $this;
    }

    /**
     * @return Assets
     */
    public function dontForceSecureUrls(): Assets
    {
        $this->forceSecureUrls = false;

        return $this;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Context\Context
     */
    public function context(): Context\Context
    {
        return $this->context;
    }

    /**
     * @return Version\Version
     */
    public function version(): Version\Version
    {
        return $this->version;
    }

    /**
     * @return UrlResolver\UrlResolver
     */
    public function urlResolver(): UrlResolver\UrlResolver
    {
        return $this->urlResolver;
    }

    /**
     * @return UrlResolver\MinifyResolver
     */
    public function minifyResolver(): UrlResolver\MinifyResolver
    {
        return $this->minifyResolver;
    }

    /**
     * @param string $path
     * @return Assets
     */
    public function withCssFolder(string $path = 'css'): Assets
    {
        return $this->withSubfolder(self::CSS, $path);
    }

    /**
     * @param string $path
     * @return Assets
     */
    public function withJsFolder(string $path = 'js'): Assets
    {
        return $this->withSubfolder(self::JS, $path);
    }

    /**
     * @param string $path
     * @return Assets
     */
    public function withImagesFolder(string $path = 'images'): Assets
    {
        return $this->withSubfolder(self::IMAGE, $path);
    }

    /**
     * @param string $path
     * @return Assets
     */
    public function withVideosFolder(string $path = 'videos'): Assets
    {
        return $this->withSubfolder(self::VIDEO, $path);
    }

    /**
     * @param string $path
     * @return Assets
     */
    public function withFontsFolder(string $path = 'fonts'): Assets
    {
        return $this->withSubfolder(self::FONT, $path);
    }

    /**
     * @param string $name
     * @param string|null $path
     * @return Assets
     */
    public function withSubfolder(string $name, ?string $path = null): Assets
    {
        $name = trim($name, '/');

        $this->subFolders[$name] = ltrim(trailingslashit($path ?? $name), '/');

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
        $url = $this->rawAssetUrl($relativePath, $folder);

        $version = $this->version->calculate($url);
        if ($version) {
            return (string)add_query_arg(Version\Version::QUERY_VAR, $version, $url);
        }

        return $url;
    }

    /**
     * @param string $filename
     * @param string $folder
     * @return string
     */
    public function rawAssetUrl(string $filename, string $folder = ''): string
    {
        $urlData = (array)(parse_url($filename) ?: []);

        // looks like an absolute URL
        if (!empty($urlData['scheme']) || !empty($urlData['host'])) {
            // let's see if we can reduce it to a relative URL
            $maybeRelative = $this->tryRelativeUrl($filename, $folder);
            if (!$maybeRelative) {
                // if not, we just return it
                return $this->adjustAbsoluteUrl($filename);
            }

            // if yes, we start over with relative URL
            return $this->rawAssetUrl($maybeRelative, $folder);
        }

        if (!array_key_exists('path', $urlData)) {
            return '';
        }

        $path = ltrim((string)$urlData['path'], '/');

        $relativeUrl = $folder
            ? ltrim(trailingslashit(($this->subFolders[$folder] ?? '')) . $path, '/')
            : $path;

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        if (!$ext && in_array($folder, [self::CSS, self::JS], true)) {
            $ext = $folder === self::CSS ? 'css' : 'js';
            $relativeUrl .= ".{$ext}";
        }

        if ($urlData['query'] ?? '') {
            $relativeUrl .= "?{$urlData['query']}";
        }

        $url = $this->urlResolver->resolve($relativeUrl, $this->minifyResolver);

        return $this->adjustAbsoluteUrl($url);
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

        $handle = $this->handleForName($name);

        wp_enqueue_style(
            $handle,
            $this->assetUrl($name, self::CSS),
            $this->prepareDeps($deps),
            null,
            $media
        );

        return new Enqueue\CssEnqueue($handle);
    }

    /**
     * @param string $name
     * @param array $deps
     * @param bool $footer
     * @return Enqueue\JsEnqueue
     */
    public function enqueueScript(
        string $name,
        array $deps = [],
        bool $footer = true
    ): Enqueue\JsEnqueue {

        $handle = $this->handleForName($name);

        wp_enqueue_script(
            $handle,
            $this->assetUrl($name, self::JS),
            $this->prepareDeps($deps),
            null,
            $footer
        );

        return new Enqueue\JsEnqueue($handle);
    }

    /**
     * @param string $handle
     * @param string $url
     * @param array $deps
     * @param string $media
     * @return Enqueue\CssEnqueue
     */
    public function enqueueExternalStyle(
        string $handle,
        string $url,
        array $deps = [],
        string $media = 'all'
    ): Enqueue\CssEnqueue {

        wp_enqueue_style(
            $handle,
            $this->adjustAbsoluteUrl($url),
            $this->prepareDeps($deps),
            null,
            $media
        );

        return new Enqueue\CssEnqueue($handle);
    }

    /**
     * @param string $handle
     * @param string $url
     * @param array $deps
     * @param bool $footer
     * @return Enqueue\JsEnqueue
     */
    public function enqueueExternalScript(
        string $handle,
        string $url,
        array $deps = [],
        bool $footer = true
    ): Enqueue\JsEnqueue {

        wp_enqueue_script(
            $handle,
            $this->adjustAbsoluteUrl($url),
            $this->prepareDeps($deps),
            null,
            $footer
        );

        return new Enqueue\JsEnqueue($handle);
    }

    /**
     * @param string $name
     * @return string
     */
    public function handleForName(string $name): string
    {
        if (!$name) {
            return '';
        }

        $noExt = strtolower(explode('.', $name)[0]);
        $replaced = preg_replace('~[^a-z0-9\-]~i', '-', $noExt);
        $prepared = is_string($replaced) ? trim($replaced, '-') : trim($noExt, '-');

        return $this->prefixHandle ? "{$this->prefixHandle}-{$prepared}" : $prepared;
    }

    /**
     * @param string $url
     * @return string
     */
    private function adjustAbsoluteUrl(string $url): string
    {
        if (substr($url, 0, 2) === '//') {
            return $url;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!$scheme) {
            return (string)set_url_scheme($url);
        }

        if ($scheme === 'http' && is_ssl() && $this->forceSecureUrls) {
            return (string)set_url_scheme($url, 'https');
        }

        return $url;
    }

    /**
     * @param string $url
     * @param string $folder
     * @param string|null $baseUrl
     * @return string
     */
    private function tryRelativeUrl(string $url, string $folder, ?string $baseUrl = null): string
    {
        $urlData = parse_url($url);
        $baseData = parse_url($baseUrl ?? $this->context->baseUrl());

        $urlHost = (string)($urlData['host'] ?? '');
        $urlPath = trim((string)($urlData['path'] ?? ''), '/');
        $urlQuery = (string)($urlData['query'] ?? '');

        $basePath = trim((string)($baseData['path'] ?? ''), '/');
        $baseHost = (string)($baseData['host'] ?? '');

        $subFolder = trim(($this->subFolders[$folder] ?? ''));
        $subFolder and $basePath .= "/{$subFolder}";

        $urlComp = trailingslashit($urlHost) . $urlPath;
        $baseComp = trailingslashit($baseHost) . $basePath;

        if (strpos($urlComp, $baseComp) === 0) {
            $relative = trim((string)substr($urlComp, strlen($baseComp)), '/');

            return $urlQuery ? "{$relative}?{$urlQuery}" : $relative;
        }

        if ($baseUrl === null && $this->context()->hasAlternative()) {
            return $this->tryRelativeUrl($url, $folder, $this->context->altBaseUrl());
        }

        return '';
    }

    /**
     * @param array $deps
     * @return string[]
     */
    private function prepareDeps(array $deps): array
    {
        $prepared = [];

        foreach ($deps as $dep) {
            $name = null;

            if (is_string($dep)) {
                $name = $dep;
            } elseif ($dep instanceof Enqueue\Enqueue) {
                $name = $dep->handle();
            }

            if ($name && !in_array($name, $prepared, true)) {
                $prepared[] = $name;
            }
        }

        return $prepared;
    }
}
