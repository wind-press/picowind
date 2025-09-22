<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Core;

use Exception;
use Picowind\Core\Exception\TemplateNotExistException;
use Picowind\Core\Exception\UnsupportedRenderEngineException;
use Picowind\Core\Render\Blade as RenderBlade;
use Picowind\Core\Render\Twig as RenderTwig;
use Picowind\Utils\Theme as UtilsTheme;

/**
 * Template rendering
 *
 * @package Picowind
 */
class Template
{
    /**
     * Cache path for Twig templates.
     * @var string
     */
    public string $twig_cache_path;

    /**
     * Cache path for Blade templates.
     * @var string
     */
    public string $blade_cache_path;

    /**
     * Directories the templates are located in.
     * @var array
     */
    public array $template_dirs = [];

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
        $upload_dir = wp_upload_dir()['basedir'];

        $this->twig_cache_path = $upload_dir . '/picowind/cache/twig';
        $this->blade_cache_path = $upload_dir . '/picowind/cache/blade';

        // blocks and views directories
        $this->template_dirs = [
            UtilsTheme::current_dir() . '/blocks',
            UtilsTheme::current_dir() . '/views',
        ];
        if (UtilsTheme::is_child_theme()) {
            array_push($this->template_dirs, UtilsTheme::parent_dir() . '/blocks');
            array_push($this->template_dirs, UtilsTheme::parent_dir() . '/views');
        }
    }

    /**
     * Render a template using the specified engine.
     *
     * @param string $engine The template engine to use ('twig', 'blade', 'php').
     * @param string $path The path to the template file including the file extension.
     * @param array  $context The context data to pass to the template.
     * @throws TemplateNotExist If the template file does not exist.
     */
    public function render_template(string $engine, string $path, array $context = []): void
    {
        // if the extension is `.?`, determine the actual extension based on the engine
        if (substr($path, -2) === '.?') {
            if ($engine === 'twig') {
                $path = substr($path, 0, -2) . '.twig';
            } elseif ($engine === 'blade') {
                $path = substr($path, 0, -2) . '.blade.php';
            } elseif ($engine === 'php') {
                $path = substr($path, 0, -2) . '.php';
            } else {
                throw new TemplateNotExistException($path);
            }
        }

        if (! file_exists($path)) {
            throw new TemplateNotExistException($path);
        }

        if ($engine === 'twig') {
            RenderTwig::get_instance()->render_template($path, $context);
        } elseif ($engine === 'blade') {
            RenderBlade::get_instance()->render_template($path, $context);
        } elseif ($engine === 'php') {
            extract($context);
            include $path;
        } else {
            throw new UnsupportedRenderEngineException($engine);
        }
    }

    public static function render(string $engine, string $path, array $context = []): void
    {
        self::get_instance()->render_template($engine, $path, $context);
    }
}
