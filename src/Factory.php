<?php

declare(strict_types=1);

namespace Brain\Assets;

class Factory
{
    private bool|null $hasManifest = null;
    /** @var array<class-string, object> */
    private array $objects = [];
    /** @var array<class-string, callable(Factory):object> */
    private mixed $factories = [];

    /**
     * @param string $mainPluginFilePath
     * @param string $assetsDir
     * @return Context\Context
     */
    public static function factoryContextForPlugin(
        string $mainPluginFilePath,
        string $assetsDir = '/'
    ): Context\Context {

        $name = (string) plugin_basename($mainPluginFilePath);
        if (substr_count($name, '/') > 0) {
            $name = explode('/', $name)[0];
        }

        $assetsDir = '/' . trailingslashit(ltrim($assetsDir, '/'));
        $baseDir = dirname($mainPluginFilePath) . $assetsDir;
        /** @var non-falsy-string $baseUrl */
        $baseUrl = (string) plugins_url($assetsDir, $mainPluginFilePath);

        return Context\WpContext::new($name, $baseDir, $baseUrl, null, null);
    }

    /**
     * @param string $assetsDir
     * @return Context\Context
     */
    public static function factoryContextForTheme(string $assetsDir = '/'): Context\Context
    {
        $name = (string) get_template();
        $dir = trailingslashit(ltrim($assetsDir, '/'));
        /** @var non-falsy-string $baseDir */
        $baseDir = trailingslashit((string) get_template_directory()) . $dir;
        /** @var non-falsy-string $baseUrl */
        $baseUrl = trailingslashit((string) get_template_directory_uri()) . $dir;

        return Context\WpContext::new($name, $baseDir, $baseUrl, null, null);
    }

    /**
     * @param string $assetsDir
     * @param string|null $parentAssetsDir
     * @return Context\Context
     */
    public static function factoryContextForChildTheme(
        string $assetsDir = '/',
        ?string $parentAssetsDir = null
    ): Context\Context {

        $name = (string) get_stylesheet();
        $dir = ltrim($assetsDir, '/');
        /** @var non-falsy-string $baseDir */
        $baseDir = trailingslashit((string) get_stylesheet_directory()) . $dir;
        /** @var non-falsy-string $baseUrl */
        $baseUrl = trailingslashit((string) get_stylesheet_directory_uri()) . $dir;

        $altBaseDir = null;
        $altBaseUrl = null;
        if ($name !== (string) get_template()) {
            $parentDir = ltrim($parentAssetsDir ?? $assetsDir, '/');
            /** @var non-falsy-string $altBaseDir */
            $altBaseDir = trailingslashit((string) get_template_directory()) . $parentDir;
            /** @var non-falsy-string $altBaseUrl */
            $altBaseUrl = trailingslashit((string) get_template_directory_uri()) . $parentDir;
        }

        return Context\WpContext::new($name, $baseDir, $baseUrl, $altBaseDir, $altBaseUrl);
    }

    /**
     * @param non-empty-string $name
     * @param non-falsy-string $baseDir
     * @param non-falsy-string $baseUrl
     * @return Context\Context
     */
    public static function factoryContextForLibrary(
        string $name,
        string $baseDir,
        string $baseUrl
    ): Context\Context {

        return Context\WpContext::new($name, $baseDir, $baseUrl, null, null);
    }

    /**
     * @param non-empty-string $name
     * @param non-falsy-string $manifestJsonPath
     * @param non-falsy-string $baseUrl
     * @param non-falsy-string|null $basePath
     * @param non-falsy-string|null $altBasePath
     * @param non-falsy-string|null $altBaseUrl
     * @return Context\Context
     */
    public static function factoryContextForManifest(
        string $name,
        string $manifestJsonPath,
        string $baseUrl,
        ?string $basePath = null,
        ?string $altBasePath = null,
        ?string $altBaseUrl = null
    ): Context\Context {

        $isDir = is_dir($manifestJsonPath);
        /** @var non-falsy-string $manifestPath */
        $manifestPath = $isDir ? untrailingslashit($manifestJsonPath) : dirname($manifestJsonPath);
        $basePath ??= $manifestPath;

        return Context\WpContext::new(
            $name,
            $basePath,
            $baseUrl,
            $altBasePath,
            $altBaseUrl,
            $manifestPath
        );
    }

