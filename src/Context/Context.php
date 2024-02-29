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

namespace Brain\Assets\Context;

interface Context
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return bool
     */
    public function isDebug(): bool;

    /**
     * @return static
     */
    public function enableDebug(): static;

    /**
     * @return static
     */
    public function disableDebug(): static;

    /**
     * @return bool
     */
    public function isSecure(): bool;

    /**
     * @return non-falsy-string
     */
    public function basePath(): string;

    /**
     * @return non-falsy-string
     */
    public function manifestJsonPath(): string;

    /**
     * @return non-falsy-string
     */
    public function baseUrl(): string;

    /**
     * @return non-falsy-string|null
     */
    public function altBasePath(): ?string;

    /**
     * @return non-falsy-string|null
     */
    public function altBaseUrl(): ?string;

    /**
     * @return bool
     */
    public function hasAlternative(): bool;
}
