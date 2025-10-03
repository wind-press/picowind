<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render\Latte;

use Latte\Loader;
use RuntimeException;

/**
 * Custom Latte loader that supports multiple template directories with fallback
 */
class MultiDirectoryLoader implements Loader
{
    private array $directories;

    public function __construct(array $directories)
    {
        $this->directories = $directories;
    }

    public function getContent(string $fileName): string
    {
        $path = $this->findTemplate($fileName);

        if (null === $path) {
            throw new RuntimeException("Template '{$fileName}' not found in any directory.");
        }

        return file_get_contents($path);
    }

    public function isExpired(string $fileName, int $time): bool
    {
        $path = $this->findTemplate($fileName);

        if (null === $path) {
            return true;
        }

        return @filemtime($path) > $time;
    }

    public function getReferredName(string $fileName, string $referringFileName): string
    {
        // If it's an absolute path, return as-is
        if ($fileName[0] === '/' || $fileName[0] === '\\') {
            return $fileName;
        }

        // Otherwise, resolve relative to the referring file's directory
        $referringDir = dirname($referringFileName);
        return $referringDir . '/' . $fileName;
    }

    public function getUniqueId(string $fileName): string
    {
        $path = $this->findTemplate($fileName);
        return $path ?? $fileName;
    }

    private function findTemplate(string $fileName): ?string
    {
        // If it's already an absolute path and exists, use it
        if (($fileName[0] === '/' || $fileName[0] === '\\') && file_exists($fileName)) {
            return $fileName;
        }

        // Try to find in each directory
        foreach ($this->directories as $dir) {
            $path = rtrim($dir, '/\\') . '/' . ltrim($fileName, '/\\');
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
