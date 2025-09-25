<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core;

use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Core\Render\Blade as RenderBlade;
use Picowind\Core\Render\Php as RenderPhp;
use Picowind\Core\Render\Twig as RenderTwig;
use Picowind\Exceptions\UnsupportedRenderEngineException;

/**
 * Template rendering
 *
 * @package Picowind
 */
#[Service]
class Template
{
    public function __construct(
        private readonly RenderTwig $renderTwig,
        private readonly RenderBlade $renderBlade,
        private readonly RenderPhp $renderPhp,
    ) {}

    /**
     * Render a template using the specified engine.
     *
     * @param string|array $paths The path to the template file(s) including the file extension.
     * @param array  $context The context data to pass to the template.
     * @param ?string $engine The template engine to use ('twig', 'blade', 'php'). Default is 'twig' or determined by file extension.
     * @param ?bool $print Whether to print the rendered template. Default is true.
     * @return void|string The rendered template output if $print is false, otherwise void.
     * @throws TemplateNotExist If the template file does not exist.
     */
    public function render_template(string|array $paths, array $context = [], ?string $engine = null, ?bool $print = true): ?string
    {
        // Handle array of paths for fallback support
        if (is_array($paths)) {
            // if engine is not specified, throw exception
            if (null === $engine) {
                throw new UnsupportedRenderEngineException('unknown', 'Engine must be specified when multiple paths are provided: ' . implode(', ', $paths));
            }

            $paths = array_map(fn ($single_path) => $this->process_path_extension($single_path, $engine), $paths);
        } else {
            // Handle single path

            // if engine is not specified, determine from file extension
            if (null === $engine) {
                $ext = pathinfo($paths, PATHINFO_EXTENSION);
                if ('twig' === $ext) {
                    $engine = 'twig';
                } elseif ('php' === $ext) {
                    // could be blade or php
                    $engine = substr($paths, -10) === '.blade.php' ? 'blade' : 'php';
                } elseif ('?' === $ext) {
                    throw new UnsupportedRenderEngineException('?', 'Cannot determine engine from `.?` extension. Please provide a valid extension or specify the engine.');
                } else {
                    $engine = 'twig';
                }
            }

            $paths = $this->process_path_extension($paths, $engine);
        }

        if ('twig' === $engine) {
            return $this->renderTwig->render_template($paths, $context, $print);
        } elseif ('blade' === $engine) {
            return $this->renderBlade->render_template($paths, $context, $print);
        } elseif ('php' === $engine) {
            return $this->renderPhp->render_template($paths, $context, $print);
        } else {
            throw new UnsupportedRenderEngineException($engine);
        }
    }

    /**
     * Process path extension based on engine
     * @param string $path The original template path.
     * @param string|null $engine The rendering engine.
     * @return string The processed template path with correct extension.
     * @throws UnsupportedRenderEngineException If the engine is not supported.
     */
    private function process_path_extension(string $path, ?string $engine = null): string
    {
        // if the extension is `.?`, determine the actual extension based on the engine
        if (str_ends_with($path, '.?')) {
            $path = substr($path, 0, -2);
            $path .= match ($engine) {
                'twig' => '.twig',
                'blade' => '.blade.php',
                'php' => '.php',
                default => throw new UnsupportedRenderEngineException($engine),
            };
        }

        // if the path is missing an extension, add it based on the engine
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ('' === $ext) {
            $path .= match ($engine) {
                'twig' => '.twig',
                'blade' => '.blade.php',
                'php' => '.php',
                default => throw new UnsupportedRenderEngineException($engine),
            };
        }

        return $path;
    }
}
