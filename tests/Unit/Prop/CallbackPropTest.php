<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\Prop;

use EnzoBrigati\InertiaBundle\InertiaHeaders;
use EnzoBrigati\InertiaBundle\InertiaPage;
use EnzoBrigati\InertiaBundle\Prop\CallbackProp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CallbackPropTest extends TestCase
{
    private function createPage(): InertiaPage
    {
        return new InertiaPage(
            InertiaHeaders::fromRequest(Request::create('/test')),
            'Test',
        );
    }

    #[Test]
    public function it_resolves_callback_value(): void
    {
        $prop = new CallbackProp(fn () => 'from callback');
        $page = $this->createPage();

        $this->assertSame('from callback', $prop->resolveValue($page));
    }

    #[Test]
    public function it_evaluates_callback_lazily(): void
    {
        $called = false;
        $prop = new CallbackProp(function () use (&$called) {
            $called = true;

            return 'result';
        });

        $this->assertFalse($called);

        $page = $this->createPage();
        $prop->resolveValue($page);

        $this->assertTrue($called);
    }
}
