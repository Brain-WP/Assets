<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Brain\Assets\Version;

use Brain\Assets\Context\Context;

final class NoVersion implements Version
{
    /**
     * @param string $url
     * @return string|null
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public function calculate(string $url): ?string
    {
        //phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration

        return null;
    }

    /**
     * @param Context $context
     * @return Version
     */
    public function withContext(Context $context): Version
    {
        return $this;
    }
}
