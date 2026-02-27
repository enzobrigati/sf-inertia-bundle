<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Prop;

use EnzoBrigati\InertiaBundle\InertiaPage;

interface PropInterface
{
    /**
     * Called when the prop becomes part of an InertiaPage.
     */
    public function attachToPage(InertiaPage $page): void;

    /**
     * Called when the final prop value needs to be resolved.
     */
    public function resolveValue(InertiaPage $page): mixed;
}
