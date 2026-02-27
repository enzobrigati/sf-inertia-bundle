<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\PropProvider;

use EnzoBrigati\InertiaBundle\InertiaFlash;
use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\Prop\PropInterface;

/**
 * A class that provides global Inertia props.
 */
interface InertiaPropProviderInterface
{
    /**
     * @return array<string, PropInterface>
     */
    public function getInertiaProps(InertiaHeaders $headers, InertiaFlash $flash): array;
}
