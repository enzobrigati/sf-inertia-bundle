<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Modal;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\Service\InertiaInterface;

class Modal
{
    protected ?string $baseUrl = null;

    /**
     * @param array<string, mixed> $props
     */
    public function __construct(
        protected readonly string $component,
        protected readonly array $props,
        protected readonly InertiaInterface $inertia,
        protected readonly HttpKernelInterface $httpKernel,
    ) {
    }

    public function baseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function toResponse(Request $request): Response
    {
        $page = $this->inertia->buildPage($this->component, $this->props);
        $serialized = json_decode($this->inertia->serialize($page), true);
        $modalHeaders = ModalHeaders::fromRequest($request);
        $inertiaHeaders = InertiaHeaders::fromRequest($request);
        $baseUrl = $this->resolveBaseUrl($request);

        if (! $modalHeaders->shouldUseRouter() || $baseUrl === null) {
            if ($inertiaHeaders->isInertiaRequest()) {
                return $this->extractMeta(new JsonResponse($serialized));
            }

            // Fallback for non-Inertia requests: render normally
            return $this->inertia->render($this->component, $this->props);
        }

        // Normal case: dispatch sub-request to base URL
        $this->inertia->share('_inertiaui_modal', [
            ...$serialized,
            'id' => $modalHeaders->getModalId(),
            'baseUrl' => $baseUrl,
        ]);

        $response = $this->dispatchBaseUrlSubrequest($request, $baseUrl);

        return $this->spoofUrl($response, $serialized['url'] ?? $request->getRequestUri());
    }

    protected function resolveBaseUrl(Request $request): ?string
    {
        $candidates = [
            $request->headers->get(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL),
            $request->headers->get('referer'),
            $this->baseUrl,
        ];

        $currentPath = $this->extractPath($request->getRequestUri());

        foreach ($candidates as $candidate) {
            if ($candidate !== null && $this->extractPath($candidate) !== $currentPath) {
                return $candidate;
            }
        }

        return null;
    }

    protected function extractPath(string $url): string
    {
        $decoded = rawurldecode(trim((string) parse_url($url, PHP_URL_PATH), '/'));

        return $decoded !== '' ? $decoded : '/';
    }

    protected function dispatchBaseUrlSubrequest(Request $originalRequest, string $baseUrl): Response
    {
        $subRequest = Request::create(
            $baseUrl,
            'GET',
            $originalRequest->query->all(),
            $originalRequest->cookies->all(),
            [],
            $originalRequest->server->all(),
        );

        $subRequest->headers->replace($originalRequest->headers->all());

        if ($originalRequest->hasSession()) {
            $subRequest->setSession($originalRequest->getSession());
        }

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    protected function spoofUrl(Response $response, string $url): Response
    {
        if ($response instanceof JsonResponse) {
            $data = json_decode((string) $response->getContent(), true);
            $data['url'] = $url;
            $response->setData($data);

            return $response;
        }

        // HTML response: replace URL in data-page attribute
        $content = (string) $response->getContent();
        $content = (string) preg_replace_callback(
            '/data-page="([^"]*)"/',
            function (array $matches) use ($url): string {
                $pageData = json_decode(htmlspecialchars_decode($matches[1]), true);

                if (is_array($pageData)) {
                    $pageData['url'] = $url;

                    return 'data-page="' . htmlspecialchars(json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', ENT_QUOTES, 'UTF-8') . '"';
                }

                return $matches[0];
            },
            $content,
        );

        $response->setContent($content);

        return $response;
    }

    protected function extractMeta(JsonResponse $response): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getContent(), true);
        $data['meta'] = [];

        foreach (['mergeProps', 'deferredProps', 'cache'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $data['meta'][$key] = $data[$key];
            unset($data[$key]);
        }

        if (empty($data['meta'])) {
            $data['meta'] = new \stdClass();
        }

        $response->setData($data);

        return $response;
    }
}
