<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\PropProvider;

use EnzoBrigati\InertiaBundle\InertiaFlash;
use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\InertiaProp;
use EnzoBrigati\InertiaBundle\Service\InertiaInterface;

/**
 * A prop provider that provides flashed validation errors.
 */
class ErrorsPropProvider implements InertiaPropProviderInterface
{
    public function getInertiaProps(InertiaHeaders $headers, InertiaFlash $flash): array
    {
        return [
            InertiaInterface::PROP_ERRORS => InertiaProp::always($flash->getErrors()),
        ];
    }
}
