<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Modal;

enum QueryStringArrayFormat: string
{
    case Brackets = 'brackets';
    case Indices = 'indices';
}
