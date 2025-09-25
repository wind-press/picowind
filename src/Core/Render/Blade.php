<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render;

use Jenssegers\Blade\Blade as BladeBlade;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Theme as UtilsTheme;

#[Service]
class Blade
{
    private readonly ?BladeBlade $bladeBlade;

    public function __construct()
    {
        $cache_path = UtilsTheme::get_cache_path('blade');
        if (! file_exists($cache_path)) {
            wp_mkdir_p($cache_path);
        }

        $this->bladeBlade = new BladeBlade(UtilsTheme::get_template_directories(), $cache_path);
    }

    /**
     * Render a Blade template.
     *
     * @param string|array $paths The path(s) to the Blade template file(s).
     * @param array  $context The context data to pass to the template.
     * @param bool   $print Whether to print the output directly or return it.
     * @return string|null
     */
    public function render_template($paths, array $context = [], bool $print = true)
    {
        $view_name = null;
        $template_dirs = UtilsTheme::get_template_directories();
        $resolve_view_name = function ($path) use ($template_dirs) {
            foreach ($template_dirs as $template_dir) {
                if (str_starts_with($path, $template_dir)) {
                    $relative_path = substr($path, strlen($template_dir) + 1);
                    return str_replace(['/', '.blade.php'], ['.', ''], $relative_path);
                }
            }

            // If not absolute, treat as relative to template_dirs
            $relative_path = substr($path, -10) === '.blade.php' ? $path : $path . '.blade.php';

            $relative_path = ltrim($relative_path, '/');
            return str_replace(['/', '.blade.php'], ['.', ''], $relative_path);
        };

        if (is_array($paths)) {
            foreach ($paths as $path) {
                $view_name = $resolve_view_name($path);
                if ('' !== $view_name && '0' !== $view_name) {
                    break;
                }
            }
        } else {
            $view_name = $resolve_view_name($paths);
        }

        if (null === $view_name) {
            // Fallback: use the basename without extension
            $view_name = pathinfo($paths, PATHINFO_FILENAME);
        }

        $output = $this->bladeBlade->make($view_name, $context)->render();

        if ($print) {
            echo $output;
        } else {
            return $output;
        }
        return null;
    }
}
