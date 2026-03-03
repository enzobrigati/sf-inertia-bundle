<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Modal;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

readonly class ModalHeaders
{
    public const string X_INERTIAUI_MODAL = 'X-InertiaUI-Modal';

    public const string X_INERTIAUI_MODAL_BASE_URL = 'X-InertiaUI-Modal-Base-Url';

    public const string X_INERTIAUI_MODAL_USE_ROUTER = 'X-InertiaUI-Modal-Use-Router';

    public function __construct(protected HeaderBag $headers)
    {
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->headers);
    }

    public function isModalRequest(): bool
    {
        return $this->headers->has(self::X_INERTIAUI_MODAL);
    }

    public function getModalId(): ?string
    {
        return $this->headers->get(self::X_INERTIAUI_MODAL);
    }

    public function getBaseUrl(): ?string
    {
        return $this->headers->get(self::X_INERTIAUI_MODAL_BASE_URL);
    }

    public function shouldUseRouter(): bool
    {
        $value = $this->headers->get(self::X_INERTIAUI_MODAL_USE_ROUTER);

        return ! in_array($value, ['0', 0], true);
    }
}
