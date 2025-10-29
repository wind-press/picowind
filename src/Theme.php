<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

use Picowind\Core\Container\Container;
use Picowind\Core\Discovery\CommandDiscovery;
use Picowind\Core\Discovery\DiscoveryManager;
use Picowind\Core\Discovery\HookDiscovery;
use RuntimeException;

class Theme
{
    private static ?self $instance = null;

    private ?Container $container = null;

    private ?DiscoveryManager $discoveryManager = null;

    private bool $booted = false;

    public static function get_instance(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->boot();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->container = new Container();
        $this->boot_discover();

        if (! $this->container instanceof Container) {
            throw new RuntimeException('Container initialization failed');
        }

        $this->container->compile();
        $this->register_discovered_hooks();
        $this->register_discovered_commands();
        $this->booted = true;

        do_action('a!picowind/core/theme:booted', $this);
    }

    public function container(): Container
    {
        if (! $this->container instanceof Container) {
            throw new RuntimeException('Theme not booted yet. Call boot() first.');
        }

        return $this->container;
    }

    private function boot_discover(): void
    {
        if (! $this->container instanceof Container) {
            throw new RuntimeException('Container not initialized');
        }

        $this->discoveryManager = new DiscoveryManager($this->container);
        $this->discoveryManager->discover();
    }

    private function register_discovered_hooks(): void
    {
        if (! $this->discoveryManager instanceof DiscoveryManager) {
            return;
        }

        foreach ($this->discoveryManager->getDiscoveries() as $discovery) {
            if ($discovery instanceof HookDiscovery) {
                $discovery->registerHooks();
            }
        }
    }

    private function register_discovered_commands(): void
    {
        if (! $this->discoveryManager instanceof DiscoveryManager) {
            return;
        }

        foreach ($this->discoveryManager->getDiscoveries() as $discovery) {
            if ($discovery instanceof CommandDiscovery) {
                $discovery->registerCommands();
            }
        }
    }
}
