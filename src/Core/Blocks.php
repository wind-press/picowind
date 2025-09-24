<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Core;

use DirectoryIterator;
use Exception;
use Picowind\Core\Template as CoreTemplate;

/**
 * Handle custom blocks registration and management.
 *
 * @package Picowind
 */
class Blocks
{
    public array $supported_namespaces = ['acf', 'blockstudio', 'picowind'];
    public array $supported_render_engines = ['twig', 'blade', 'php'];
    public array $blocks_dir = [];

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
        add_action('init', [$this, 'register']);
        add_filter('block_type_metadata', [$this, 'metadata'], 1);
        add_filter('block_type_metadata_settings', [$this, 'metadata_settings'], 1, 2);
    }

    /**
     * Register custom blocks from the `/blocks` directory.
     */
    public function register(): void
    {
        $blocks = [];

        foreach (CoreTemplate::get_instance()->template_dirs as $dir_path) {
            if (substr($dir_path, -7) !== '/blocks') {
                continue;
            }

            foreach (new DirectoryIterator($dir_path) as $dir) {
                if ($dir->isDot()) {
                    continue;
                }

                if (file_exists($dir->getPathname() . '/block.json')) {
                    $block_json = json_decode(file_get_contents($dir->getPathname() . '/block.json'), true);

                    do_action('picowind/blocks/register:before', $block_json, $dir->getPathname());

                    $shouldRegister = apply_filters('picowind/blocks/register:should-register', true, $block_json, $dir->getPathname());
                    if (! $shouldRegister) {
                        continue;
                    }

                    $blocks[] = $dir->getPathname();
                }
            }
        }

        foreach ($blocks as $block) {
            register_block_type($block);
        }
    }

    public function metadata(array $metadata): array
    {
        if (! isset($metadata['file'])) {
            return $metadata;
        }

        $in_blocks_dir = false;
        foreach (CoreTemplate::get_instance()->template_dirs as $dir_path) {
            if (strpos($metadata['file'], $dir_path) !== false) {
                $in_blocks_dir = true;
                break;
            }
        }
        if (! $in_blocks_dir) {
            return $metadata;
        }

        // add `picowind/` namespace as default if not set
        if (strpos($metadata['name'], '/') === false) {
            $metadata['name'] = 'picowind/' . $this->name_slugify($metadata['name']);
        }

        [$namespace, $name] = explode('/', $metadata['name'], 2);

        if (! in_array($namespace, $this->supported_namespaces, true)) {
            return $metadata;
        }

        if (! isset($metadata['render'])) {
            if (in_array($namespace, $this->supported_namespaces, true)) {
                $metadata['renderEngine'] = 'twig';
            }
        } elseif (in_array($metadata['render'], $this->supported_render_engines, true)) {
            $metadata['renderEngine'] = $metadata['render'];
            unset($metadata['render']);
        }

        return $metadata;
    }

    public function metadata_settings(array $settings, array $metadata): array
    {
        if (! isset($metadata['file'])) {
            return $settings;
        }

        $in_blocks_dir = false;
        foreach (CoreTemplate::get_instance()->template_dirs as $dir_path) {
            if (strpos($metadata['file'], $dir_path) !== false) {
                $in_blocks_dir = true;
                break;
            }
        }
        if (! $in_blocks_dir) {
            return $settings;
        }

        [$namespace, $name] = explode('/', $metadata['name'], 2);

        if (! in_array($namespace, $this->supported_namespaces, true)) {
            return $settings;
        }

        if (isset($metadata['renderEngine']) && in_array($metadata['renderEngine'], $this->supported_render_engines, true)) {
            $settings['render_engine'] = $metadata['renderEngine'];

            if ($namespace === 'acf') {
                $settings['render_callback'] = ['Picowind\Supports\AdvancedCustomFields', 'block_render_callback'];
            }
        }

        return $settings;
    }

    /**
     * Returns a slug friendly string.
     *
     * @see     acf_slugify()
     * @param   string $str  The string to convert.
     * @param   string $glue The glue between each slug piece.
     * @return  string
     */
    private function name_slugify($str = '', $glue = '-')
    {
        $raw = $str;
        $slug = str_replace(array('_', '-', '/', ' '), $glue, strtolower(remove_accents($raw)));
        $slug = preg_replace('/[^A-Za-z0-9' . preg_quote($glue) . ']/', '', $slug);

        /**
         * Filters the slug created by name_slugify().
         *
         * @since 5.11.4
         *
         * @param string $slug The newly created slug.
         * @param string $raw  The original string.
         * @param string $glue The separator used to join the string into a slug.
         */
        return apply_filters('picowind/blocks/name_slugify', $slug, $raw, $glue);
    }
}
