<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Tests\Unit\DependencyInjection;

use EnzoBrigati\InertiaBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private function processConfiguration(array $configs = []): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    #[Test]
    public function it_has_default_root_view(): void
    {
        $config = $this->processConfiguration();

        $this->assertSame('base.html.twig', $config['root_view']);
    }

    #[Test]
    public function it_can_override_root_view(): void
    {
        $config = $this->processConfiguration([
            ['root_view' => 'app.html.twig'],
        ]);

        $this->assertSame('app.html.twig', $config['root_view']);
    }

    #[Test]
    public function it_has_default_component_resolver(): void
    {
        $config = $this->processConfiguration();

        $this->assertSame('inertia.component_resolver.default', $config['component_resolver']);
    }

    #[Test]
    public function it_has_default_component_locator(): void
    {
        $config = $this->processConfiguration();

        $this->assertSame('inertia.component_locator.default', $config['component_locator']);
    }

    #[Test]
    public function it_has_exception_enabled_by_default(): void
    {
        $config = $this->processConfiguration();

        $this->assertTrue($config['exception']['enabled']);
    }

    #[Test]
    public function it_can_disable_exception(): void
    {
        $config = $this->processConfiguration([
            ['exception' => ['enabled' => false]],
        ]);

        $this->assertFalse($config['exception']['enabled']);
    }

    #[Test]
    public function it_has_csrf_enabled_by_default(): void
    {
        $config = $this->processConfiguration();

        $this->assertTrue($config['csrf']['enabled']);
    }

    #[Test]
    public function it_has_default_csrf_config(): void
    {
        $config = $this->processConfiguration();

        $this->assertSame('X-Inertia-CSRF-TOKEN', $config['csrf']['token_name']);
        $this->assertSame('X-XSRF-TOKEN', $config['csrf']['header_name']);
        $this->assertSame('XSRF-TOKEN', $config['csrf']['cookie']['name']);
        $this->assertSame(0, $config['csrf']['cookie']['expire']);
        $this->assertSame('/', $config['csrf']['cookie']['path']);
        $this->assertNull($config['csrf']['cookie']['domain']);
        $this->assertFalse($config['csrf']['cookie']['secure']);
        $this->assertFalse($config['csrf']['cookie']['raw']);
        $this->assertSame('lax', $config['csrf']['cookie']['samesite']);
    }

    #[Test]
    public function it_has_modal_config_with_redirect_to_base_url_enabled_by_default(): void
    {
        $config = $this->processConfiguration();

        $this->assertArrayHasKey('modal', $config);
        $this->assertTrue($config['modal']['redirect_to_base_url']);
    }

    #[Test]
    public function it_can_disable_modal_redirect_to_base_url(): void
    {
        $config = $this->processConfiguration([
            ['modal' => ['redirect_to_base_url' => false]],
        ]);

        $this->assertFalse($config['modal']['redirect_to_base_url']);
    }

    #[Test]
    public function it_can_customize_csrf_cookie(): void
    {
        $config = $this->processConfiguration([
            ['csrf' => ['cookie' => [
                'name' => 'MY-CSRF',
                'secure' => true,
                'samesite' => 'strict',
            ]]],
        ]);

        $this->assertSame('MY-CSRF', $config['csrf']['cookie']['name']);
        $this->assertTrue($config['csrf']['cookie']['secure']);
        $this->assertSame('strict', $config['csrf']['cookie']['samesite']);
    }
}
