<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

use Exception;
use Picowind\Core\Container\Container;
use Picowind\Core\Discovery\CommandDiscovery;
use Picowind\Core\Discovery\DiscoveryManager;
use Picowind\Core\Discovery\HookDiscovery;
use Picowind\Supports\Timber as SupportsTimber;
use RuntimeException;
use Timber\Site;

class Theme extends Site
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
        parent::__construct();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->container = new Container();
        $this->discover_components();

        if (! $this->container instanceof Container) {
            throw new RuntimeException('Container initialization failed');
        }

        $this->container->compile();
        $this->register_discovered_hooks();
        $this->register_discovered_commands();
        $this->setup_timber();
        $this->booted = true;

        do_action('picowind/core:theme.booted', $this);
    }

    public function container(): Container
    {
        if (! $this->container instanceof Container) {
            throw new RuntimeException('Theme not booted yet. Call boot() first.');
        }

        return $this->container;
    }

    private function discover_components(): void
    {
        if (! $this->container instanceof Container) {
            throw new RuntimeException('Container not initialized');
        }

        $this->discoveryManager = new DiscoveryManager($this->container);
        $this->discoveryManager->discover();
    }

    private function register_discovered_hooks(): void
    {
        if (! $this->discoveryManager instanceof \Picowind\Core\Discovery\DiscoveryManager) {
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
        if (! $this->discoveryManager instanceof \Picowind\Core\Discovery\DiscoveryManager) {
            return;
        }

        foreach ($this->discoveryManager->getDiscoveries() as $discovery) {
            if ($discovery instanceof CommandDiscovery) {
                $discovery->registerCommands();
            }
        }
    }

    private function setup_timber(): void
    {
        // Get the Timber service from container and set the site
        try {
            $timberService = $this->container->get(SupportsTimber::class);
            $timberService->setSite($this);
        } catch (Exception $exception) {
            // Fallback if Timber service not found
            error_log('Failed to get Timber service from container: ' . $exception->getMessage());
        }
    }
}
