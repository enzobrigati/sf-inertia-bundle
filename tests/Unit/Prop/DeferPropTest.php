<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Prop;

use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\InertiaPage;
use EnzoBrigati\InertiaBundle\Prop\DeferProp;
use EnzoBrigati\InertiaBundle\Prop\MergeablePropInterface;
use EnzoBrigati\InertiaBundle\Prop\PartialLoadPropInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class DeferPropTest extends TestCase
{
    #[Test]
    public function it_implements_required_interfaces(): void
    {
        $prop = new DeferProp(fn () => 'data');

        $this->assertInstanceOf(MergeablePropInterface::class, $prop);
        $this->assertInstanceOf(PartialLoadPropInterface::class, $prop);
    }

    #[Test]
    public function it_has_default_group(): void
    {
        $prop = new DeferProp(fn () => 'data');

        $this->assertSame('default', $prop->group);
    }

    #[Test]
    public function it_accepts_custom_group(): void
    {
        $prop = new DeferProp(fn () => 'data', 'custom-group');

        $this->assertSame('custom-group', $prop->group);
    }

    #[Test]
    public function it_is_excluded_from_full_requests(): void
    {
        $page = new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            'Test',
            [
                'basic' => 'data',
                'deferred' => new DeferProp(fn () => 'deferred data'),
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertArrayHasKey('basic', $resolved);
        $this->assertArrayNotHasKey('deferred', $resolved);
    }

    #[Test]
    public function it_appears_in_deferred_props_metadata(): void
    {
        $page = new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            'Test',
            [
                'stats' => new DeferProp(fn () => 'stats data', 'analytics'),
            ],
        );

        $deferred = $page->resolveDeferredProps();

        $this->assertArrayHasKey('deferredProps', $deferred);
        $this->assertArrayHasKey('analytics', $deferred['deferredProps']);
        $this->assertContains('stats', $deferred['deferredProps']['analytics']);
    }

    #[Test]
    public function it_groups_multiple_deferred_props(): void
    {
        $page = new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            'Test',
            [
                'stats' => new DeferProp(fn () => 'stats', 'group1'),
                'chart' => new DeferProp(fn () => 'chart', 'group1'),
                'logs' => new DeferProp(fn () => 'logs', 'group2'),
            ],
        );

        $deferred = $page->resolveDeferredProps();

        $this->assertCount(2, $deferred['deferredProps']['group1']);
        $this->assertCount(1, $deferred['deferredProps']['group2']);
    }
}
