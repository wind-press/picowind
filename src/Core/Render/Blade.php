<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Core\Render;

use Exception;
use Jenssegers\Blade\Blade as BladeBlade;
use Picowind\Core\Template as CoreTemplate;

class Blade
{
    private ?BladeBlade $blade;

    /**
     * Stores the instance, implementing a Singleton pattern.
     */
    private static self $instance;

    /**
     * Singletons should not be cloneable.
     */
    private function __clone()
    {
    }

    /**
     * Singletons should not be restorable from strings.
     *
     * @throws Exception Cannot unserialize a singleton.
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a singleton.');
    }

    /**
     * This is the static method that controls the access to the singleton
     * instance. On the first run, it creates a singleton object and places it
     * into the static property. On subsequent runs, it returns the client existing
     * object stored in the static property.
     */
    public static function get_instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * The Singleton's constructor should always be private to prevent direct
     * construction calls with the `new` operator.
     */
    private function __construct()
    {
        if (! file_exists(CoreTemplate::get_instance()->blade_cache_path)) {
            wp_mkdir_p(CoreTemplate::get_instance()->blade_cache_path);
        }

        $this->blade = new BladeBlade(CoreTemplate::get_instance()->template_dirs, CoreTemplate::get_instance()->blade_cache_path);
    }

    /**
     * Render a Blade template.
     *
     * @param string|array $paths The path(s) to the Blade template file(s).
     * @param array  $context The context data to pass to the template.
     * @param bool   $print Whether to print the output directly or return it.
     * @return void|string
     */
    public function render_template($paths, array $context = [], bool $print = true)
    {
        $view_name = null;
        $template_dirs = CoreTemplate::get_instance()->template_dirs;
        $resolve_view_name = function ($path) use ($template_dirs) {
            foreach ($template_dirs as $dir) {
                if (strpos($path, $dir) === 0) {
                    $relative_path = substr($path, strlen($dir) + 1);
                    return str_replace(['/', '.blade.php'], ['.', ''], $relative_path);
                }
            }
            // If not absolute, treat as relative to template_dirs
            if (substr($path, -10) === '.blade.php') {
                $relative_path = $path;
            } else {
                $relative_path = $path . '.blade.php';
            }
            $relative_path = ltrim($relative_path, '/');
            return str_replace(['/', '.blade.php'], ['.', ''], $relative_path);
        };

        if (is_array($paths)) {
            foreach ($paths as $single_path) {
                $view_name = $resolve_view_name($single_path);
                if ($view_name) {
                    break;
                }
            }
        } else {
            $view_name = $resolve_view_name($paths);
        }

        if ($view_name === null) {
            // Fallback: use the basename without extension
            $view_name = pathinfo($paths, PATHINFO_FILENAME);
        }

        $output = $this->blade->make($view_name, $context)->render();

        if ($print) {
            echo $output;
        } else {
            return $output;
        }
    }
}
