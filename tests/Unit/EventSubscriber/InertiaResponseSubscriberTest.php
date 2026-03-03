<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\EventSubscriber;

use EnzoBrigati\InertiaBundle\EventSubscriber\InertiaResponseSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class InertiaResponseSubscriberTest extends TestCase
{
    private function createEvent(Request $request, Response $response): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }

    #[Test]
    public function it_does_nothing_for_non_inertia_requests(): void
    {
        $subscriber = new InertiaResponseSubscriber(false);

        $request = Request::create('/test', 'PUT');
        $response = new RedirectResponse('/other', Response::HTTP_FOUND);

        $event = $this->createEvent($request, $response);
        $subscriber->onKernelResponse($event);

        // Status should remain 302 (not changed to 303)
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    #[Test]
    #[DataProvider('methodsThatShouldConvert302To303')]
    public function it_converts_302_to_303_for_put_patch_delete(string $method): void
    {
        $subscriber = new InertiaResponseSubscriber(false);

        $request = Request::create('/test', $method);
        $request->headers->set('X-Inertia', 'true');

        $response = new RedirectResponse('/other', Response::HTTP_FOUND);

        $event = $this->createEvent($request, $response);
        $subscriber->onKernelResponse($event);

        $this->assertSame(Response::HTTP_SEE_OTHER, $response->getStatusCode());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function methodsThatShouldConvert302To303(): array
    {
        return [
            'PUT' => ['PUT'],
            'PATCH' => ['PATCH'],
            'DELETE' => ['DELETE'],
        ];
    }

    #[Test]
    public function it_does_not_convert_302_for_get_requests(): void
    {
        $subscriber = new InertiaResponseSubscriber(false);

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $response = new RedirectResponse('/other', Response::HTTP_FOUND);

        $event = $this->createEvent($request, $response);
        $subscriber->onKernelResponse($event);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    #[Test]
    public function it_does_not_convert_302_for_post_requests(): void
    {
        $subscriber = new InertiaResponseSubscriber(false);

        $request = Request::create('/test', 'POST');
        $request->headers->set('X-Inertia', 'true');

        $response = new RedirectResponse('/other', Response::HTTP_FOUND);

        $event = $this->createEvent($request, $response);
        $subscriber->onKernelResponse($event);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    #[Test]
    public function it_does_not_modify_non_redirect_responses(): void
    {
        $subscriber = new InertiaResponseSubscriber(false);

        $request = Request::create('/test', 'PUT');
        $request->headers->set('X-Inertia', 'true');

        $response = new Response('OK', Response::HTTP_OK);

        $event = $this->createEvent($request, $response);
        $subscriber->onKernelResponse($event);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function it_sets_debug_toolbar_header_in_debug_mode(): void
    {
        $subscriber = new InertiaResponseSubscriber(true);

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = new Response('OK');

        $event = $this->createEvent($request, $response);
        $subscriber->onKernelResponse($event);

        $this->assertSame('1', $response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    #[Test]
    public function it_does_not_set_debug_toolbar_header_when_not_debug(): void
    {
        $subscriber = new InertiaResponseSubscriber(false);

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = new Response('OK');

        $event = $this->createEvent($request, $response);
        $subscriber->onKernelResponse($event);

        $this->assertNull($response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }
}
