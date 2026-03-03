<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit;

use EnzoBrigati\InertiaBundle\InertiaFlash;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class InertiaFlashTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_request_with_session(): void
    {
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $flash = InertiaFlash::fromRequest($request);

        $this->assertInstanceOf(InertiaFlash::class, $flash);
    }

    #[Test]
    public function it_can_be_created_from_request_without_session(): void
    {
        $request = Request::create('/test');

        $flash = InertiaFlash::fromRequest($request);

        $this->assertInstanceOf(InertiaFlash::class, $flash);
        $this->assertEquals([], $flash->getErrors());
    }

    #[Test]
    public function it_returns_empty_errors_when_no_flash(): void
    {
        $flash = new InertiaFlash(null);

        $this->assertEquals([], $flash->getErrors());
    }

    #[Test]
    public function it_can_set_and_get_errors(): void
    {
        $flashBag = new FlashBag();
        $flash = new InertiaFlash($flashBag);

        $errors = ['name' => 'Name is required', 'email' => 'Email is invalid'];
        $flash->setErrors($errors);

        $result = $flash->getErrors();
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function it_can_set_errors_with_error_bag(): void
    {
        $flashBag = new FlashBag();
        $flash = new InertiaFlash($flashBag);

        $errors = ['name' => 'Name is required'];
        $flash->setErrors($errors, 'createUser');

        $result = $flash->getErrors();
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('createUser', $result);
        $this->assertEquals($errors, $result['createUser']);
    }

    #[Test]
    public function it_returns_empty_errors_after_getting_them(): void
    {
        $flashBag = new FlashBag();
        $flash = new InertiaFlash($flashBag);

        $flash->setErrors(['name' => 'Required']);

        // First call returns errors
        $this->assertNotEmpty($flash->getErrors());

        // Flash is consumed, second call returns empty
        $this->assertEquals([], $flash->getErrors());
    }
}
