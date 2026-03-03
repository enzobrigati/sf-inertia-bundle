<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Prop;

use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\InertiaPage;
use EnzoBrigati\InertiaBundle\Prop\BasicProp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BasicPropTest extends TestCase
{
    private function createPage(): InertiaPage
    {
        return new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            'Test',
        );
    }

    #[Test]
    public function it_resolves_static_value(): void
    {
        $prop = new BasicProp('hello');
        $page = $this->createPage();

        $this->assertSame('hello', $prop->resolveValue($page));
    }

    #[Test]
    public function it_resolves_array_value(): void
    {
        $prop = new BasicProp(['a', 'b', 'c']);
        $page = $this->createPage();

        $this->assertEquals(['a', 'b', 'c'], $prop->resolveValue($page));
    }

    #[Test]
    public function it_resolves_callable_value(): void
    {
        $prop = new BasicProp(fn () => 'computed');
        $page = $this->createPage();

        $this->assertSame('computed', $prop->resolveValue($page));
    }

    #[Test]
    public function it_resolves_null_value(): void
    {
        $prop = new BasicProp(null);
        $page = $this->createPage();

        $this->assertNull($prop->resolveValue($page));
    }

    #[Test]
    public function it_implements_attach_to_page(): void
    {
        $prop = new BasicProp('value');
        $page = $this->createPage();

        // Should not throw
        $prop->attachToPage($page);
        $this->assertTrue(true);
    }
}
