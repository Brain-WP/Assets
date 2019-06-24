<?php
if (defined('ABSPATH')) {
    return;
}

class WP_Styles
{
    /**
     * @var string
     */
    public $text_direction = '';

    public function add_data(string $handle, string $key, $what)
    {
    }
}

class WP_Scripts
{

    public function add_data(string $handle, string $key, $what)
    {
    }
}

function trailingslashit(string $path)
{
}

function untrailingslashit(string $path)
{
}

function esc_attr(string $attr)
{
}

function plugin_basename(string $plugin)
{
}

function get_template()
{
}

function get_stylesheet()
{
}

function plugins_url(string $path, string $plugin)
{
}

function get_template_directory()
{
}

function get_template_directory_uri()
{
}

function get_stylesheet_directory()
{
}

function get_stylesheet_directory_uri()
{
}

function set_url_scheme(string $filename, ?string $scheme = null)
{
}

function wp_enqueue_style(
    string $handle,
    string $url,
    array $deps = [],
    ?string $ver = null,
    string $media = 'all'
) {
}

function wp_enqueue_script(
    string $handle,
    string $url,
    array $deps = [],
    ?string $ver = null,
    bool $footer = true
) {
}

function add_query_arg($var, $value, ?string $url = null)
{
}

function is_ssl()
{
}

function add_filter(string $hook, callable $function, int $priority = 10, int $argsNum = 1)
{
}

function wp_styles()
{
    return new WP_Styles();
}

function wp_scripts()
{
    return new WP_Scripts();
}

function wp_localize_script(string $handle, string $objectName, array $data)
{
}

function wp_add_inline_style(string $handle, string $code)
{
}
