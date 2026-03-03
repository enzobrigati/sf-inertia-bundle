<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Modal;

use EnzoBrigati\InertiaBundle\Modal\ModalConfig;
use EnzoBrigati\InertiaBundle\Modal\ModalPosition;
use EnzoBrigati\InertiaBundle\Modal\ModalType;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModalConfigTest extends TestCase
{
    #[Test]
    public function it_can_create_a_new_instance(): void
    {
        $config = ModalConfig::new();

        $this->assertInstanceOf(ModalConfig::class, $config);
        $this->assertEquals([
            'type' => null,
            'modal' => false,
            'slideover' => false,
            'closeButton' => null,
            'closeExplicitly' => null,
            'maxWidth' => null,
            'paddingClasses' => null,
            'panelClasses' => null,
            'position' => null,
        ], $config->toArray());
    }

    #[Test]
    public function it_can_create_via_make(): void
    {
        $config = ModalConfig::make();

        $this->assertInstanceOf(ModalConfig::class, $config);
    }

    #[Test]
    public function it_can_set_the_type_to_modal(): void
    {
        $config = ModalConfig::new()->modal();

        $this->assertEquals(ModalType::Modal->value, $config->toArray()['type']);
        $this->assertTrue($config->toArray()['modal']);
        $this->assertFalse($config->toArray()['slideover']);
    }

    #[Test]
    public function it_can_set_the_type_to_slideover(): void
    {
        $config = ModalConfig::new()->slideover();

        $this->assertEquals(ModalType::Slideover->value, $config->toArray()['type']);
        $this->assertFalse($config->toArray()['modal']);
        $this->assertTrue($config->toArray()['slideover']);
    }

    #[Test]
    public function it_can_configure_the_close_button(): void
    {
        $config = ModalConfig::new()->closeButton();
        $this->assertTrue($config->toArray()['closeButton']);

        $config = ModalConfig::new()->closeButton(false);
        $this->assertFalse($config->toArray()['closeButton']);
    }

    #[Test]
    public function it_can_configure_the_explicit_closing(): void
    {
        $config = ModalConfig::new()->closeExplicitly();
        $this->assertTrue($config->toArray()['closeExplicitly']);

        $config = ModalConfig::new()->closeExplicitly(false);
        $this->assertFalse($config->toArray()['closeExplicitly']);
    }

    #[Test]
    #[DataProvider('validMaxWidthProvider')]
    public function it_can_set_a_max_width(string $width): void
    {
        $config = ModalConfig::new()->maxWidth($width);
        $this->assertEquals($width, $config->toArray()['maxWidth']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validMaxWidthProvider(): array
    {
        return [
            'sm width' => ['sm'],
            'md width' => ['md'],
            'lg width' => ['lg'],
            'xl width' => ['xl'],
            '2xl width' => ['2xl'],
            '3xl width' => ['3xl'],
            '4xl width' => ['4xl'],
            '5xl width' => ['5xl'],
            '6xl width' => ['6xl'],
            '7xl width' => ['7xl'],
        ];
    }

    #[Test]
    public function it_throws_an_exception_for_invalid_max_width(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ModalConfig::new()->maxWidth('invalid');
    }

    #[Test]
    public function it_can_set_padding_classes(): void
    {
        $config = ModalConfig::new()->paddingClasses('p-4');

        $this->assertEquals('p-4', $config->toArray()['paddingClasses']);
    }

    #[Test]
    public function it_can_set_panel_classes(): void
    {
        $config = ModalConfig::new()->panelClasses('bg-white');

        $this->assertEquals('bg-white', $config->toArray()['panelClasses']);
    }

    #[Test]
    public function it_can_set_the_position(): void
    {
        $config = ModalConfig::new()->position(ModalPosition::Center);

        $this->assertEquals(ModalPosition::Center->value, $config->toArray()['position']);
    }

    #[Test]
    #[DataProvider('positionMethodsProvider')]
    public function it_can_set_predefined_positions(string $method, ModalPosition $expectedPosition): void
    {
        $config = ModalConfig::new()->$method();
        $this->assertEquals($expectedPosition->value, $config->toArray()['position']);
    }

    /**
     * @return array<string, array{string, ModalPosition}>
     */
    public static function positionMethodsProvider(): array
    {
        return [
            'bottom position' => ['bottom', ModalPosition::Bottom],
            'center position' => ['center', ModalPosition::Center],
            'left position' => ['left', ModalPosition::Left],
            'right position' => ['right', ModalPosition::Right],
            'top position' => ['top', ModalPosition::Top],
        ];
    }

    #[Test]
    public function it_can_chain_multiple_configurations(): void
    {
        $config = ModalConfig::new()
            ->modal()
            ->closeButton()
            ->maxWidth('2xl')
            ->paddingClasses('p-4')
            ->panelClasses('bg-white')
            ->center();

        $this->assertEquals([
            'type' => ModalType::Modal->value,
            'modal' => true,
            'slideover' => false,
            'closeButton' => true,
            'closeExplicitly' => null,
            'maxWidth' => '2xl',
            'paddingClasses' => 'p-4',
            'panelClasses' => 'bg-white',
            'position' => ModalPosition::Center->value,
        ], $config->toArray());
    }
}
