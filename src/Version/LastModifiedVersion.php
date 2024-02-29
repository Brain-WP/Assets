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

namespace Brain\Assets\Version;

use Brain\Assets\Context\Context;
use Brain\Assets\MatchSchemeTrait;
use Brain\Assets\Utils\PathFinder;

class LastModifiedVersion implements Version
{
    /**
     * @param PathFinder $pathFinder
     * @param Context $context
     * @return static
     */
    public static function new(PathFinder $pathFinder): static
    {
        return new static($pathFinder);
    }

    /**
     * @param PathFinder $pathFinder
     * @param Context $context
     */
    final protected function __construct(
        private PathFinder $pathFinder
    ) {
    }

    /**
     * @param string $url
     * @return string|null
     */
    public function calculate(string $url): ?string
    {
        $fullPath = $this->pathFinder->findPath($url);
        if ($fullPath === null) {
            return null;
        }

        $lastModified = @filemtime($fullPath);

        return ($lastModified !== false) ? (string) $lastModified : null;
    }
}
