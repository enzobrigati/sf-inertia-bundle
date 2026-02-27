<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\ResponseFactory\InertiaResponseFactory;

class InertiaExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly InertiaResponseFactory $responseFactory,
        private readonly bool $enabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($this->enabled === false) {
            return;
        }

        if ($event->getResponse() !== null) {
            return;
        }

        $request = $event->getRequest();
        $headers = InertiaHeaders::fromRequest($request);

        if ($headers->isInertiaRequest() === false) {
            return;
        }

        $response = $this->responseFactory->handle($request, $event->getThrowable());

        if ($response !== null) {
            $event->setResponse($response);
        }
    }
}
