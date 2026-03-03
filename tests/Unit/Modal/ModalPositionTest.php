<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Modal;

use EnzoBrigati\InertiaBundle\Modal\ModalPosition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModalPositionTest extends TestCase
{
    #[Test]
    public function it_has_all_position_cases(): void
    {
        $this->assertSame('bottom', ModalPosition::Bottom->value);
        $this->assertSame('center', ModalPosition::Center->value);
        $this->assertSame('left', ModalPosition::Left->value);
        $this->assertSame('right', ModalPosition::Right->value);
        $this->assertSame('top', ModalPosition::Top->value);
    }

    #[Test]
    public function it_can_be_created_from_value(): void
    {
        $this->assertSame(ModalPosition::Center, ModalPosition::from('center'));
    }

    #[Test]
    public function it_has_exactly_five_cases(): void
    {
        $this->assertCount(5, ModalPosition::cases());
    }
}
