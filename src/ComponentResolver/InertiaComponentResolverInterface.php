<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\ComponentResolver;

use EnzoBrigati\InertiaBundle\Exception\ComponentResolverException;

interface InertiaComponentResolverInterface
{
    /**
     * Resolve component name to a final path to the component.
     *
     * @throws ComponentResolverException
     */
    public function resolve(string $component): string;
}
