<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

final class DiscoveryCache
{
    private readonly string $cacheDir;

    public function __construct(
        private readonly DiscoveryCacheStrategy $discoveryCacheStrategy,
    ) {
        $this->cacheDir = wp_get_upload_dir()['basedir'] . '/picowind/cache/discovery/';
        wp_mkdir_p($this->cacheDir);
    }

    public function isEnabled(): bool
    {
        return $this->discoveryCacheStrategy !== DiscoveryCacheStrategy::DISABLED;
    }

    /**
     * @param array<Discovery> $discoveries
     */
    public function store(DiscoveryLocation $discoveryLocation, array $discoveries): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $data = [];
        foreach ($discoveries as $discovery) {
            $data[$discovery::class] = $discovery->getItems()->all();
        }

        $filename = $this->getCacheFilename($discoveryLocation);
        file_put_contents($filename, serialize($data));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function restore(DiscoveryLocation $discoveryLocation): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $filename = $this->getCacheFilename($discoveryLocation);

        if (! file_exists($filename)) {
            return null;
        }

        // Check if cache is stale based on strategy
        if ($this->isCacheStale($filename, $discoveryLocation)) {
            unlink($filename);
            return null;
        }

        $content = file_get_contents($filename);
        if (false === $content) {
            return null;
        }

        $data = unserialize($content);
        return is_array($data) ? $data : null;
    }

    public function clear(): void
    {
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '*.cache');
            if ($files) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }
    }

    private function getCacheFilename(DiscoveryLocation $discoveryLocation): string
    {
        $hash = md5($discoveryLocation->namespace . $discoveryLocation->path);
        return $this->cacheDir . $hash . '.cache';
    }

    private function isCacheStale(string $cacheFile, DiscoveryLocation $discoveryLocation): bool
    {
        $cacheTime = filemtime($cacheFile);
        if (false === $cacheTime) {
            return true;
        }

        // Always consider stale in debug mode for partial strategy
        if ($this->discoveryCacheStrategy === DiscoveryCacheStrategy::PARTIAL && defined('WP_DEBUG') && WP_DEBUG) {
            return true;
        }

        // Check if any PHP files in the location are newer than cache
        if (is_dir($discoveryLocation->path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($discoveryLocation->path),
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php' && $file->getMTime() > $cacheTime) {
                    return true;
                }
            }
        }

        return false;
    }
}
