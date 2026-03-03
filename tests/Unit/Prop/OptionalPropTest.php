<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Prop;

use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\InertiaPage;
use EnzoBrigati\InertiaBundle\Prop\OptionalProp;
use EnzoBrigati\InertiaBundle\Prop\PartialLoadPropInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class OptionalPropTest extends TestCase
{
    #[Test]
    public function it_implements_partial_load_interface(): void
    {
        $prop = new OptionalProp(fn () => 'data');

        $this->assertInstanceOf(PartialLoadPropInterface::class, $prop);
    }

    #[Test]
    public function it_is_excluded_from_full_requests(): void
    {
        $page = new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            'Test',
            [
                'basic' => 'some data',
                'optional' => new OptionalProp(fn () => 'optional data'),
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertArrayHasKey('basic', $resolved);
        $this->assertArrayNotHasKey('optional', $resolved);
    }

    #[Test]
    public function it_is_included_when_explicitly_requested_in_partial(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia-Partial-Component', 'Test');
        $request->headers->set('X-Inertia-Partial-Data', 'optional');

        $page = new InertiaPage(
            InertiaHeaders::fromRequest($request),
            'Test',
            [
                'basic' => 'some data',
                'optional' => new OptionalProp(fn () => 'optional data'),
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertArrayHasKey('optional', $resolved);
        $this->assertSame('optional data', $resolved['optional']);
    }
}
