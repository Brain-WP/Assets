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

abstract class AbstractEnqueue implements Enqueue
{
    protected bool $isEnqueue;
    protected bool $isCss;

    /**
     * @return bool
     */
    final public function isEnqueued(): bool
    {
        return $this->isEnqueue;
    }

    /**
     * @return static
     */
    final public function dequeue(): static
    {
        if ($this->isEnqueue) {
            $this->isCss()
                ? wp_dequeue_style($this->handle())
                : wp_dequeue_script($this->handle());
            $this->isEnqueue = false;
        }

        return $this;
    }

    /**
     * @return static
     */
    final public function enqueue(): static
    {
        if (!$this->isEnqueue) {
            $this->isCss()
                ? wp_enqueue_style($this->handle())
                : wp_enqueue_script($this->handle());
            $this->isEnqueue = true;
        }

        return $this;
    }

    /**
     * @return void
     */
    final public function deregister(): void
    {
        $this->dequeue();
        $this->isCss()
            ? wp_deregister_style($this->handle())
            : wp_deregister_script($this->handle());
    }

    /**
     * @return bool
     */
    final public function isJs(): bool
    {
        return !$this->isCss;
    }

    /**
     * @return bool
     */
    final public function isCss(): bool
    {
        return $this->isCss;
    }
}
