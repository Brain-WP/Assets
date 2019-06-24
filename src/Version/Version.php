<?php

namespace Brain\Assets\Version;

use Brain\Assets\Context\Context;

interface Version
{
    public const QUERY_VAR = 'v';

    /**
     * @param string $url
     * @return string
     */
    public function calculate(string $url): ?string;

    /**
     * @param Context $context
     * @return Version
     */
    public function withContext(Context $context): Version;
}
