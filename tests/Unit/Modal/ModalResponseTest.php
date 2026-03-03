<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Modal;

use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\InertiaPage;
use EnzoBrigati\InertiaBundle\Modal\Modal;
use EnzoBrigati\InertiaBundle\Modal\ModalHeaders;
use EnzoBrigati\InertiaBundle\Service\InertiaInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ModalResponseTest extends TestCase
{
    private InertiaInterface $inertia;
    private HttpKernelInterface $httpKernel;

    protected function setUp(): void
    {
        $this->inertia = $this->createMock(InertiaInterface::class);
        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
    }

    private function createPage(string $component = 'Users/Edit', array $props = []): InertiaPage
    {
        return new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            $component,
            $props,
            '/users/1/edit',
            '1.0',
        );
    }

    private function setupInertiaForModal(string $component = 'Users/Edit', array $props = []): void
    {
        $page = $this->createPage($component, $props);

        $this->inertia
            ->method('buildPage')
            ->willReturn($page);

        $serialized = json_encode([
            'component' => $component,
            'props' => $props ?: new \stdClass(),
            'url' => '/users/1/edit',
            'version' => '1.0',
            'clearHistory' => false,
            'encryptHistory' => false,
        ]);

        $this->inertia
            ->method('serialize')
            ->willReturn($serialized);
    }

    #[Test]
    public function it_can_set_base_url(): void
    {
        $modal = new Modal('Test', [], $this->inertia, $this->httpKernel);

        $result = $modal->baseUrl('/users');

        $this->assertSame($modal, $result);
    }

    #[Test]
    public function it_returns_modal_data_when_not_using_router(): void
    {
        $this->setupInertiaForModal('Users/Edit', ['user' => ['id' => 1]]);

        $modal = new Modal('Users/Edit', ['user' => ['id' => 1]], $this->inertia, $this->httpKernel);

        $request = Request::create('/users/1/edit');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '0');

        $response = $modal->toResponse($request);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Users/Edit', $data['component']);
        $this->assertArrayHasKey('meta', $data);
    }

    #[Test]
    public function it_returns_modal_data_with_meta_extraction(): void
    {
        $page = $this->createPage();
        $this->inertia->method('buildPage')->willReturn($page);

        $serialized = json_encode([
            'component' => 'Users/Edit',
            'props' => new \stdClass(),
            'url' => '/users/1/edit',
            'version' => '1.0',
            'clearHistory' => false,
            'encryptHistory' => false,
            'mergeProps' => ['items'],
            'deferredProps' => ['group1' => ['stats']],
        ]);
        $this->inertia->method('serialize')->willReturn($serialized);

        $modal = new Modal('Users/Edit', [], $this->inertia, $this->httpKernel);

        $request = Request::create('/users/1/edit');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '0');

        $response = $modal->toResponse($request);
        $data = json_decode($response->getContent(), true);

        // mergeProps and deferredProps should be moved to meta
        $this->assertArrayNotHasKey('mergeProps', $data);
        $this->assertArrayNotHasKey('deferredProps', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals(['items'], $data['meta']['mergeProps']);
        $this->assertEquals(['group1' => ['stats']], $data['meta']['deferredProps']);
    }

    #[Test]
    public function it_returns_empty_meta_object_when_no_meta_keys(): void
    {
        $this->setupInertiaForModal();

        $modal = new Modal('Users/Edit', [], $this->inertia, $this->httpKernel);

        $request = Request::create('/users/1/edit');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '0');

        $response = $modal->toResponse($request);
        $data = json_decode($response->getContent(), true);

        // Empty meta should be an empty object, not array
        $raw = json_decode($response->getContent());
        $this->assertIsObject($raw->meta);
    }

    #[Test]
    public function it_throws_404_for_non_inertia_direct_access(): void
    {
        $this->setupInertiaForModal();

        $modal = new Modal('Users/Edit', [], $this->inertia, $this->httpKernel);

        $request = Request::create('/users/1/edit');
        // No X-Inertia header, no use-router header → direct browser access

        $this->expectException(NotFoundHttpException::class);

        $modal->toResponse($request);
    }

    #[Test]
    public function it_dispatches_sub_request_when_using_router_with_base_url(): void
    {
        $this->setupInertiaForModal();

        $basePageResponse = new JsonResponse([
            'component' => 'Users/Index',
            'props' => ['users' => [1, 2, 3]],
            'url' => '/users',
            'version' => '1.0',
        ]);

        $this->httpKernel
            ->method('handle')
            ->willReturn($basePageResponse);

        $modal = new Modal('Users/Edit', [], $this->inertia, $this->httpKernel);
        $modal->baseUrl('/users');

        $request = Request::create('/users/1/edit');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL, 'modal-123');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '1');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL, '/users');

        $response = $modal->toResponse($request);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);
        // URL should be spoofed to the modal URL, not the base URL
        $this->assertSame('/users/1/edit', $data['url']);
    }

    #[Test]
    public function it_spoofs_url_in_json_response(): void
    {
        $this->setupInertiaForModal();

        $basePageResponse = new JsonResponse([
            'component' => 'Users/Index',
            'props' => [],
            'url' => '/users',
            'version' => '1.0',
        ]);

        $this->httpKernel->method('handle')->willReturn($basePageResponse);

        $modal = new Modal('Users/Edit', [], $this->inertia, $this->httpKernel);

        $request = Request::create('/users/1/edit');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL, 'modal-1');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '1');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL, '/users');

        $response = $modal->toResponse($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('/users/1/edit', $data['url']);
    }

    #[Test]
    public function it_spoofs_url_in_html_response(): void
    {
        $this->setupInertiaForModal();

        $pageData = json_encode([
            'component' => 'Users/Index',
            'props' => [],
            'url' => '/users',
            'version' => '1.0',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $html = '<html><body><div id="app" data-page="' . htmlspecialchars($pageData, ENT_QUOTES, 'UTF-8') . '"></div></body></html>';
        $htmlResponse = new Response($html);

        $this->httpKernel->method('handle')->willReturn($htmlResponse);

        $modal = new Modal('Users/Edit', [], $this->inertia, $this->httpKernel);

        $request = Request::create('/users/1/edit');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL, 'modal-1');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '1');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL, '/users');

        $response = $modal->toResponse($request);

        $content = $response->getContent();
        $this->assertStringContainsString('/users/1/edit', $content);

        // Extract data-page and verify URL was replaced
        preg_match('/data-page="([^"]*)"/', $content, $matches);
        $pageDataDecoded = json_decode(htmlspecialchars_decode($matches[1]), true);
        $this->assertSame('/users/1/edit', $pageDataDecoded['url']);
    }

    #[Test]
    public function it_shares_modal_data_before_sub_request(): void
    {
        $this->setupInertiaForModal();

        $basePageResponse = new JsonResponse([
            'component' => 'Users/Index',
            'props' => [],
            'url' => '/users',
        ]);
        $this->httpKernel->method('handle')->willReturn($basePageResponse);

        $sharedKey = null;
        $sharedValue = null;
        $this->inertia
            ->method('share')
            ->willReturnCallback(function (string $key, mixed $value) use (&$sharedKey, &$sharedValue) {
                $sharedKey = $key;
                $sharedValue = $value;
            });

        $modal = new Modal('Users/Edit', [], $this->inertia, $this->httpKernel);

        $request = Request::create('/users/1/edit');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL, 'modal-abc');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '1');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL, '/users');

        $modal->toResponse($request);

        $this->assertSame('_inertiaui_modal', $sharedKey);
        $this->assertIsArray($sharedValue);
        $this->assertSame('modal-abc', $sharedValue['id']);
        $this->assertSame('/users', $sharedValue['baseUrl']);
        $this->assertSame('Users/Edit', $sharedValue['component']);
    }

    #[Test]
    public function it_returns_modal_only_when_base_url_is_null(): void
    {
        $this->setupInertiaForModal();

        $modal = new Modal('Users/Edit', [], $this->inertia, $this->httpKernel);
        // No baseUrl set, no header, no referer

        $request = Request::create('/users/1/edit');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '1');

        $response = $modal->toResponse($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('meta', $data);
    }
}
