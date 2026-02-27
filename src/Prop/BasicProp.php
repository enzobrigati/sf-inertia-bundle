<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Prop;

use EnzoBrigati\InertiaBundle\InertiaPage;

/**
 * The most basic prop with no special functionality.
 */
class BasicProp implements PropInterface
{
    public function __construct(public readonly mixed $value)
    {
    }

    public function attachToPage(InertiaPage $page): void
    {
    }

    public function resolveValue(InertiaPage $page): mixed
    {
        if (is_callable($this->value)) {
            return call_user_func($this->value);
        } else {
            return $this->value;
        }
    }
}
