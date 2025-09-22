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

    public function render_template(string $path, array $context = [], bool $print = true)
    {
        // Convert absolute path to relative view name for Blade
        $template_dirs = CoreTemplate::get_instance()->template_dirs;
        $view_name = null;

        foreach ($template_dirs as $dir) {
            if (strpos($path, $dir) === 0) {
                $relative_path = substr($path, strlen($dir) + 1);
                $view_name = str_replace(['/', '.blade.php'], ['.', ''], $relative_path);
                break;
            }
        }

        if ($view_name === null) {
            // Fallback: use the basename without extension
            $view_name = pathinfo($path, PATHINFO_FILENAME);
        }

        $output = $this->blade->make($view_name, $context)->render();

        if ($print) {
            echo $output;
        } else {
            return $output;
        }
    }
}
