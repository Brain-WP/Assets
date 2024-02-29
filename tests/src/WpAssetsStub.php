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

namespace Brain\Assets\Tests;

class WpAssetsStub
{
    public array $data = [];

    /**
     * @param string $handle
     * @param mixed ...$args
     *
     * phpcs:disable PSR1.Methods.CamelCapsMethodName
     */
    public function add_data(string $handle, mixed ...$args): void
    {
        // phpcs:enable PSR1.Methods.CamelCapsMethodName
        $this->data[$handle] = $args;
    }
}
