<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Theme as UtilsTheme;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Iconify as UXIconify;
use Symfony\UX\Icons\IconRegistryInterface;
use Symfony\UX\Icons\Registry\CacheIconRegistry;
use Symfony\UX\Icons\Registry\IconifyOnDemandRegistry;

/**
 * Iconify service for rendering SVG icons from Iconify API.
 *
 * @example
 * // Basic usage
 * $iconify->get_icon('prefix:name');
 *
 * // With attributes
 * $iconify->get_icon('prefix:name', ['class' => 'icon-large', 'width' => '32', 'height' => '32']);
 */
#[Service]
class Iconify
{
    private readonly IconRegistryInterface $registry;

    public function __construct()
    {
        $cache_path = UtilsTheme::get_cache_path();
        if (! file_exists($cache_path)) {
            wp_mkdir_p($cache_path);
        }

        // Initialize Symfony cache adapter
        $cache = new FilesystemAdapter('iconify', 0, $cache_path);

        // Initialize Symfony UX Iconify (for fetching icon sets metadata)
        $iconify = new UXIconify($cache);

        // Create the IconifyOnDemandRegistry and wrap it with CacheIconRegistry
        // This ensures both icon sets metadata AND individual icon SVG data are cached
        $onDemandRegistry = new IconifyOnDemandRegistry($iconify);
        $this->registry = new CacheIconRegistry($onDemandRegistry, $cache);
    }

    /**
     * Get an icon from Iconify and return as HTML string.
     *
     * @param string $iconName Icon name in format "prefix:icon-name" (e.g., "mdi:home", "bi:github")
     * @param array $attributes Optional HTML attributes to add to the SVG element
     * @return null|string the SVG HTML if exists, or null if couldn't found
     */
    public function get_icon(string $iconName, array $attributes = [])
    {
        // Validate icon name format
        if (! str_contains($iconName, ':')) {
            return null;
        }

        [$prefix, $name] = explode(':', $iconName, 2);

        try {
            // Fetch icon from registry (with caching)
            $icon = $this->registry->get($iconName);

            // Add custom attributes if provided
            if (! empty($attributes)) {
                $icon = $icon->withAttributes($attributes);
            }

            return $icon->toHtml();
        } catch (IconNotFoundException $e) {
            // Log error in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Iconify: Icon not found - {$iconName}");
            }
            return null;
        } catch (\Exception $e) {
            // Log other errors in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Iconify error: {$e->getMessage()}");
            }
            return null;
        }
    }
}
