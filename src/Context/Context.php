<?php

namespace Brain\Assets\Context;

interface Context
{
    /**
     * @return bool
     */
    public function isDebug(): bool;

    /**
     * @return Context
     */
    public function enableDebug(): Context;

    /**
     * @return Context
     */
    public function disableDebug(): Context;

    /**
     * @return bool
     */
    public function isSecure(): bool;

    /**
     * @return string
     */
    public function basePath(): string;

    /**
     * @return string
     */
    public function baseUrl(): string;

    /**
     * @return string|null
     */
    public function altBasePath(): ?string;

    /**
     * @return string|null
     */
    public function altBaseUrl(): ?string;

    /**
     * @return bool
     */
    public function hasAlternative(): bool;
}
