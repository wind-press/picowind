<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

use Picowind\Core\Container\Container;
use Throwable;

final class DiscoveryManager
{
    /** @var array<Discovery> */
    private array $discoveries = [];

    /** @var array<DiscoveryLocation> */
    private array $discoveryLocations = [];

    private ?DiscoveryCache $discoveryCache = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    public function discover(): void
    {
        $this->initializeDiscoveryLocations();
        $this->initializeDiscoveries();
        $this->runDiscovery();
        $this->applyDiscoveries();
    }

    /**
     * @return array<Discovery>
     */
    public function getDiscoveries(): array
    {
        return $this->discoveries;
    }

    public function clear_cache(): void
    {
        if ($this->discoveryCache instanceof DiscoveryCache) {
            $this->discoveryCache->clear();
        }
    }

    private function initializeDiscoveryLocations(): void
    {
        $composerFile = get_template_directory() . '/composer.json';
        $composerContent = file_get_contents($composerFile);

        if (false === $composerContent) {
            return;
        }

        $composerData = json_decode($composerContent, true);

        if (! is_array($composerData)) {
            return;
        }

        if (isset($composerData['autoload']) && is_array($composerData['autoload']) && isset($composerData['autoload']['psr-4']) && is_array($composerData['autoload']['psr-4'])) {
            foreach ($composerData['autoload']['psr-4'] as $namespace => $path) {
                if (is_string($namespace) && is_string($path) && str_starts_with($namespace, 'Picowind\\')) {
                    $fullPath = get_template_directory() . '/' . $path;
                    $this->discoveryLocations[] = new DiscoveryLocation(
                        $namespace,
                        $fullPath,
                    );
                }
            }
        }

        if (
            defined('WP_DEBUG') && WP_DEBUG && (
                isset($composerData['autoload-dev'])
                && is_array($composerData['autoload-dev'])
                && isset($composerData['autoload-dev']['psr-4'])
                && is_array($composerData['autoload-dev']['psr-4'])
            )
        ) {
            foreach ($composerData['autoload-dev']['psr-4'] as $namespace => $path) {
                if (is_string($namespace) && is_string($path) && str_starts_with($namespace, 'Picowind\\Tests\\')) {
                    $fullPath = get_template_directory() . '/' . $path;
                    $this->discoveryLocations[] = new DiscoveryLocation(
                        $namespace,
                        $fullPath,
                    );
                }
            }
        }
    }

    private function initializeDiscoveries(): void
    {
        $this->discoveryCache = new DiscoveryCache($this->determineCacheStrategy());

        $this->discoveries = [
            new ServiceDiscovery($this->container),
            new HookDiscovery($this->container),
            new CommandDiscovery($this->container),
        ];
    }

    private function determineCacheStrategy(): DiscoveryCacheStrategy
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return DiscoveryCacheStrategy::PARTIAL;
        }

        return DiscoveryCacheStrategy::FULL;
    }

    private function runDiscovery(): void
    {
        foreach ($this->discoveryLocations as $discoveryLocation) {
            if ($this->isLocationCached($discoveryLocation)) {
                $this->restoreFromCache($discoveryLocation);
                continue;
            }

            $this->scanLocation($discoveryLocation);
            $this->cacheLocation($discoveryLocation);
        }
    }

    private function isLocationCached(DiscoveryLocation $discoveryLocation): bool
    {
        if (! $this->discoveryCache || ! $this->discoveryCache->isEnabled()) {
            return false;
        }

        $cached = $this->discoveryCache->restore($discoveryLocation);
        return null !== $cached;
    }

    private function restoreFromCache(DiscoveryLocation $discoveryLocation): void
    {
        if (null === $this->discoveryCache) {
            return;
        }

        $cached = $this->discoveryCache->restore($discoveryLocation);
        if (null === $cached) {
            return;
        }

        foreach ($this->discoveries as $discovery) {
            $items = $cached[$discovery::class] ?? [];
            if (! empty($items)) {
                $discoveryItems = $discovery->getItems();
                foreach ($items as $item) {
                    $discoveryItems->add($discoveryLocation, $item);
                }

                $discovery->setItems($discoveryItems);
            }
        }
    }

    private function scanLocation(DiscoveryLocation $discoveryLocation): void
    {
        $path = $discoveryLocation->path;

        if (! is_dir($path)) {
            return;
        }

        $classmap = $this->getComposerClassmap();

        $classes = [];
        foreach ($classmap as $className => $filePath) {
            if (str_starts_with($className, rtrim($discoveryLocation->namespace, '\\'))) {
                $classes[$className] = $filePath;
            }
        }

        foreach (array_keys($classes) as $className) {
            if (! class_exists($className)) {
                continue;
            }

            try {
                $classReflector = new ClassReflector($className);

                foreach ($this->discoveries as $discovery) {
                    $discovery->discover($discoveryLocation, $classReflector);
                }
            } catch (Throwable $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Picowind Discovery Error for ' . $className . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @return array<class-string, string>
     */
    private function getComposerClassmap(): array
    {
        $classmapFile = get_template_directory() . '/vendor/composer/autoload_classmap.php';

        if (file_exists($classmapFile)) {
            $classmap = include $classmapFile;
            if (is_array($classmap)) {
                /** @var array<class-string, string> $classmap */
                return $classmap;
            }
        }

        return [];
    }

    private function cacheLocation(DiscoveryLocation $discoveryLocation): void
    {
        if (! $this->discoveryCache || ! $this->discoveryCache->isEnabled()) {
            return;
        }

        $this->discoveryCache->store($discoveryLocation, $this->discoveries);
    }

    private function applyDiscoveries(): void
    {
        foreach ($this->discoveries as $discovery) {
            $discovery->apply();
        }
    }
}
