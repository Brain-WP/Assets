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
    /** @var array<string, array<string, mixed>> */
    public array $data = [];
    /** @var array<string, list{\_WP_Dependency, list<non-empty-string>}>  */
    public array $dependencies = [];

    /**
     * @param string $handle
     * @param string $key
     * @param mixed $value
     *
     * phpcs:disable PSR1.Methods.CamelCapsMethodName
     */
    public function add_data(string $handle, string $key, mixed $value): void
    {
        // phpcs:enable PSR1.Methods.CamelCapsMethodName
        isset($this->data[$handle]) or $this->data[$handle] = [];
        $this->data[$handle][$key] = $value;

        [$dep] = $this->dependencies[$handle] ?? [null];
        /** @psalm-suppress MixedArgument */
        $dep?->add_data($key, $value);
    }

    /**
     * @param string $handle
     * @param string $type
     * @return \_WP_Dependency|bool
     */
    public function query(string $handle, string $type = 'registered'): \_WP_Dependency|bool
    {
        [$dep, $types] = $this->dependencies[$handle] ?? [null, []];
        if (($dep !== null) && ($type === 'registered')) {
            return $dep;
        }

        return ($dep !== null) && in_array($type, $types, true);
    }

    /**
     * @param \_WP_Dependency $dependency
     * @param 'enqueued'|'registered'|'to_do'|'done' $status
     * @return static
     */
    public function addWpDependencyStub(\_WP_Dependency $dependency, string $status): static
    {
        $types = match ($status) {
            'registered' => ['registered'],
            'enqueued' => ['registered', 'enqueued'],
            'to_do' => ['registered', 'enqueued', 'to_do'],
            'done' => ['registered', 'enqueued', 'done'],
        };
        $this->dependencies[$dependency->handle] = [$dependency, $types];

        return $this;
    }
}
