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

namespace Brain\Assets\Enqueue;

interface Enqueue
{
    /**
     * @return string
     */
    public function handle(): string;

    /**
     * @return bool
     */
    public function isJs(): bool;

    /**
     * @return bool
     */
    public function isCss(): bool;

    /**
     * @return bool
     */
    public function isEnqueued(): bool;

    /**
     * @return static
     */
    public function dequeue(): static;

    /**
     * @return static
     */
    public function enqueue(): static;
}
