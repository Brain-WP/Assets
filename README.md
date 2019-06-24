# Brain Assets

> WordPress assets helpers

---
[![license](https://img.shields.io/packagist/l/brain/assets.svg?style=flat-square)](http://opensource.org/licenses/MIT)
[![travis-ci status](https://img.shields.io/travis/Brain-WP/Assets.svg?style=flat-square)](https://travis-ci.org/Brain-WP/Assets)
[![codecov.io](https://img.shields.io/codecov/c/github/Brain-WP/Assets.svg?style=flat-square)](http://codecov.io/github/Brain-WP/Assets?branch=master)
[![packagist](https://img.shields.io/packagist/v/brain/assets.svg?style=flat-square)](https://packagist.org/packages/brain/assets)
[![PHP version requirement](https://img.shields.io/packagist/php-v/brain/assets.svg?style=flat-square)](https://packagist.org/packages/brain/assets)
---

## Quick start



This is a Composer package that can be used in WordPress plugins, themes and libraries to deal with assets URLs, and style / scripts enqueueing.

The package API entry point is the class **`Brain\Assets\Assets`** so first thing to do is to obtain an instance of it.

That can be done via one static constructor to choose, among a few available, depending where the library is used: in a plugin, in a theme, or in a library.

```php
use Brain\Assets\Assets;

// For plugins.
// First param is main plugin file,second optional param
// is the subfolder in which assets files are saved.
Assets::forPlugin(__FILE__, '/assets');

// For themes.
// First param is the subfolder in which assets files are saved.
Assets::forTheme('/assets');

// For child themes.
// First param is the subfolder in which assets files are saved.
Assets::forChildTheme('/assets');

// For libraries.
// First param is the name of the library.
// Second param is absolute path in which assets files are saved.
// Third param is the absolute URL that points to the base path.
Assets::forLibrary('my-lib', __DIR_ . '/files', 'https://cdn.example.com/files');
```

There is a separate constructor for child themes, because when using that, the library will be able to automatically fallback to parent theme in case a given asset is not found in child theme folder.

After an instance of `Assets` has been obtained, there are two groups of methods that is possible to call on it:

- methods to *obtain URLs* of assets
- methods to *enqueue* scripts and styles



### Obtain URLs

```php
$assets = Assets::forPlugin(__FILE__, '/assets');

$styleUrl  = $assets->assetUrl('css/my-style.css');
$scriptUrl = $assets->assetUrl('js/my-script.js');
$imageUrl  = $assets->assetUrl('images/foo.jpg');
$fontUrl   = $assets->assetUrl('fonts/bar.eot');
$videoUrl  = $assets->assetUrl('videos/baz.mp4');
```

In the snippet above it is assumed that different "types" of assets (images, videos, fonts, CSS, JS) are saved in sub-folders of the main assets folder, in this case `/assets` inside plugin folder.

When that is the case, it is possible to instruct the `Assets` object about the existence of these sub-folders and then use type-specific methods to obtain the URLs:

```php
$assets = Assets::forPlugin(__FILE__, '/assets')
  ->withCssFolder('/css')
  ->withJsFolder('/js')
  ->withImagesFolder('/images')
  ->withVideosFolder('/videos')
  ->withFontsFolder('fonts');

$styleUrl  = $assets->cssUrl('my-style');
$scriptUrl = $assets->jsUrl('my-script');
$imageUrl  = $assets->imgUrl('foo.jpg');
$fontUrl   = $assets->fontUrl('bar.eot');
$videoUrl  = $assets->videoUrl('baz.mp4');
```

This latest snippet is equivalent to the previous. But sub-folders are setup once, when creating the instance, and then it is possible to use less verbose and more explicit methods to obtain URLs.

Note how for JS and CSS files it is not required to pass the file extension.

Also note that type-specific methods can be used even when all assets are stored in same folder, by just calling them on the instance of `Assets` obtained without any of the `with...Folder()` method.



### Enqueueing assets

The second set of methods of the `Assets` class can be used to enqueue styles and script and also act on the enqueue output. For example:

```php
$assets = Assets::forPlugin(__FILE__, '/assets')
  ->withCssFolder('/css')
  ->withJsFolder('/js');

$assets->enqueueScript('my-script', ['jquery'])
  ->useAsync()
  ->useDefer()
  ->useAttribute("crossorigin", "anonymous")
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

Here the advantages of the library are evident.

To enqueue assets, we just pass the name of the asset (and optionally its dependencies) and the library resolves the full URL, will deal with versioning, minified versions and so on.

Moreover:

- we can change attributes of the printed `<link>` or `<script>` tag (e. g. `async` and `defer` for scripts and `alternate` and `title` for styles, but also custom attributes);
- we can add conditional enqueue to both scripts and styles;
- we can pass localization data to the scripts;
- we can add inline code pre-pending or appending it to the tag (for CSS only "append" is supported).



### Proper Hook

WordPress requires that assets are enqueued using a proper hook (e. g. `wp_enqueue_scripts` or `admin_enqueue_scrips`) and **that is also true** when calling `Assets::enqueueStyle()` and `Assets::enqueueScript()` methods.

An instance of `Assets` can be created early with no issues, but the actual enqueueing must be done using proper hook.



## A deeper look



### What and why

WordPress has a series of functions that deal with "assets" URLs, the most obvious are the functions to "register" and "enqueue" scripts and styles, plus there are functions like `plugins_url`, `get_template_directory_uri`, `get_stylesheet_directory_uri`, `get_theme_file_uri`, that more often than not are used to retrieve URLs to CSS, JS, fonts or image files.

#### Issues with core functions

The issue with the "register" and "enqueue" style/scripts functions is that they require "a few" arguments that, to be used properly, needs some code to be added again and again.

One example: to add  "version" URL query string for "cache-busting", it is nice to use the file modification time, but also ensuring that during debug the file is always non cached.

Or another example: when both minified and not-minified versions of a style or a script are available, it is nice to have the minified version used on production and the non-minified otherwise.

Regarding the plugin and theme functions to obtain assets URLs, issue with minified version (when applicable) and cache-busting are there as well, and it is also kind of confusing to have multiple functions for the same purpose.

#### Manifest file

On top of that, some assets bundlers (such as Webpack) create a `manifest.json` file that is a map of names of not processed assets file to the processed ones. When this file is available, it is nice to be able to make use of it, for example that allows to use bundler "native" assets versioning instead of relying on assets file modification time. However, there is no core function that allows to deal with that.

#### Act on enqueued assets

Another issue with "register" and "enqueue" style/scripts functions is that after an asset has been enqueued the actual HTML markup that will be printed to page is hard to modify.

Some modifications can be done via filters, some others needs to be done via `add_data` method on `WP_Dependency` class.

So there is no simple, nor unified way to deal with this kind of modifications.



### Setting up `Assets ` class behavior

#### Dealing with  `manifest.json`

When instantiating the `Assets` class with any of `forPlugin`, `forTheme`, `forChildTheme`, `forLibrary` methods, the library tries to look for a `manifest.json` in the “base” folder, and if found it is automatically used to resolve full URLs.

When `manifest.json` is in custom location, it is possible to call **`Assets::useManifest()`** method on the asset instance to tell where to look for the file.

#### Context

The library has an internal class `Brain\Assets\Context\Context` that encapsulates several configuration, like the base path and base URL, plus "secure" and "debug" status of the instance.

Even if `Assets`  class provides a **`replaceContext()`** method that allows to force the `Context` instance used, that is rarely necessary, because the class is instantiated with "sensitive" defaults that should be fine most of the times. For example, "secure" status is set by calling `is_ssl()` and "debug" status is retrieved from `SCRIPT_DEBUG` constant.

#### Debug

The "debug" status from `Context` class is used for at least two reasons:

- decide whether to use cache busting URL query variable or not
- decide whether to look for a minified version of the asset (one with `.min` before the extension)

to change this behavior,  `Assets`  class provides two methods: **`forceDebug()`** and **`forceNoDebug()`**, that can be used to enforce the debug status no matter what was auto-discovered.

#### Cache busting

WordPress enqueue functions have a built-in functionality to deal with cache busting, that is adding a `"v"` URL query variable that has to be manually passed, or will fallback to WordPress version (which makes very little sense for non-core assets).

This library in this regard has a default behavior that is based on the "debug" context status.

When debug is disabled, the library retrieves the file modification time of the asset file, and use it as URL query variable.

When debug is enabled, the library uses the result of the PHP function `time()` for the URL query variable, making sure that when debugging no cache happen for assets, ever.

To disable cache busting query variable at all, something useful for example when using assets bundler and `manifest.json` to deal with versioning, it is possible to call **`Assets::dontAddVersion()`** on the used `Assets` instance.

For completeness, it is worth mentioning that version resolution in the library is handled via objects that implement the `Brain\Assets\Version\Version` interface.

Via the **`Assets::addVersionUsing()`** method it is possible to pass an implementation of the interface to deal with versioning in a very customized way.

#### Minified file resolution

All the  `Assets` class API methods, when "debug" status is disabled,  try to resolve URLs looking for a minified version of the assets.

For example, using a code like `$assets->enqueueStyle('my-style')`, assuming debug is disabled, the library will look for a file named `my-style.min.css` and only if not found will fallback to `my-style.css`.

If the minified version is the _only_ version available, than doing `$assets->enqueueStyle('my-style.min.css')` will be enough to prevent any further check.

(Note how the full file name is used (including extension), that is necessary everytime a file name contains one or more dots.)

In case the minified version is never available, for any reason, this "minified" resolution can be disabled via **`Assets::dontTryMinUrls()`**. 

There is also the  **`Assets::tryMinUrls()`** counter part, which is less useful before this feature is enabled by default, but can be  used to re-enable the feature after it has been disabled.

#### HTTP scheme resolution

This library forces the usage of `https` scheme when `Context` "secure" status is enabled. This is, by default, based on the result of `is_ssl()` WordPress function.

It is possible to use **`Assets::forceSecureUrls()`** and **`Assets::dontForceSecureUrls()`** to disable this feature.

When disabled the base URL will be used as-is, which will likely contain `https` anyway when in HTTPs context and `Assets` instance has been created via `forPlugin()` or `forTheme()` constructors; however, when using `Assets::forLibrary()` and then `dontForceSecureUrls()` on the obtained instance, the HTTP scheme that will be used for all assets is up to the developer and will only depend on the `$baseUrl` parameter. 

#### Assets "handle"

This library, when enqueueing assets, always uses under the hood the WordPress core functions `wp_enqueue_script` and `wp_enqueue_style`.

These functions first argument is the "handle", that is an *unique* identifier for assets that allows to identify assets in several functions and hooks and also prevents the same asset to be added more than once.

Because the handle has to be unique, the library uses the file name (without extension) *prepended* with a "name" that is stored in the `Assets` instance and for themes and plugin is, respectively, the name of theme or plugin, and for libraries has to be explicitly passed to `Assets::forLibrary()` named constructor.

For example:

```php
$assets = Assets::forPlugin(__FILE__, '/assets')
  ->withJsFolder('/js')
  ->enqueueScript('admin');
```

In the snippet above, assuming the plugin *basename* (see [`plugin_basename`](https://developer.wordpress.org/reference/functions/plugin_basename/)) is `"awesome-plugin/plugin.php"`, the library will enqueue the script using **`"awesome-plugin-admin"`** as handle.

Similarly, for themes the handle "prefix" will be the name of the theme folder, and for libraries it will be anything passed as first argument to `Assets::forLibrary()`.

The biggest pros of having automatic prefix for assets is the possibility to use generic and compact names for assets files and so less verbose enqueueing code.

However, it makes the actually used handle non predictable, for example, to use the enqueued assets as dependency for some other asset becomes harder.

One possible solution is the `handle()` method of the object returned by both `Assets::enqueueScript` and `Assets::enqueueStyle()` methods, that returns the exact handle that has been used.

```php
$assets = Assets::forPlugin(__FILE__, '/assets')
  ->withJsFolder('/js');

$fooHandle = $assets->enqueueScript('foo')->handle();
$barHandle = $assets->enqueueScript('bar')->handle();

$assets->enqueueScript('baz', [$scriptFoo, $scriptBar]);
```

It is also possible completely disabling auto-prefixing of assets handles by calling `Assets::disableHandlePrefix()`. 

Finally, it is possible to force the prefix to be a given string via `Assets::enableHandlePrefix()` that accepts as optional parameter the prefix to use. If used without any parameter it enables auto-prefixing and let library calculate the prefix (basically, the default behavior).

To be fair, even if `handle()` method can be useful is several situations, when using `Assets::enqueueScript()` and `Assets::enqueueStyle` it is possible to pass as dependencies an array of enqueue _objects_. 

For example, the latest snippet above could be re-written like this:

```php
$assets = Assets::forPlugin(__FILE__, '/assets')
  ->withJsFolder('/js');

$foo = $assets->enqueueScript('foo');
$bar = $assets->enqueueScript('bar');

$assets->enqueueScript('baz', [$foo, $bar]);
```

Another way to obtain an handle for a name is to use `Assets::handleForName()` method (meaning that it is needed access to the instance of `Assets` used to enqueue the asset).

Again, following snippet is equivalent to the previous:

```php
$assets = Assets::forPlugin(__FILE__, '/assets')
  ->withJsFolder('/js');

$foo = $assets->enqueueScript('foo');
$bar = $assets->enqueueScript('bar');

$fooHandle = $assets->handleForName('foo');
$barHandle = $assets->handleForName('bar');

$assets->enqueueScript('baz', [$fooHandle, $barHandle]);
```

This method is particularly useful when it is needed to access "advanced" enqueue methods like `localize ` or `appendInline()` in a different place (even from a different plugin) from where the assets itself has been enqueued.

For example, let's assume in theme `functions.php` we find:

```php
use Brain\Assets\Assets;

add_action('wp_enqueue_scripts', function () {
  Assets::forTheme()->enqueueScript('main');
});
```

And let's assume that in a plugin we want to add localization data to that theme script plus we want to use "async" attribute.

We can do:

```php
use Brain\Assets;

add_action('wp_enqueue_scripts', function () {
  $handle = Assets\Assets::forTheme()->handleForName('main');
  
  Assets\Enqueue\JsEnqueue::create($handle)
    ->useAsync()
    ->localize('ThemeData', ['foo' => 'bar'])
}, 11);
```

Because we had no access to the `Assets` instance used by theme, we re-created it, then we used it to "resolve" the theme script handle, after that we where ready to create an instance of `JsEnqueue` that gives us access to all the "advanced" enqueue methods.

`Brain\Assets\Enqueue\CssEnqueue` is the equivalent for styles.

### Enqueueing external assets

When using `Assets::enqueueStyle()` and `Assets::enqueueScript()` it is necessary to pass the file name of the asset to be enqueued and from there the library resolves the full URL taking into account versioning, possible minified file, and so on.

Sometimes it is desired to just enqueue a given full URL, e. g. a file that resides in a CDN (or anywhere not locally), and it can be easily done by just calling `wp_enqueue_script` or `wp_enqueue_style`.

However, by the "plain" WordPress functions we would loose the possibility to use "advanced" features that the library provides.

This is why `Assets` class provides `enqueueExternalStyle()` and `enqueueExternalScript()` that can be used to enqueue assets with (almost) no processing, and then allow calling advanced enqueue methods.

For example:

```php
$assets->enqueueExternalScript('foo-js', 'https://cdn.example.com/foo.js?v=1.0')
  ->useAsync()
  ->useDefer()
  ->withCondition('lte IE 10')
  ->localize('MyScriptData', ['foo' => 'bar'])
  ->prependInline("window.foo = 'Foo';");
  ->appendInline("delete window.foo;");
```

Please note that when using these methods the library will enqueue assets without trying to append any cache busting query variable (and also preventing WordPress to add its version) because non-local assets URLs usually comes with cache variable as part of the URL.

The only processing that the library attempts on the given URL is to adjust the scheme: an URL that starts with  `http://` will be, by default, converted to use `https://` instead if `Context` "secure" status is enabled (which by default depends on `is_ssl()`).

Disabling the HTTPs forcing via **`Assets::dontForceSecureUrls()`** (discussed earlier) will also affect external URLs enqueueing, meaning that external URLs will be used exactly as is.

It is worth noting that using external URLs that start with `//` (relative scheme) will skip any scheme processing as well.