# Brain Assets

[![license](https://img.shields.io/packagist/l/brain/assets.svg)](http://opensource.org/licenses/MIT)
[![packagist](https://img.shields.io/packagist/v/brain/assets.svg)](https://packagist.org/packages/brain/assets)
[![PHP version requirement](https://img.shields.io/packagist/php-v/brain/assets.svg)](https://packagist.org/packages/brain/assets)
[![Quality Assurance](https://github.com/Brain-WP/Assets/actions/workflows/qa.yml/badge.svg)](https://github.com/Brain-WP/Assets/actions/workflows/qa.yml)



## The `Assets` class

This is a Composer package that can be used in WordPress plugins, themes and libraries to deal with assets URLs, and style / scripts enqueueing.

The package API entry point is the class **`Brain\Assets\Assets`** so the first thing to do is to obtain an instance of it.

That can be done via one static constructor to choose, among a few available, depending on where the library is used: in a plugin, in a theme, or in a library.

```php
use Brain\Assets\Assets;

// For plugins.
// First param is main plugin file,second optional param
// is the subfolder in which assets files are saved.
Assets::forPlugin(__FILE__, '/dist');

// For themes.
// First param is the subfolder in which assets files are saved.
Assets::forTheme('/dist');

// For child themes.
// First param is the subfolder in which assets files are saved.
Assets::forChildTheme('/dist');

// For libraries.
// First param is the name of the library.
// Second param is absolute path in which assets files are saved.
// Third param is the absolute URL that points to the base path.
Assets::forLibrary('my-lib', __DIR__ . '/dist', content_url('/vendor/acme/my-lib/dist'));
```

After an instance of `Assets` has been obtained, there are two groups of methods that is possible to call on it:

- methods to ***enqueue scripts and styles***
- methods to ***obtain assets URLs***



## Enqueuing assets

The first set of methods of the `Assets` class can be used to enqueue styles and script and also act on the enqueue output. For example:

```php
$assets = Assets::forPlugin(__FILE__, '/dist');

$assets->enqueueScript('my-script', strategy: 'async')
  ->useAsync()
  ->useAttribute('crossorigin', 'anonymous')
  ->localize('MyScriptData', ['foo' => 'bar'])
  ->prependInline("window.foo = 'Foo';");

$assets->enqueueStyle('my-alt-style')
  ->asAlternate()
  ->useAttribute("disabled", null)
  ->useAttribute("data-something", "Something")
  ->withTitle('my style')
  ->withCondition('lte IE 10')
  ->appendInline(".custom-color: #{$customColor}");
```



### Proper Hook

WordPress requires that assets are enqueued using a proper hook (e.g. `wp_enqueue_scripts` or `admin_enqueue_scrips`) and **that is also true** when calling `Assets::enqueueStyle()` and `Assets::enqueueScript()` methods.

An instance of `Assets` can be created early with no issues, but the actual enqueueing must be done using proper hook.



### Support for WordPress' Dependency-Extraction Webpack plugin

The [WordPress dependency-extraction Webpack plugin](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dependency-extraction-webpack-plugin/) (included when using [`wp-scripts`](https://developer.wordpress.org/block-editor/getting-started/devenv/get-started-with-wp-scripts/)) produces a PHP file names `<asset name>.asset.php` file that contains information about scripts dependencies and version. To make use of this file, use the `Assets::useDependencyExtractionData()` method.

For example, using a code like the following:

```php
$assets = Assets::forTheme('/dist')->useDependencyExtractionData();
$assets->enqueueScript('main', strategy: 'async');
$assets->enqueueScript('secondary', strategy: Strategy::newDeferInFooter());
$assets->enqueueScript('head', strategy: Strategy::newInHead());
```

And the library will end up calling:

```php
wp_enqueue_script(
    'my-theme-main',
    'https://example.com/wp-content/themes/my-theme/dist/main.js?v=a29c9d677e174811e603',
    ['react', 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-data'],
    null,
    ['in_footer' => false, 'strategy' => 'async']
);

wp_enqueue_script(
    'my-theme-secondary',
    'https://example.com/wp-content/themes/my-theme/dist/secondary.js?v=d1e2af1f57008411b820',
    ['wp-api-fetch', 'lodash'],
    null,
    ['in_footer' => true, 'strategy' => 'defer']
);

wp_enqueue_script(
    'my-theme-head',
    'https://example.com/wp-content/themes/my-theme/dist/head.js?v=2933858c47af52e33b8a',
    [],
    null,
    ['in_footer' => false]
);
```



### Registering

Besides `Assets::enqueueScript()` and `Assets::enqueueStyle()` the `Assets` class has also  `Assets::registerScript()` and `Assets::registerStyle()` which return the same `Enqueue` instance returned by the "enqueue" methods. The object has a `Enqueue::enqueue()` method that can be used to call `wp_enqueue_script()`/`wp_enqueue_style()` on the registered asset.

For the record, the `Enqueue` instance has also a `Enqueue::dequeue()` method, which has effect only if the assets is enqueued (either via `Assets::enqueueStyle()` /`Assets::enqueueSscript()` or via register methods + `Enqueue::enqueue()` ), just like `Enqueue::enqueue()` has only effect if the asset is not enqueued yet.



### Advantages

The advantages of the library are evident. By just passing a script file name we get from the library:

- a unique script "handle", obtained prefixing the theme name (would be the same for plugin)
- a full URL, including cache-busting query variable, that uses the version determined by the dependency-extraction Webpack plugin
- dependencies, determined by the dependency-extraction Webpack plugin
- enqueue "strategy"

This already greatly improves the development experience, and reduce the amount of code to be written.

Moreover, it allows us to:

- change HTML attributes of the printed `<link>` or `<script>` tags (including custom attributes);
- add conditional enqueue to both scripts and styles;
- pass localization data to the scripts;
- add inline code pre-pending or appending it to the tag (for CSS only "append" is supported).



### Manifest file

If we are using Webpack, and we are using the [Webpack Manifest Plugin](https://github.com/shellscape/webpack-manifest-plugin) we will have a `manifest.json` file containing a map of names of not processed assets file to the processed ones.

When instantiating the `Assets` class with any of `forPlugin`, `forTheme`, `forChildTheme`, `forLibrary` methods, the library automatically recognizes a `manifest.json` in the “base” folder,  and if found, it is used to resolve full URLs.



### Manifest + Dependency-Extraction Webpack plugins

Using both `wp-scripts` (or just the "Dependency Extraction" plugin) and the Webpack Manifest, enables a very powerful and concise workflow.

The `Assets` class provides a `Assets::registerAllFromManifest()` method that, as the names suggests, register all the CSS/JS assets present in the `manifest.json`. Combined with the "Dependency Extraction" plugin, which can automatically provide dependencies and version for each of the assets, we can automate the registering for all the assets in one line of code.

The `Assets::registerAllFromManifest()` returns a `Collection` class containing all the `Enqueue` instances for the registered assets, with methods to filter them and obtaining specific instances by name or handle.

Having some convention on the names used for the files (e..g using "*-admin*" suffix for backed assets, "*-view*" suffix for frontend assets, and "*-block*" suffix for block-related assets) it is possible to have a code like the following:

```php
$myAssets = Assets::forTheme('/dist')->useDependencyExtractionData()->registerAllFromManifest();

add_action('admin_enqueue_scripts'), fn () => $myAssets->keep('*-admin')->enqueue());
add_action('wp_enqueue_scripts'), fn () => $myAssets->keep('*-view')->enqueue());
```

**These three lines of code are all we need to register potentially many assets, and to enqueue them properly, with the right URL, the right dependencies, and the right version**.

The assets with "*-block*" suffix are not enqueued in the snippet because we likely want to use their handle in the `block.json`, letting WordPress enqueue them when necessary.

#### More about the `Collection` object

The `Collection` class provides many different ways of filtering assets. The `Collection::keep()` method shown above not only works with glob patterns, it also accepts regular expressions, like `$myAssets->keep('#foo-[a-z]+bar#')` or simple strings, like `$myAssets->keep('admin')`, in which case it uses `str_contains()` for filtering.

Besides `Collection::keep()` , the collection also provides a `Collection::discard()` (with similar characteristics but opposite scope), `Collection::filter()` (to filter using custom callbacks), as well as methods to filter based on type like `Collection::cssOnly()` and `Collection::jsOnly()` .

Moreover, the class provides methods to retrieve single instances by handle or by file name, useful to fine-tune the registration of specific assets. For example:

```php
$myAssets = Assets::forTheme('/dist')->useDependencyExtractionData()->registerAllFromManifest();
$myAssets->byName('main', 'js')->localize('MyData', $mainScriptData);

add_action('wp_enqueue_scripts'), fn () => $myAssets->discard('*-admin')->enqueue());
```

Please review [`Collection` source code](./src/Enqueue/Collection.php) to find out all the useful API the class provides.



### Retrieving registered/enqueued assets' collection

The `Assets` class provides a `Assets::collection()` method that returns a `Collection` instance with all the assets that have been enqueued/registered.  The collection allows us to act on individual assets (retrieved via `Collection::oneByName()` or `Collection::oneByHandle()`) or collectively on all or some assets (filtered using one of the many methods `Collection` provides, see above). 

Here's an example on how this could be leveraged for obtaining "automatic bulk registration" for all our assets in few lines of code that loop files in the assets directory:

```php
$assets = Assets::forTheme('/dist')->useDependencyExtractionData();

foreach (glob($assets->context()->basePath() . '*.{css,js}', GLOB_BRACE) as $file) {
    str_ends_with($file, '.css')
        ? $assets->registerStyle(basename($file, '.css'))
        : $assets->registerScript(basename($file, '.js'));  
}

add_action('admin_enqueue_scripts'), fn () => $assets->collection()->keep('*-admin')->enqueue());
add_action('wp_enqueue_scripts'), fn () => $assets->collection()->keep('*-view')->enqueue());
```

Please note: because `Collection` has an immutable design, do not store the result of `Assets::collection()` but always call that method to retrieve an up-to-date collection.




### Debug

The library has a "debug" status flag used in two occasions:

- When true, ensures a new "version" parameter for the asset URLs is used on every request.
- Decide whether to look for a minified version of the asset (one with `.min` before the extension) in the case minified lookup is enabled (see below).

The "debug" status depends on the value of the `SCRIPT_DEBUG` constant, but can be set via the methods: **`Assets::forceDebug()`** and **`Assets::forceNoDebug()`**.



### Minified file resolution

Some methods used to compile assets build two version of files, one for debug purposes, and another "minified" having a `.min` suffix. For example, we might have both a `my-style.css` and a `my-style.min.css`.

In such cases, it is possible to instructs the library to look for minified files when "debug" is false. That is done via `Assets::tryMinUrls()` method.

For example:

```php
Assets::forPlugin(__FILE__, '/dist')
    ->tryMinUrls()
    ->enqueueStyle('my-style.css');
```

The snippet above, when "debug" is false, will search for a `my-style.min.css` and will enqueue it if found, falling back to `my-style.css` if the minified file is not found.

If the minified file is the _only_ file created by the assets building pipeline, then we can enqueue is as usual, providing the `.min` part as part of the file name:

```php
Assets::forPlugin(__FILE__, '/dist')->enqueueStyle('my-style.min');
```




### HTTP scheme resolution

This library forces the usage of `https` scheme when `Context` "secure" status is enabled. This is, by default, based on the result of `is_ssl()` WordPress function.

It is possible to use **`Assets::forceSecureUrls()`** and **`Assets::dontForceSecureUrls()`** to disable this feature.

When disabled the base URL will be used as-is, which will likely contain `https` anyway when in HTTPs context and `Assets` instance has been created via `forPlugin()` or `forTheme()` constructors; however, when using `Assets::forLibrary()` and then `dontForceSecureUrls()` on the obtained instance, the HTTP scheme that will be used for all assets is up to the developer and will only depend on the `$baseUrl` parameter.



### Enqueue external assets

When using `Assets::enqueueStyle()` and `Assets::enqueueScript()` it is necessary to pass the file name of the asset to be enqueued and the library resolves the full URL.

Sometimes it is desired to just enqueue a given full URL, e.g. a file that resides in a CDN (or anywhere not locally), and it can be easily done by just calling `wp_enqueue_script` or `wp_enqueue_style`.

The `Assets` class provides `enqueueExternalStyle()` and `enqueueExternalScript()` that can be used to enqueue assets with (almost) no processing, and then allow us to use "advanced" methods provided by the library.

For example:

```php
$assets->enqueueExternalScript('foo-js', 'https://cdn.example.com/foo.js?v=1.0')
  ->useDefer()
  ->useAttribute('data-id', 'foo-script')
  ->withCondition('lte IE 10')
  ->localize('MyScriptData', ['foo' => 'bar'])
  ->prependInline("window.foo = 'Foo';");
  ->appendInline("delete window.foo;");
```

Please note that when using these methods the library will enqueue assets without trying to append any cache busting query variable (and also preventing WordPress to add its version) because non-local assets URLs usually comes with cache variable as part of the URL.

The only processing that the library attempts on the given URL is to **adjust the scheme**: URLs starting with `http://` , by default, will be converted to use `https://`  if `is_ssl()` is true. This processing can also be disabled via **`Assets::dontForceSecureUrls()`**.

It is worth noting that using external URLs starting with `//` (relative scheme) will skip any scheme processing as well.



## Obtaining URLs

Besides enqueue assets, the library provides method to obtain assets URLs, which besides for scripts and styles, might be useful for any kind of asset, like images, video, fonts, etc.

Here's a quick example:

```php
$assets = Assets::forPlugin(__FILE__, '/dist');

$styleUrl  = $assets->assetUrl('css/my-style.css');
$scriptUrl = $assets->assetUrl('js/my-script.js');
$imageUrl = $assets->assetUrl('images/foo.jpg');
$fontUrl = $assets->assetUrl('fonts/bar.eot');
$videoUrl = $assets->assetUrl('videos/baz.mp4');
```

In the snippet above it is assumed that different "types" of assets (images, videos, fonts, CSS, JS) are saved in sub-folders of the main assets folder, in this case `/dist` inside plugin folder.

When that is the case, it is possible to instruct the `Assets` object about the existence of these sub-folders and then use type-specific methods to obtain the URLs:

```php
$assets = Assets::forPlugin(__FILE__, '/dist')
  ->withCssFolder('/css')
  ->withJsFolder('/js')
  ->withImagesFolder('/images')
  ->withVideosFolder('/videos')
  ->withFontsFolder('fonts');

$styleUrl  = $assets->cssUrl('my-style');
$scriptUrl = $assets->jsUrl('my-script');
$imageUrl = $assets->imgUrl('foo.jpg');
$fontUrl = $assets->fontUrl('bar.eot');
$videoUrl = $assets->videoUrl('baz.mp4');
```

This latest snippet is equivalent to the previous. But sub-folders are set up once, when creating the instance, and then it is possible to use less verbose and more explicit methods to obtain URLs.

Note how for JS and CSS files it is not required to pass the file extension.



### URLs include version

All the URLs obtained with the methods described above contain a query variable for cache busting. For example:

```php
print $assets->cssUrl('my-style');
// https://www.example.com/wp-content/themes/my-theme/dist/my-style.css?v=1708973312
```

To obtain URLs without any version query variable it is possible to use the "raw" version of methods:

```php
$assets->rawAssetUrl('my-style.css');
$assets->rawCssUrl('my-style');
$assets->rawJsUrl('my-script');
$assets->rawImgUrl('foo.jpg');
$assets->rawFontUrl('bar.eot');
$assets->rawVideoUrl('baz.mp4');
```

Alternatively, it is possible to configure the whole `Asset` instance to not ever add version query variables via `Assets::dontAddVersion()`:

```php
print $assets->dontAddVersion()->cssUrl('my-style');
// https://www.example.com/wp-content/themes/my-theme/dist/my-style.css
```



## Requisites

The library requires:

- **PHP 8.0+**
- **WordPress 6.3+**.

When installed for development, following packages are required:

* [phpunit/phpunit](https://packagist.org/packages/phpunit/phpunit) (BSD-3-Clause)
* [brain/monkey](https://packagist.org/packages/brain/monkey) (MIT)
* [inpsyde/php-coding-standards](https://packagist.org/packages/inpsyde/php-coding-standards) (MIT)
* [phpcompatibility/php-compatibility](https://packagist.org/packages/phpcompatibility/php-compatibility) (LGPL 3)
* [vimeo/psalm](https://packagist.org/packages/vimeo/psalm) (MIT)
* [inpsyde/wp-stubs](https://packagist.org/packages/inpsyde/wp-stubs) (MIT)



## License

Brain Monkey is open source and released under MIT license. See [LICENSE](./LICENSE) file for more info.
