<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Modal;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ModalRedirectSubscriber
{
    public function __construct(
        private readonly bool $redirectToBaseUrl = true,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (! $this->redirectToBaseUrl) {
            return;
        }

        if (! $event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        if (! $response instanceof RedirectResponse) {
            return;
        }

        $request = $event->getRequest();
        $baseUrl = $request->headers->get(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL);

        if ($baseUrl === null) {
            return;
        }

        $response->setTargetUrl($baseUrl);
    }
}
