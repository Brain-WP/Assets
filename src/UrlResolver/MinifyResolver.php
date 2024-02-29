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

namespace Brain\Assets\UrlResolver;

class MinifyResolver
{
    /**
     * @return MinifyResolver
     */
    final public static function new(): MinifyResolver
    {
        return new static();
    }

    /**
     */
    final protected function __construct()
    {
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function resolve(string $path): ?string
    {
        if (!preg_match("~(?P<file>.+?)(?P<min>\.min)?\.(?P<ext>js|css)\$~i", $path, $matches)) {
            return null;
        }

        if (($matches['min'] ?? '') !== '') {
            return null;
        }

        return "{$matches['file']}.min.{$matches['ext']}";
    }
}
