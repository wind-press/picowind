<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Core\Render;

use Exception;
use Picowind\Core\Blocks as CoreBlocks;
use Picowind\Core\Template as CoreTemplate;
use Timber\Timber;

class Twig
{
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
        if (! file_exists(CoreTemplate::get_instance()->twig_cache_path)) {
            wp_mkdir_p(CoreTemplate::get_instance()->twig_cache_path);
        }

        add_filter('timber/locations', [$this, 'locations']);
        add_filter('timber/twig/environment/options', [$this, 'filter_env']);
    }

    public function locations(array $locations): array
    {
        $blocks_dir = CoreBlocks::get_instance()->blocks_dir;

        $locations = array_unique(array_merge($locations, $blocks_dir));

        return $locations;
    }

    public function filter_env(array $options): array
    {
        $options['cache'] = CoreTemplate::get_instance()->twig_cache_path;
        return $options;
    }

    public function render_template(string $path, array $context = [], bool $print = true)
    {
        $output = Timber::compile($path, $context);
        if ($print) {
            echo $output;
        } else {
            return $output;
        }
    }
}
