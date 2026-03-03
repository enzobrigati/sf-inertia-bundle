<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\EventSubscriber;

use EnzoBrigati\InertiaBundle\EventSubscriber\InertiaCsrfSubscriber;
use EnzoBrigati\InertiaBundle\ResponseFactory\InertiaResponseFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class InertiaCsrfSubscriberTest extends TestCase
{
    private CsrfTokenManagerInterface $csrfTokenManager;
    private InertiaResponseFactory $responseFactory;

    protected function setUp(): void
    {
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->responseFactory = $this->createMock(InertiaResponseFactory::class);
    }

    private function createSubscriber(bool $enabled = true): InertiaCsrfSubscriber
    {
        return new InertiaCsrfSubscriber(
            $this->csrfTokenManager,
            $this->responseFactory,
            $enabled,
        );
    }

    private function createRequestEvent(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $type,
        );
    }

    private function createResponseEvent(
        Request $request,
        Response $response,
        int $type = HttpKernelInterface::MAIN_REQUEST,
    ): ResponseEvent {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $type,
            $response,
        );
    }

    #[Test]
    public function it_does_nothing_for_non_inertia_requests(): void
    {
        $subscriber = $this->createSubscriber();

        $request = Request::create('/test');
        $event = $this->createRequestEvent($request);

        $this->csrfTokenManager->expects($this->never())->method('isTokenValid');

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function it_does_nothing_when_disabled(): void
    {
        $subscriber = $this->createSubscriber(false);

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');

        $event = $this->createRequestEvent($request);

        $this->csrfTokenManager->expects($this->never())->method('isTokenValid');

        $subscriber->onKernelRequest($event);
    }

    #[Test]
    public function it_validates_csrf_token_for_inertia_requests(): void
    {
        $subscriber = $this->createSubscriber();

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-XSRF-TOKEN', 'valid-token');
        $request->attributes->set('_route', 'app_test');

        $this->csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true);

        $event = $this->createRequestEvent($request);
        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function it_skips_internal_routes(): void
    {
        $subscriber = $this->createSubscriber();

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->attributes->set('_route', '_wdt');

        $this->csrfTokenManager->expects($this->never())->method('isTokenValid');

        $event = $this->createRequestEvent($request);
        $subscriber->onKernelRequest($event);
    }

    #[Test]
    public function it_skips_sub_requests(): void
    {
        $subscriber = $this->createSubscriber();

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->attributes->set('_route', 'app_test');

        $this->csrfTokenManager->expects($this->never())->method('isTokenValid');

        $event = $this->createRequestEvent($request, HttpKernelInterface::SUB_REQUEST);
        $subscriber->onKernelRequest($event);
    }

    #[Test]
    public function it_sets_csrf_cookie_on_response(): void
    {
        $subscriber = $this->createSubscriber();

        $token = new CsrfToken('X-Inertia-CSRF-TOKEN', 'token-value');
        $this->csrfTokenManager->method('getToken')->willReturn($token);

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->attributes->set('_route', 'app_test');

        $response = new Response('OK');
        $event = $this->createResponseEvent($request, $response);

        $subscriber->onKernelResponse($event);

        $cookies = $response->headers->getCookies();
        $this->assertNotEmpty($cookies);

        $csrfCookie = $cookies[0];
        $this->assertSame('XSRF-TOKEN', $csrfCookie->getName());
        $this->assertSame('token-value', $csrfCookie->getValue());
    }

    #[Test]
    public function it_does_not_set_cookie_on_redirect_response(): void
    {
        $subscriber = $this->createSubscriber();

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->attributes->set('_route', 'app_test');

        $response = new Response('', Response::HTTP_FOUND, ['Location' => '/other']);
        $event = $this->createResponseEvent($request, $response);

        $this->csrfTokenManager->expects($this->never())->method('getToken');

        $subscriber->onKernelResponse($event);
    }

    #[Test]
    public function it_does_not_set_cookie_on_sub_requests(): void
    {
        $subscriber = $this->createSubscriber();

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->attributes->set('_route', 'app_test');

        $response = new Response('OK');
        $event = $this->createResponseEvent($request, $response, HttpKernelInterface::SUB_REQUEST);

        $this->csrfTokenManager->expects($this->never())->method('getToken');

        $subscriber->onKernelResponse($event);
    }
}
