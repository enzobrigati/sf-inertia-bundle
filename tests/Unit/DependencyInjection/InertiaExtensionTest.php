<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\DependencyInjection;

use EnzoBrigati\InertiaBundle\DependencyInjection\InertiaExtension;
use EnzoBrigati\InertiaBundle\Modal\ModalRedirectSubscriber;
use EnzoBrigati\InertiaBundle\Service\InertiaInterface;
use EnzoBrigati\InertiaBundle\Service\InertiaService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InertiaExtensionTest extends TestCase
{
    private function loadExtension(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder();

        // Register required service stubs
        $container->register('twig', \stdClass::class);
        $container->register('request_stack', \stdClass::class);
        $container->register('serializer', \stdClass::class);
        $container->register('translator', \stdClass::class);
        $container->register('security.csrf.token_manager', \stdClass::class);
        $container->register('http_kernel', \stdClass::class);

        $extension = new InertiaExtension();
        $extension->load([$config], $container);

        return $container;
    }

    #[Test]
    public function it_registers_inertia_service(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has('inertia.service'));
        $this->assertSame(
            InertiaService::class,
            $container->getDefinition('inertia.service')->getClass(),
        );
    }

    #[Test]
    public function it_aliases_interface_to_service(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has(InertiaInterface::class));
    }

    #[Test]
    public function it_registers_response_subscriber(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has('inertia.subscriber.response'));
    }

    #[Test]
    public function it_registers_version_subscriber(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has('inertia.subscriber.version'));
    }

    #[Test]
    public function it_registers_csrf_subscriber(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has('inertia.subscriber.csrf'));
    }

    #[Test]
    public function it_registers_exception_subscriber(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has('inertia.subscriber.exception'));
    }

    #[Test]
    public function it_registers_modal_redirect_subscriber(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has('inertia.modal.subscriber.redirect'));
        $this->assertSame(
            ModalRedirectSubscriber::class,
            $container->getDefinition('inertia.modal.subscriber.redirect')->getClass(),
        );
    }

    #[Test]
    public function it_sets_root_view_parameter(): void
    {
        $container = $this->loadExtension(['root_view' => 'custom.html.twig']);

        $this->assertSame('custom.html.twig', $container->getParameter('inertia.root_view'));
    }

    #[Test]
    public function it_sets_modal_redirect_parameter(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->getParameter('inertia.modal.redirect_to_base_url'));
    }

    #[Test]
    public function it_sets_modal_redirect_parameter_when_disabled(): void
    {
        $container = $this->loadExtension(['modal' => ['redirect_to_base_url' => false]]);

        $this->assertFalse($container->getParameter('inertia.modal.redirect_to_base_url'));
    }

    #[Test]
    public function it_sets_csrf_parameters(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->getParameter('inertia.csrf.enabled'));
        $this->assertSame('X-Inertia-CSRF-TOKEN', $container->getParameter('inertia.csrf.token_name'));
        $this->assertSame('X-XSRF-TOKEN', $container->getParameter('inertia.csrf.header_name'));
    }

    #[Test]
    public function it_registers_twig_extension(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has('inertia.twig'));
    }

    #[Test]
    public function it_registers_response_factories(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has('inertia.response_factory'));
        $this->assertTrue($container->has('inertia.response_factory.invalid_csrf'));
        $this->assertTrue($container->has('inertia.response_factory.validation_failed'));
    }

    #[Test]
    public function it_registers_default_component_resolver_and_locator(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->has('inertia.component_resolver.default'));
        $this->assertTrue($container->has('inertia.component_locator.default'));
    }
}
