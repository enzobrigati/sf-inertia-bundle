<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Prop;

use EnzoBrigati\InertiaBundle\InertiaPage;

/**
 * A basic prop the value of which is provided by a callback.
 */
class CallbackProp extends BasicProp
{
    /**
     * @param callable(): mixed $callback
     */
    public function __construct(callable $callback)
    {
        parent::__construct($callback(...));
    }

    public function resolveValue(InertiaPage $page): mixed
    {
        return call_user_func($this->value);
    }
}
