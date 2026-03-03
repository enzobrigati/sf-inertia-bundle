<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Modal;

enum ModalPosition: string
{
    case Bottom = 'bottom';
    case Center = 'center';
    case Left = 'left';
    case Right = 'right';
    case Top = 'top';
}
