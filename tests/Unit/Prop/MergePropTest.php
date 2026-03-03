<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Prop;

use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\InertiaPage;
use EnzoBrigati\InertiaBundle\Prop\MergeablePropInterface;
use EnzoBrigati\InertiaBundle\Prop\MergeProp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class MergePropTest extends TestCase
{
    #[Test]
    public function it_implements_mergeable_interface(): void
    {
        $prop = new MergeProp([1, 2, 3]);

        $this->assertInstanceOf(MergeablePropInterface::class, $prop);
    }

    #[Test]
    public function it_should_merge_by_default(): void
    {
        $prop = new MergeProp([1, 2, 3]);

        $this->assertTrue($prop->shouldMerge());
    }

    #[Test]
    public function it_resolves_value(): void
    {
        $prop = new MergeProp([1, 2, 3]);
        $page = new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            'Test',
        );

        $this->assertEquals([1, 2, 3], $prop->resolveValue($page));
    }

    #[Test]
    public function it_appears_in_merge_props_metadata(): void
    {
        $page = new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            'Test',
            [
                'items' => new MergeProp([1, 2, 3]),
            ],
        );

        $merge = $page->resolveMergeProps();

        $this->assertArrayHasKey('mergeProps', $merge);
        $this->assertContains('items', $merge['mergeProps']);
    }

    #[Test]
    public function it_is_not_deep_merge_by_default(): void
    {
        $prop = new MergeProp(['key' => 'value']);

        $this->assertFalse($prop->shouldDeepMerge());
    }
}
