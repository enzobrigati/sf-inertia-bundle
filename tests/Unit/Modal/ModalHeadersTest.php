<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Modal;

use EnzoBrigati\InertiaBundle\Modal\ModalHeaders;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ModalHeadersTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_request(): void
    {
        $request = Request::create('/test');
        $headers = ModalHeaders::fromRequest($request);

        $this->assertInstanceOf(ModalHeaders::class, $headers);
    }

    #[Test]
    public function it_detects_modal_request(): void
    {
        $request = Request::create('/test');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL, 'modal-123');

        $headers = ModalHeaders::fromRequest($request);

        $this->assertTrue($headers->isModalRequest());
    }

    #[Test]
    public function it_detects_non_modal_request(): void
    {
        $request = Request::create('/test');

        $headers = ModalHeaders::fromRequest($request);

        $this->assertFalse($headers->isModalRequest());
    }

    #[Test]
    public function it_returns_modal_id(): void
    {
        $request = Request::create('/test');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL, 'modal-456');

        $headers = ModalHeaders::fromRequest($request);

        $this->assertSame('modal-456', $headers->getModalId());
    }

    #[Test]
    public function it_returns_null_when_no_modal_id(): void
    {
        $request = Request::create('/test');

        $headers = ModalHeaders::fromRequest($request);

        $this->assertNull($headers->getModalId());
    }

    #[Test]
    public function it_returns_base_url(): void
    {
        $request = Request::create('/test');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_BASE_URL, '/users');

        $headers = ModalHeaders::fromRequest($request);

        $this->assertSame('/users', $headers->getBaseUrl());
    }

    #[Test]
    public function it_returns_null_when_no_base_url(): void
    {
        $request = Request::create('/test');

        $headers = ModalHeaders::fromRequest($request);

        $this->assertNull($headers->getBaseUrl());
    }

    #[Test]
    public function it_returns_true_for_use_router_when_header_is_1(): void
    {
        $request = Request::create('/test');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '1');

        $headers = ModalHeaders::fromRequest($request);

        $this->assertTrue($headers->shouldUseRouter());
    }

    #[Test]
    public function it_returns_false_for_use_router_when_header_is_0(): void
    {
        $request = Request::create('/test');
        $request->headers->set(ModalHeaders::X_INERTIAUI_MODAL_USE_ROUTER, '0');

        $headers = ModalHeaders::fromRequest($request);

        $this->assertFalse($headers->shouldUseRouter());
    }

    #[Test]
    public function it_returns_false_for_use_router_when_header_is_absent(): void
    {
        $request = Request::create('/test');

        $headers = ModalHeaders::fromRequest($request);

        $this->assertFalse($headers->shouldUseRouter());
    }
}
