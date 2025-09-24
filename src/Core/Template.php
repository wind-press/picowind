<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Core;

use Exception;
use Picowind\Core\Exception\UnsupportedRenderEngineException;
use Picowind\Core\Render\Blade as RenderBlade;
use Picowind\Core\Render\Php as RenderPhp;
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

        $current_dir = UtilsTheme::current_dir();

        $this->template_dirs = array_merge(
            $this->template_dirs,
            [
                $current_dir . '/views',
                $current_dir . '/blocks',
                $current_dir . '/components',
            ],
        );

        if (UtilsTheme::is_child_theme()) {
            $parent_dir = UtilsTheme::parent_dir();
            $this->template_dirs = array_merge(
                $this->template_dirs,
                [
                    $parent_dir . '/views',
                    $parent_dir . '/blocks',
                    $parent_dir . '/components',
                ],
            );
        }
    }

    /**
     * Render a template using the specified engine.
     *
     * @param string|array $path The path to the template file(s) including the file extension.
     * @param array  $context The context data to pass to the template.
     * @param ?string $engine The template engine to use ('twig', 'blade', 'php'). Default is 'twig' or determined by file extension.
     * @param ?bool $print Whether to print the rendered template. Default is true.
     * @return void|string The rendered template output if $print is false, otherwise void.
     * @throws TemplateNotExist If the template file does not exist.
     */
    public function render_template($paths, array $context = [], ?string $engine = null, ?bool $print = true)
    {
        // Handle array of paths for fallback support
        if (is_array($paths)) {
            // if engine is not specified, throw exception
            if ($engine === null) {
                throw new UnsupportedRenderEngineException('unknown', 'Engine must be specified when multiple paths are provided.');
            }
            $paths = array_map(fn ($single_path) => $this->process_path_extension($single_path, $engine), $paths);
        } else {
            // Handle single path
            if ($engine === null) {
                $ext = pathinfo($paths, PATHINFO_EXTENSION);
                if ($ext === 'twig') {
                    $engine = 'twig';
                } elseif ($ext === 'php') {
                    // could be blade or php
                    if (substr($paths, -10) === '.blade.php') {
                        $engine = 'blade';
                    } else {
                        $engine = 'php';
                    }
                } elseif ($ext === '?') {
                    throw new UnsupportedRenderEngineException('?', 'Cannot determine engine from `.?` extension. Please provide a valid extension or specify the engine.');
                } else {
                    // default to twig
                    $engine = 'twig';
                    $paths .= '.twig';
                }
            }

            $paths = $this->process_path_extension($paths, $engine);
        }

        if ($engine === 'twig') {
            return RenderTwig::get_instance()->render_template($paths, $context, $print);
        } elseif ($engine === 'blade') {
            return RenderBlade::get_instance()->render_template($paths, $context, $print);
        } elseif ($engine === 'php') {
            return RenderPhp::get_instance()->render_template($paths, $context, $print);
        } else {
            throw new UnsupportedRenderEngineException($engine);
        }
    }

    /**
     * Process path extension based on engine
     * @param string $path The original template path.
     * @param string $engine The rendering engine.
     * @return string The processed template path with correct extension.
     * @throws UnsupportedRenderEngineException If the engine is not supported.
     */
    private function process_path_extension(string $path, ?string $engine = null): string
    {
        // if the extension is `.?`, determine the actual extension based on the engine
        if (substr($path, -2) === '.?') {
            if ($engine === 'twig') {
                return substr($path, 0, -2) . '.twig';
            } elseif ($engine === 'blade') {
                return substr($path, 0, -2) . '.blade.php';
            } elseif ($engine === 'php') {
                return substr($path, 0, -2) . '.php';
            } else {
                throw new UnsupportedRenderEngineException($engine);
            }
        }
        return $path;
    }

    /**
     * Static method to render a template using the specified engine.
     *
     * @param string|array $paths The path to the template file(s) including the file extension.
     * @param array  $context The context data to pass to the template.
     * @param ?string $engine The template engine to use ('twig', 'blade', 'php'). Default is 'twig' or determined by file extension.
     * @param ?bool $print Whether to print the rendered template. Default is true.
     * @return void|string The rendered template output if $print is false, otherwise void.
     */
    public static function render($paths, array $context = [], ?string $engine = null, ?bool $print = true)
    {
        return self::get_instance()->render_template($paths, $context, $engine, $print);
    }
}