    /**
     * @param Context\Context $context
     * @return static
     */
    public static function new(Context\Context $context): static
    {
        return new static($context);
    }

    /**
     * @param Context\Context $context
     */
    final protected function __construct(private Context\Context $context)
    {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     * @param callable(Factory):T $callback
     * @return static
     */
    public function registerFactory(string $class, callable $callback): static
    {
        $this->factories[$class] = $callback;

        return $this;
    }

    /**
     * @return Context\Context
     */
    public function context(): Context\Context
    {
        return $this->context;
    }

    /**
     * @return Utils\PathFinder
     */
    public function pathFinder(): Utils\PathFinder
    {
        return $this->factoryObject(
            Utils\PathFinder::class,
            static function (Factory $factory): Utils\PathFinder {
                return Utils\PathFinder::new($factory->context());
            }
        );
    }

    /**
     * @return Version\Version
     */
    public function version(): Version\Version
    {
        return $this->factoryObject(
            Version\Version::class,
            static function (Factory $factory): Version\Version {
                return Version\LastModifiedVersion::new($factory->pathFinder());
            }
        );
    }

    /**
     * @return Version\LastModifiedVersion
     */
    public function lastModifiedVersion(): Version\LastModifiedVersion
    {
        return $this->factoryObject(
            Version\LastModifiedVersion::class,
            static function (Factory $factory): Version\LastModifiedVersion {
                return Version\LastModifiedVersion::new($factory->pathFinder());
            }
        );
    }

    /**
     * @return Utils\DependencyInfoExtractor
     */
    public function dependencyInfoExtractor(): Utils\DependencyInfoExtractor
    {
        return $this->factoryObject(
            Utils\DependencyInfoExtractor::class,
            static function (Factory $factory): Utils\DependencyInfoExtractor {
                return Utils\DependencyInfoExtractor::new($factory->pathFinder());
            }
        );
    }

    /**
     * @return UrlResolver\UrlResolver
     */
    public function urlResolver(): UrlResolver\UrlResolver
    {
        return $this->factoryObject(
            UrlResolver\UrlResolver::class,
            static function (Factory $factory): UrlResolver\UrlResolver {
                return $factory->hasManifest()
                    ? $factory->manifestUrlResolver()
                    : $factory->directUrlResolver();
            }
        );
    }

    /**
     * @return UrlResolver\DirectUrlResolver
     */
    public function directUrlResolver(): UrlResolver\DirectUrlResolver
    {
        return $this->factoryObject(
            UrlResolver\DirectUrlResolver::class,
            static function (Factory $factory): UrlResolver\DirectUrlResolver {
                return UrlResolver\DirectUrlResolver::new($factory->context());
            }
        );
    }

    /**
     * @return UrlResolver\ManifestUrlResolver
     */
    public function manifestUrlResolver(): UrlResolver\ManifestUrlResolver
    {
        return $this->factoryObject(
            UrlResolver\ManifestUrlResolver::class,
            static function (Factory $factory): UrlResolver\ManifestUrlResolver {
                return UrlResolver\ManifestUrlResolver::new(
                    $factory->directUrlResolver(),
                    $factory->context()->manifestJsonPath()
                );
            }
        );
    }

    /**
     * @return UrlResolver\MinifyResolver
     */
    public function minifyResolver(): UrlResolver\MinifyResolver
    {
        return $this->factoryObject(
            UrlResolver\MinifyResolver::class,
            static function (): UrlResolver\MinifyResolver {
                return UrlResolver\MinifyResolver::new();
            }
        );
    }

    /**
     * @return bool
     */
    public function hasManifest(): bool
    {
        $this->hasManifest ??= file_exists($this->context()->manifestJsonPath());

        return $this->hasManifest;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     * @param callable(Factory):T $defaultFactory
     * @return T
     */
    private function factoryObject(string $class, callable $defaultFactory): object
    {
        if (!isset($this->objects[$class])) {
            /** @var callable(Factory):T $factory */
            $factory = $this->factories[$class] ?? $defaultFactory;
            $object = $factory($this);
            $filtered = apply_filters("brain.assets.factory.{$class}", $object, $this);
            if (is_a($filtered, $class)) {
                $object = $filtered;
            }
            /** @var T $object */
            $this->objects[$class] = $object;
        }
        /** @var T */
        return $this->objects[$class];
    }
}
