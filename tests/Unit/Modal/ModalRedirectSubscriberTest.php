<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Modal;

use EnzoBrigati\InertiaBundle\Modal\ModalHeaders;
use EnzoBrigati\InertiaBundle\Modal\ModalRedirectSubscriber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ModalRedirectSubscriberTest extends TestCase
{
    private function createEvent(
        Request $request,
        Response $response,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): ResponseEvent {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ResponseEvent($kernel, $request, $requestType, $response);
    }

    #[Test]
    public function it_redirects_to_base_url_when_header_is_present(): void
    {
        $subscriber = new ModalRedirectSubscriber(true);

        $request = Request::create('/users', 'POST');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL, '/dashboard');

        $response = new RedirectResponse('/users/1');
        $event = $this->createEvent($request, $response);

        $subscriber->onKernelResponse($event);

        $this->assertSame('/dashboard', $response->getTargetUrl());
    }

    #[Test]
    public function it_does_not_modify_redirect_when_no_base_url_header(): void
    {
        $subscriber = new ModalRedirectSubscriber(true);

        $request = Request::create('/users', 'POST');
        $response = new RedirectResponse('/users/1');

        $event = $this->createEvent($request, $response);

        $subscriber->onKernelResponse($event);

        $this->assertSame('/users/1', $response->getTargetUrl());
    }

    #[Test]
    public function it_does_not_modify_non_redirect_responses(): void
    {
        $subscriber = new ModalRedirectSubscriber(true);

        $request = Request::create('/users');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL, '/dashboard');

        $response = new Response('OK');
        $event = $this->createEvent($request, $response);

        $subscriber->onKernelResponse($event);

        $this->assertSame('OK', $response->getContent());
    }

    #[Test]
    public function it_does_nothing_when_disabled(): void
    {
        $subscriber = new ModalRedirectSubscriber(false);

        $request = Request::create('/users', 'POST');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL, '/dashboard');

        $response = new RedirectResponse('/users/1');
        $event = $this->createEvent($request, $response);

        $subscriber->onKernelResponse($event);

        $this->assertSame('/users/1', $response->getTargetUrl());
    }

    #[Test]
    public function it_does_not_modify_sub_requests(): void
    {
        $subscriber = new ModalRedirectSubscriber(true);

        $request = Request::create('/users', 'POST');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL, '/dashboard');

        $response = new RedirectResponse('/users/1');
        $event = $this->createEvent($request, $response, HttpKernelInterface::SUB_REQUEST);

        $subscriber->onKernelResponse($event);

        $this->assertSame('/users/1', $response->getTargetUrl());
    }
}
