<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Service;

use EnzoBrigati\InertiaBundle\ComponentLocator\InertiaComponentLocatorInterface;
use EnzoBrigati\InertiaBundle\ComponentResolver\InertiaComponentResolverInterface;
use EnzoBrigati\InertiaBundle\Exception\ComponentNotFoundException;
use EnzoBrigati\InertiaBundle\InertiaPage;
use EnzoBrigati\InertiaBundle\Modal\Modal;
use EnzoBrigati\InertiaBundle\Service\InertiaService;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

class InertiaServiceTest extends TestCase
{
    private InertiaService $service;
    private Environment $twig;
    private RequestStack $requestStack;
    private SerializerInterface $serializer;
    private InertiaComponentResolverInterface $componentResolver;
    private InertiaComponentLocatorInterface $componentLocator;
    private HttpKernelInterface $httpKernel;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->requestStack = new RequestStack();
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->componentResolver = $this->createMock(InertiaComponentResolverInterface::class);
        $this->componentLocator = $this->createMock(InertiaComponentLocatorInterface::class);
        $this->httpKernel = $this->createMock(HttpKernelInterface::class);

        $this->componentResolver
            ->method('resolve')
            ->willReturnArgument(0);
        $this->componentLocator
            ->method('exists')
            ->willReturn(true);

