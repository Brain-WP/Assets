<?php
declare(strict_types=1);

namespace Brain\Assets\Tests;

use Brain\Assets\Version\Version;
use Brain\Monkey\Functions;

abstract class AssetsTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Functions\stubs(
            [
                'is_ssl' => true,
                'esc_attr' => null,
            ]
        );

        Functions\when('add_query_arg')->alias(
            function (string $key, string $value, string $url): string {
                return "{$url}?{$key}={$value}";
            }
        );

        Functions\when('set_url_scheme')->alias(
            function (string $url, ?string $scheme = null): string {
                $scheme === null and $scheme = is_ssl() ? 'https' : 'http';
                if (substr($url, 0, 2) === '//') {
                    return "{$scheme}:{$url}";
                }

                return preg_replace('~^\w+://~', "{$scheme}://", $url);
            }
        );
    }

    /**
     * @param string $url
     * @return \stdClass
     */
    protected function urlParts(string $url): \stdClass
    {
        $path = parse_url($url, PHP_URL_PATH);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        parse_str((string)parse_url($url, PHP_URL_QUERY), $vars);
        $ver = $vars[Version::QUERY_VAR] ?? '';

        return (object)compact('path', 'scheme', 'ver');
    }
}
