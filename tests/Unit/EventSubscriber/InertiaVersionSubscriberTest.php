<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\EventSubscriber;

use EnzoBrigati\InertiaBundle\EventSubscriber\InertiaVersionSubscriber;
use EnzoBrigati\InertiaBundle\Service\InertiaInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class InertiaVersionSubscriberTest extends TestCase
{
    private function createEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    #[Test]
    public function it_does_nothing_for_non_inertia_requests(): void
    {
        $inertia = $this->createMock(InertiaInterface::class);
        $inertia->expects($this->never())->method('getVersion');

        $subscriber = new InertiaVersionSubscriber($inertia);

        $request = Request::create('/test', 'GET');
        $event = $this->createEvent($request);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function it_does_nothing_when_versions_match(): void
    {
        $inertia = $this->createMock(InertiaInterface::class);
        $inertia->method('getVersion')->willReturn('abc123');

        $subscriber = new InertiaVersionSubscriber($inertia);

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'abc123');

        $event = $this->createEvent($request);
        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function it_forces_redirect_when_versions_differ(): void
    {
        $locationResponse = new Response('', Response::HTTP_CONFLICT, [
            'X-Inertia-Location' => 'http://localhost/test',
        ]);

        $inertia = $this->createMock(InertiaInterface::class);
        $inertia->method('getVersion')->willReturn('new-version');
        $inertia->method('location')->willReturn($locationResponse);

        $subscriber = new InertiaVersionSubscriber($inertia);

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'old-version');

        $event = $this->createEvent($request);
        $subscriber->onKernelRequest($event);

        $this->assertNotNull($event->getResponse());
        $this->assertSame(Response::HTTP_CONFLICT, $event->getResponse()->getStatusCode());
    }

    #[Test]
    public function it_does_nothing_when_server_version_is_null(): void
    {
        $inertia = $this->createMock(InertiaInterface::class);
        $inertia->method('getVersion')->willReturn(null);

        $subscriber = new InertiaVersionSubscriber($inertia);

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'any-version');

        $event = $this->createEvent($request);
        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function it_does_nothing_for_non_get_requests(): void
    {
        $inertia = $this->createMock(InertiaInterface::class);
        $inertia->method('getVersion')->willReturn('new-version');

        $subscriber = new InertiaVersionSubscriber($inertia);

        $request = Request::create('/test', 'POST');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'old-version');

        $event = $this->createEvent($request);
        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }
}
