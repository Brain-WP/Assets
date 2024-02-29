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

interface UrlResolver
{
    /**
     * @param string $relative
     * @param MinifyResolver|null $minifyResolver
     * @return string
     */
    public function resolve(string $relative, ?MinifyResolver $minifyResolver): string;
}
