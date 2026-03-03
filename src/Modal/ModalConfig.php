<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Modal;

use InvalidArgumentException;

class ModalConfig
{
    public function __construct(
        protected ?ModalType $type = null,
        protected ?bool $closeButton = null,
        protected ?bool $closeExplicitly = null,
        protected ?string $maxWidth = null,
        protected ?string $paddingClasses = null,
        protected ?string $panelClasses = null,
        protected ?ModalPosition $position = null
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    public static function new(): self
    {
        return new self();
    }

    public function modal(): self
    {
        $this->type = ModalType::Modal;

        return $this;
    }

    public function slideover(): self
    {
        $this->type = ModalType::Slideover;

        return $this;
    }

    public function closeButton(?bool $closeButton = true): self
    {
        $this->closeButton = $closeButton;

        return $this;
    }

    public function closeExplicitly(?bool $closeExplicitly = true): self
    {
        $this->closeExplicitly = $closeExplicitly;

        return $this;
    }

    public function maxWidth(?string $maxWidth): self
    {
        if (! in_array($maxWidth, ['sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'])) {
            throw new InvalidArgumentException('Invalid max width provided. Please use a value between sm and 7xl.');
        }

        $this->maxWidth = $maxWidth;

        return $this;
    }

    public function paddingClasses(?string $paddingClasses): self
    {
        $this->paddingClasses = $paddingClasses;

        return $this;
    }

    public function panelClasses(?string $panelClasses): self
    {
        $this->panelClasses = $panelClasses;

        return $this;
    }

    public function position(?ModalPosition $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function bottom(): self
    {
        return $this->position(ModalPosition::Bottom);
    }

    public function center(): self
    {
        return $this->position(ModalPosition::Center);
    }

    public function left(): self
    {
        return $this->position(ModalPosition::Left);
    }

    public function right(): self
    {
        return $this->position(ModalPosition::Right);
    }

    public function top(): self
    {
        return $this->position(ModalPosition::Top);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type?->value,
            'modal' => $this->type instanceof ModalType && $this->type === ModalType::Modal,
            'slideover' => $this->type instanceof ModalType && $this->type === ModalType::Slideover,
            'closeButton' => $this->closeButton,
            'closeExplicitly' => $this->closeExplicitly,
            'maxWidth' => $this->maxWidth,
            'paddingClasses' => $this->paddingClasses,
            'panelClasses' => $this->panelClasses,
            'position' => $this->position?->value,
        ];
    }
}
