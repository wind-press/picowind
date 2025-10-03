<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render;

use Latte\Engine;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Theme as UtilsTheme;

#[Service]
class Latte
{
    private readonly Engine $latte;

    public function __construct()
    {
        $cache_path = UtilsTheme::get_cache_path('latte');
        if (! file_exists($cache_path)) {
            wp_mkdir_p($cache_path);
        }

        $this->latte = new Engine();
        $this->latte->setTempDirectory($cache_path);

        // Auto-refresh in development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->latte->setAutoRefresh(true);
        }
    }

    /**
     * Render a Latte template.
     *
     * @param string|array $paths The path(s) to the Latte template file(s).
     * @param array  $context The context data to pass to the template.
     * @param bool   $print Whether to print the output directly or return it.
     * @return string|null
     */
    public function render_template($paths, array $context = [], bool $print = true)
    {
        $template_path = null;
        $template_dirs = UtilsTheme::get_template_directories();

        $resolve_template = function ($path) use ($template_dirs) {
            // If absolute path exists, use it
            if (file_exists($path)) {
                return $path;
            }

            // Try relative to each template directory
            foreach ($template_dirs as $dir) {
                $full_path = rtrim($dir, '/') . '/' . ltrim($path, '/');
                if (file_exists($full_path)) {
                    return $full_path;
                }
            }

            return null;
        };

        if (is_array($paths)) {
            foreach ($paths as $path) {
                $template_path = $resolve_template($path);
                if (null !== $template_path) {
                    break;
                }
            }
        } else {
            $template_path = $resolve_template($paths);
        }

        if (null === $template_path) {
            throw new \RuntimeException("Latte template not found: " . (is_array($paths) ? implode(', ', $paths) : $paths));
        }

        $output = $this->latte->renderToString($template_path, $context);

        if ($print) {
            echo $output;
        } else {
            return $output;
        }
        return null;
    }
}
