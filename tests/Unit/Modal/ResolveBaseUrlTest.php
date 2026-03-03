<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Modal;

use EnzoBrigati\InertiaBundle\Modal\Modal;
use EnzoBrigati\InertiaBundle\Service\InertiaInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ResolveBaseUrlTest extends TestCase
{
    private Modal $modal;

    protected function setUp(): void
    {
        $inertia = $this->createMock(InertiaInterface::class);
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $this->modal = new TestableModal('TestComponent', [], $inertia, $httpKernel);
    }

    #[Test]
    public function it_prioritizes_header_over_referer_and_configured_base_url(): void
    {
        $this->modal->baseUrl('https://example.com/configured');

        $request = Request::create('/users/1/edit', 'GET');
        $request->headers->set('X-InertiaUI-Modal-Base-Url', 'https://example.com/from-header');
        $request->headers->set('referer', 'https://example.com/from-referer');

        $this->assertSame('https://example.com/from-header', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_skips_header_when_it_matches_current_path(): void
    {
        $this->modal->baseUrl('https://example.com/users');

        $request = Request::create('/users/1/edit', 'GET');
        $request->headers->set('X-InertiaUI-Modal-Base-Url', 'https://example.com/users/1/edit');
        $request->headers->set('referer', 'https://example.com/dashboard');

        $this->assertSame('https://example.com/dashboard', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_skips_configured_base_url_when_it_matches_current_path(): void
    {
        $this->modal->baseUrl('https://example.com/users/1/edit');

        $request = Request::create('/users/1/edit', 'GET');

        $this->assertNull($this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_falls_back_to_configured_base_url_when_header_and_referer_match_current_path(): void
    {
        $this->modal->baseUrl('https://example.com/users');

        $request = Request::create('/users/1/edit', 'GET');
        $request->headers->set('X-InertiaUI-Modal-Base-Url', 'https://example.com/users/1/edit');
        $request->headers->set('referer', 'https://example.com/users/1/edit');

        $this->assertSame('https://example.com/users', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_uses_referer_when_path_differs_from_current_request(): void
    {
        $this->modal->baseUrl('https://example.com/configured');

        $request = Request::create('/users/1/edit', 'GET');
        $request->headers->set('referer', 'https://example.com/dashboard');

        $this->assertSame('https://example.com/dashboard', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_uses_configured_base_url_when_no_headers(): void
    {
        $this->modal->baseUrl('https://example.com/base');

        $request = Request::create('/users/1/edit', 'GET');

        $this->assertSame('https://example.com/base', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_returns_null_when_no_base_url_sources_available(): void
    {
        $request = Request::create('/users/1/edit', 'GET');

        $this->assertNull($this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_ignores_referer_when_path_matches_current_request(): void
    {
        $this->modal->baseUrl('https://example.com/users');

        $request = Request::create('/users/1/edit', 'GET');
        $request->headers->set('referer', 'https://example.com/users/1/edit');

        $this->assertSame('https://example.com/users', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_ignores_referer_query_strings_when_comparing_paths(): void
    {
        $this->modal->baseUrl('https://example.com/users');

        $request = Request::create('/users/1/edit?navigate=1', 'GET');
        $request->headers->set('referer', 'https://example.com/users/1/edit?foo=bar');

        $this->assertSame('https://example.com/users', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_returns_null_when_referer_matches_and_no_configured_base_url(): void
    {
        $request = Request::create('/users/1/edit', 'GET');
        $request->headers->set('referer', 'https://example.com/users/1/edit');

        $this->assertNull($this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_uses_referer_when_it_is_a_parent_or_different_path(): void
    {
        $this->modal->baseUrl('https://example.com/configured');

        $request = Request::create('/users/1/edit', 'GET');
        $request->headers->set('referer', 'https://example.com/users');

        $this->assertSame('https://example.com/users', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_normalizes_trailing_slashes_when_comparing_paths(): void
    {
        $this->modal->baseUrl('https://example.com/users');

        $request = Request::create('/users/1/edit/', 'GET');
        $request->headers->set('referer', 'https://example.com/users/1/edit');

        $this->assertSame('https://example.com/users', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_handles_root_path(): void
    {
        $this->modal->baseUrl('https://example.com/dashboard');

        $request = Request::create('/', 'GET');
        $request->headers->set('referer', 'https://example.com/');

        $this->assertSame('https://example.com/dashboard', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_compares_paths_case_sensitively(): void
    {
        $this->modal->baseUrl('https://example.com/configured');

        $request = Request::create('/Users/1/Edit', 'GET');
        $request->headers->set('referer', 'https://example.com/users/1/edit');

        $this->assertSame('https://example.com/users/1/edit', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_handles_referer_without_scheme(): void
    {
        $this->modal->baseUrl('https://example.com/users');

        $request = Request::create('/users/1/edit', 'GET');
        $request->headers->set('referer', '/users/1/edit');

        $this->assertSame('https://example.com/users', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_ignores_port_and_fragment_when_comparing_paths(): void
    {
        $this->modal->baseUrl('https://example.com/users');

        $request = Request::create('/users/1/edit', 'GET');
        $request->headers->set('referer', 'https://example.com:8080/users/1/edit#section');

        $this->assertSame('https://example.com/users', $this->modal->testResolveBaseUrl($request));
    }

    #[Test]
    public function it_decodes_url_encoded_paths_when_comparing(): void
    {
        $this->modal->baseUrl('https://example.com/users');

        $request = Request::create('/users/John%20Doe/edit', 'GET');
        $request->headers->set('referer', 'https://example.com/users/John%20Doe/edit');

        $this->assertSame('https://example.com/users', $this->modal->testResolveBaseUrl($request));
    }
}

/**
 * Testable subclass to expose protected methods.
 */
class TestableModal extends Modal
{
    public function testResolveBaseUrl(Request $request): ?string
    {
        return $this->resolveBaseUrl($request);
    }
}
