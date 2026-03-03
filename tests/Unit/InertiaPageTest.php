<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit;

use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\InertiaPage;
use EnzoBrigati\InertiaBundle\Prop\AlwaysProp;
use EnzoBrigati\InertiaBundle\Prop\BasicProp;
use EnzoBrigati\InertiaBundle\Prop\CallbackProp;
use EnzoBrigati\InertiaBundle\Prop\DeferProp;
use EnzoBrigati\InertiaBundle\Prop\MergeProp;
use EnzoBrigati\InertiaBundle\Prop\OptionalProp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class InertiaPageTest extends TestCase
{
    private function createHeaders(array $headers = []): InertiaHeaders
    {
        $request = Request::create('/test');
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return InertiaHeaders::fromRequest($request);
    }

    #[Test]
    public function it_can_be_created_with_basic_props(): void
    {
        $page = new InertiaPage(
            $this->createHeaders(),
            'Users/Index',
            ['users' => [1, 2, 3]],
            '/users',
            '1.0',
        );

        $this->assertSame('Users/Index', $page->component);
        $this->assertSame('/users', $page->url);
        $this->assertSame('1.0', $page->version);
        $this->assertFalse($page->clearHistory);
        $this->assertFalse($page->encryptHistory);
    }

    #[Test]
    public function it_wraps_raw_values_in_basic_props(): void
    {
        $page = new InertiaPage(
            $this->createHeaders(),
            'Test',
            ['key' => 'value'],
        );

        $props = $page->getProps();
        $this->assertInstanceOf(BasicProp::class, $props->get('key'));
    }

    #[Test]
    public function it_preserves_prop_interface_instances(): void
    {
        $alwaysProp = new AlwaysProp('always');
        $page = new InertiaPage(
            $this->createHeaders(),
            'Test',
            ['key' => $alwaysProp],
        );

        $this->assertSame($alwaysProp, $page->getProps()->get('key'));
    }

    #[Test]
    public function it_serializes_to_json(): void
    {
        $page = new InertiaPage(
            $this->createHeaders(),
            'Users/Index',
            ['users' => [1, 2, 3]],
            '/users',
            '1.0',
        );

        $json = $page->jsonSerialize();

        $this->assertSame('Users/Index', $json['component']);
        $this->assertSame('/users', $json['url']);
        $this->assertSame('1.0', $json['version']);
        $this->assertFalse($json['clearHistory']);
        $this->assertFalse($json['encryptHistory']);
        $this->assertEquals([1, 2, 3], $json['props']['users']);
    }

    #[Test]
    public function it_resolves_props_values(): void
    {
        $page = new InertiaPage(
            $this->createHeaders(),
            'Test',
            [
                'static' => 'static data',
                'callable' => new CallbackProp(fn () => 'computed'),
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertSame('static data', $resolved['static']);
        $this->assertSame('computed', $resolved['callable']);
    }

    #[Test]
    public function it_excludes_optional_props_from_full_requests(): void
    {
        $page = new InertiaPage(
            $this->createHeaders(),
            'Test',
            [
                'users' => 'all users',
                'lazy' => new OptionalProp(fn () => 'lazy data'),
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertArrayHasKey('users', $resolved);
        $this->assertArrayNotHasKey('lazy', $resolved);
    }

    #[Test]
    public function it_includes_optional_props_in_partial_requests(): void
    {
        $headers = $this->createHeaders([
            'X-Inertia-Partial-Component' => 'Test',
            'X-Inertia-Partial-Data' => 'lazy',
        ]);

        $page = new InertiaPage(
            $headers,
            'Test',
            [
                'users' => 'all users',
                'lazy' => new OptionalProp(fn () => 'lazy data'),
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertArrayHasKey('lazy', $resolved);
        $this->assertSame('lazy data', $resolved['lazy']);
    }

    #[Test]
    public function it_always_includes_always_props(): void
    {
        $headers = $this->createHeaders([
            'X-Inertia-Partial-Component' => 'Test',
            'X-Inertia-Partial-Data' => 'users',
        ]);

        $page = new InertiaPage(
            $headers,
            'Test',
            [
                'users' => 'all users',
                'flash' => new AlwaysProp('flash message'),
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertArrayHasKey('users', $resolved);
        $this->assertArrayHasKey('flash', $resolved);
        $this->assertSame('flash message', $resolved['flash']);
    }

    #[Test]
    public function it_respects_partial_only_filter(): void
    {
        $headers = $this->createHeaders([
            'X-Inertia-Partial-Component' => 'Test',
            'X-Inertia-Partial-Data' => 'users',
        ]);

        $page = new InertiaPage(
            $headers,
            'Test',
            [
                'users' => 'users data',
                'roles' => 'roles data',
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertArrayHasKey('users', $resolved);
        $this->assertArrayNotHasKey('roles', $resolved);
    }

    #[Test]
    public function it_respects_partial_except_filter(): void
    {
        $headers = $this->createHeaders([
            'X-Inertia-Partial-Component' => 'Test',
            'X-Inertia-Partial-Except' => 'roles',
        ]);

        $page = new InertiaPage(
            $headers,
            'Test',
            [
                'users' => 'users data',
                'roles' => 'roles data',
            ],
        );

        $resolved = $page->resolveProps();

        $this->assertArrayHasKey('users', $resolved);
        $this->assertArrayNotHasKey('roles', $resolved);
    }

    #[Test]
    public function it_resolves_deferred_props(): void
    {
        $page = new InertiaPage(
            $this->createHeaders(),
            'Test',
            [
                'users' => 'users data',
                'stats' => new DeferProp(fn () => 'deferred stats', 'stats-group'),
            ],
        );

        $deferred = $page->resolveDeferredProps();

        $this->assertArrayHasKey('deferredProps', $deferred);
        $this->assertArrayHasKey('stats-group', $deferred['deferredProps']);
        $this->assertContains('stats', $deferred['deferredProps']['stats-group']);
    }

    #[Test]
    public function it_does_not_include_deferred_props_during_partial_request(): void
    {
        $headers = $this->createHeaders([
            'X-Inertia-Partial-Component' => 'Test',
            'X-Inertia-Partial-Data' => 'stats',
        ]);

        $page = new InertiaPage(
            $headers,
            'Test',
            [
                'stats' => new DeferProp(fn () => 'deferred stats'),
            ],
        );

        $deferred = $page->resolveDeferredProps();

        $this->assertEmpty($deferred);
    }

    #[Test]
    public function it_resolves_merge_props(): void
    {
        $page = new InertiaPage(
            $this->createHeaders(),
            'Test',
            [
                'users' => new MergeProp([1, 2, 3]),
            ],
        );

        $merge = $page->resolveMergeProps();

        $this->assertArrayHasKey('mergeProps', $merge);
        $this->assertContains('users', $merge['mergeProps']);
    }

    #[Test]
    public function it_returns_cloned_props_collection(): void
    {
        $page = new InertiaPage(
            $this->createHeaders(),
            'Test',
            ['key' => 'value'],
        );

        $props1 = $page->getProps();
        $props2 = $page->getProps();

        $this->assertNotSame($props1, $props2);
        $this->assertEquals($props1, $props2);
    }
}
