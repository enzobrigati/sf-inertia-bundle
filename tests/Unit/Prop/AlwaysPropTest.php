<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Prop;

use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\InertiaPage;
use EnzoBrigati\InertiaBundle\Prop\AlwaysProp;
use EnzoBrigati\InertiaBundle\Prop\BasicProp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AlwaysPropTest extends TestCase
{
    #[Test]
    public function it_extends_basic_prop(): void
    {
        $prop = new AlwaysProp('value');

        $this->assertInstanceOf(BasicProp::class, $prop);
    }

    #[Test]
    public function it_resolves_value(): void
    {
        $prop = new AlwaysProp('always here');
        $page = new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            'Test',
        );

        $this->assertSame('always here', $prop->resolveValue($page));
    }

    #[Test]
    public function it_is_included_in_partial_requests(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia-Partial-Component', 'Test');
        $request->headers->set('X-Inertia-Partial-Data', 'other');

        $page = new InertiaPage(
            InertiaHeaders::fromRequest($request),
            'Test',
            [
                'other' => 'data',
                'always' => new AlwaysProp('always here'),
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertArrayHasKey('always', $resolved);
        $this->assertSame('always here', $resolved['always']);
    }
}
