<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit;

use EnzoBrigati\InertiaBundle\InertiaHeaders;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class InertiaHeadersTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_request(): void
    {
        $request = Request::create('/test');
        $headers = InertiaHeaders::fromRequest($request);

        $this->assertInstanceOf(InertiaHeaders::class, $headers);
    }

    #[Test]
    public function it_detects_inertia_request(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertTrue($headers->isInertiaRequest());
    }

    #[Test]
    public function it_detects_non_inertia_request(): void
    {
        $request = Request::create('/test');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertFalse($headers->isInertiaRequest());
    }

    #[Test]
    public function it_detects_partial_request_for_matching_component(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia-Partial-Component', 'Users/Index');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertTrue($headers->isPartialRequest('Users/Index'));
        $this->assertFalse($headers->isPartialRequest('Users/Edit'));
    }

    #[Test]
    public function it_returns_partial_only_props(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia-Partial-Data', 'users,roles');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertEquals(['users', 'roles'], $headers->getPartialOnlyProps());
    }

    #[Test]
    public function it_returns_empty_array_when_no_partial_only_props(): void
    {
        $request = Request::create('/test');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertEquals([], $headers->getPartialOnlyProps());
    }

    #[Test]
    public function it_returns_partial_except_props(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia-Partial-Except', 'users,roles');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertEquals(['users', 'roles'], $headers->getPartialExceptProps());
    }

    #[Test]
    public function it_returns_reset_props(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia-Reset', 'users');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertEquals(['users'], $headers->getResetProps());
    }

    #[Test]
    public function it_returns_append_merge_intent_by_default(): void
    {
        $request = Request::create('/test');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertSame('append', $headers->getMergeIntent());
    }

    #[Test]
    public function it_returns_prepend_merge_intent(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia-Infinite-Scroll-Merge-Intent', 'prepend');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertSame('prepend', $headers->getMergeIntent());
    }

    #[Test]
    public function it_returns_version(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia-Version', 'abc123');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertSame('abc123', $headers->getVersion());
    }

    #[Test]
    public function it_returns_null_when_no_version(): void
    {
        $request = Request::create('/test');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertNull($headers->getVersion());
    }

    #[Test]
    public function it_returns_error_bag(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia-Error-Bag', 'createUser');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertSame('createUser', $headers->getErrorBag());
    }

    #[Test]
    public function it_returns_null_when_no_error_bag(): void
    {
        $request = Request::create('/test');

        $headers = InertiaHeaders::fromRequest($request);

        $this->assertNull($headers->getErrorBag());
    }
}
