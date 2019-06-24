<?php

namespace Brain\Assets\UrlResolver;

use Brain\Assets\Context\Context;

interface UrlResolver
{
    /**
     * @param string $relative
     * @param MinifyResolver $minifyResolver
     * @return string
     */
    public function resolve(string $relative, MinifyResolver $minifyResolver): string;

    /**
     * @param Context $context
     * @return UrlResolver
     */
    public function withContext(Context $context): UrlResolver;
}