        $this->service = new InertiaService(
            $this->twig,
            $this->requestStack,
            $this->serializer,
            $this->componentResolver,
            $this->componentLocator,
            'base.html.twig',
            [],
            $this->httpKernel,
        );
    }

    private function pushRequest(array $headers = [], string $uri = '/test'): Request
    {
        $request = Request::create($uri);
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }
        $this->requestStack->push($request);

        return $request;
    }

    #[Test]
    public function it_can_share_props(): void
    {
        $this->service->share('key', 'value');

        $this->assertSame('value', $this->service->getShared('key'));
        $this->assertEquals(['key' => 'value'], $this->service->allShared());
    }

    #[Test]
    public function it_returns_null_for_unshared_prop(): void
    {
        $this->assertNull($this->service->getShared('nonexistent'));
    }

    #[Test]
    public function it_can_set_view_data(): void
    {
        $this->service->viewData('title', 'My Page');

        $this->assertSame('My Page', $this->service->getViewData('title'));
        $this->assertEquals(['title' => 'My Page'], $this->service->allViewData());
    }

    #[Test]
    public function it_can_set_context(): void
    {
        $this->service->context('groups', ['api']);

        $this->assertEquals(['api'], $this->service->getContext('groups'));
        $this->assertEquals(['groups' => ['api']], $this->service->allContext());
    }

    #[Test]
    public function it_can_set_and_get_version(): void
    {
        $this->assertNull($this->service->getVersion());

        $this->service->version('1.0.0');
        $this->assertSame('1.0.0', $this->service->getVersion());
    }

    #[Test]
    public function it_can_set_and_get_root_view(): void
    {
        $this->assertSame('base.html.twig', $this->service->getRootView());

        $this->service->setRootView('app.html.twig');
        $this->assertSame('app.html.twig', $this->service->getRootView());
    }

    #[Test]
    public function it_builds_inertia_page(): void
    {
        $this->pushRequest();

        $page = $this->service->buildPage('Users/Index', ['users' => [1, 2, 3]]);

        $this->assertInstanceOf(InertiaPage::class, $page);
        $this->assertSame('Users/Index', $page->component);
    }

    #[Test]
    public function it_throws_when_component_not_found(): void
    {
        $locator = $this->createMock(InertiaComponentLocatorInterface::class);
        $locator->method('exists')->willReturn(false);

        $service = new InertiaService(
            $this->twig,
            $this->requestStack,
            $this->serializer,
            $this->componentResolver,
            $locator,
            'base.html.twig',
        );

        $this->pushRequest();

        $this->expectException(ComponentNotFoundException::class);
        $service->buildPage('NonExistent');
    }

    #[Test]
    public function it_includes_shared_props_in_page(): void
    {
        $this->pushRequest();
        $this->service->share('appName', 'MyApp');

        $page = $this->service->buildPage('Test', ['local' => 'local data']);

        $resolved = $page->resolveProps();
        $this->assertSame('MyApp', $resolved['appName']);
        $this->assertSame('local data', $resolved['local']);
    }

    #[Test]
    public function it_local_props_override_shared_props(): void
    {
        $this->pushRequest();
        $this->service->share('key', 'shared data');

        $page = $this->service->buildPage('Test', ['key' => 'local data']);

        $resolved = $page->resolveProps();
        $this->assertSame('local data', $resolved['key']);
    }

    #[Test]
    public function it_renders_json_for_inertia_requests(): void
    {
        $this->pushRequest(['X-Inertia' => 'true']);

        $this->serializer
            ->method('serialize')
            ->willReturn('{"component":"Test","props":{}}');

        $response = $this->service->render('Test');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($response->headers->get('X-Inertia'));
        $this->assertSame('Accept', $response->headers->get('Vary'));
    }

    #[Test]
    public function it_renders_html_for_non_inertia_requests(): void
    {
        $this->pushRequest();

        $this->twig
            ->method('render')
            ->willReturn('<html><body><div id="app" data-page="{}"></div></body></html>');

        $response = $this->service->render('Test');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotInstanceOf(JsonResponse::class, $response);
        $this->assertStringContains('data-page', $response->getContent());
    }

    #[Test]
    public function it_uses_request_uri_as_default_url(): void
    {
        $this->pushRequest([], '/users?page=1');

        $page = $this->service->buildPage('Test');

        $this->assertSame('/users?page=1', $page->url);
    }

    #[Test]
    public function it_uses_custom_url_when_provided(): void
    {
        $this->pushRequest([], '/users');

        $page = $this->service->buildPage('Test', [], '/custom-url');

        $this->assertSame('/custom-url', $page->url);
    }

    #[Test]
    public function it_sets_version_on_page(): void
    {
        $this->pushRequest();
        $this->service->version('abc123');

        $page = $this->service->buildPage('Test');

        $this->assertSame('abc123', $page->version);
    }

    #[Test]
    public function it_creates_modal(): void
    {
        $modal = $this->service->modal('Users/Edit', ['user' => ['id' => 1]]);

        $this->assertInstanceOf(Modal::class, $modal);
    }

    #[Test]
    public function it_throws_when_creating_modal_without_http_kernel(): void
    {
        $service = new InertiaService(
            $this->twig,
            $this->requestStack,
            $this->serializer,
            $this->componentResolver,
            $this->componentLocator,
            'base.html.twig',
        );

        $this->expectException(LogicException::class);
        $service->modal('Test');
    }

    #[Test]
    public function it_location_returns_conflict_for_inertia_request(): void
    {
        $this->pushRequest(['X-Inertia' => 'true']);

        $response = $this->service->location('/external');

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame('/external', $response->headers->get('X-Inertia-Location'));
    }

    #[Test]
    public function it_location_returns_redirect_for_non_inertia_request(): void
    {
        $this->pushRequest();

        $response = $this->service->location('/external');

        $this->assertTrue($response->isRedirect('/external'));
    }

    #[Test]
    public function it_throws_when_no_request_available(): void
    {
        $this->expectException(LogicException::class);
        $this->service->buildPage('Test');
    }

    #[Test]
    public function it_passes_clear_and_encrypt_history_to_page(): void
    {
        $this->pushRequest();

        $page = $this->service->buildPage('Test', [], null, true, true);

        $this->assertTrue($page->clearHistory);
        $this->assertTrue($page->encryptHistory);
    }

    /**
     * Helper for string assertion.
     */
    private static function assertStringContains(string $needle, string $haystack): void
    {
        self::assertStringContainsString($needle, $haystack);
    }
}
