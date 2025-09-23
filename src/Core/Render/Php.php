<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Core\Render;

use Exception;
use Picowind\Core\Exception\TemplateNotExistException;

class Php
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
    private function __construct() {}

    public function render_template(string|array $path, array $context = [], bool $print = true)
    {
        // Find first existing template from array or use single path
        if (is_array($path)) {
            $template_path = null;
            foreach ($path as $single_path) {
                if (file_exists($single_path)) {
                    $template_path = $single_path;
                    break;
                }
            }
            if (! $template_path) {
                throw new TemplateNotExistException(implode(', ', $path));
            }
        } else {
            $template_path = $path;
        }

        extract($context);

        if ($print) {
            include $template_path;
        } else {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
    }
}
