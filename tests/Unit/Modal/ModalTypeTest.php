<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Modal;

use EnzoBrigati\InertiaBundle\Modal\ModalType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModalTypeTest extends TestCase
{
    #[Test]
    public function it_has_modal_case(): void
    {
        $this->assertSame('modal', ModalType::Modal->value);
    }

    #[Test]
    public function it_has_slideover_case(): void
    {
        $this->assertSame('slideover', ModalType::Slideover->value);
    }

    #[Test]
    public function it_can_be_created_from_value(): void
    {
        $this->assertSame(ModalType::Modal, ModalType::from('modal'));
        $this->assertSame(ModalType::Slideover, ModalType::from('slideover'));
    }

    #[Test]
    public function it_has_exactly_two_cases(): void
    {
        $this->assertCount(2, ModalType::cases());
    }
}
