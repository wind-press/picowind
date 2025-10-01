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
    ) {
        $this->renderBlade->setTwigRenderer($this->renderTwig);
    }

    /**
     * @param string|array $paths Template path(s) including file extension
     * @param array $context Context data to pass to template
     * @param ?string $engine Engine to use ('twig', 'blade'). Auto-detected if null
     * @param ?bool $print Whether to print output. Default true
     * @return void|string Rendered output if $print is false, otherwise void
     */
    public function render_template(string|array $paths, array $context = [], ?string $engine = null, ?bool $print = true): ?string
    {
        // Handle array of paths for fallback support
        if (is_array($paths)) {
            if (null !== $engine) {
                $paths = array_map(fn ($single_path) => $this->process_path_extension($single_path, $engine), $paths);
                return $this->renderWithEngine($engine, $paths, $context, $print);
            }

            $errors = [];
            foreach ($paths as $singlePath) {
                try {
                    $detectedEngine = $this->detectEngineFromPath($singlePath);
                    $processedPath = $this->process_path_extension($singlePath, $detectedEngine);
                    $output = $this->renderWithEngine($detectedEngine, $processedPath, $context, false);

                    if (empty($output) || ! is_string($output)) {
                        $errors[] = "{$singlePath}: Rendering returned empty output.";
                        continue;
                    }

                    if ($print) {
                        echo $output;
                        return null;
                    }

                    return $output;
                } catch (\Exception $e) {
                    $errors[] = "{$singlePath}: {$e->getMessage()}";
                    continue;
                }
            }

            throw new UnsupportedRenderEngineException(
                'mixed',
                'No template could be rendered. Tried: ' . implode(', ', $errors),
            );
        }

        if (null === $engine) {
            $engine = $this->detectEngineFromPath($paths);
        }

        $paths = $this->process_path_extension($paths, $engine);
        return $this->renderWithEngine($engine, $paths, $context, $print);
    }

    private function detectEngineFromPath(string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return match ($ext) {
            'twig' => 'twig',
            'php' => 'blade',
            '?' => throw new UnsupportedRenderEngineException('?', 'Cannot determine engine from `.?` extension.'),
            default => 'twig',
        };
    }

    private function renderWithEngine(string $engine, string|array $paths, array $context, bool $print)
    {
        return match ($engine) {
            'twig' => $this->renderTwig->render_template($paths, $context, $print),
            'blade' => $this->renderBlade->render_template($paths, $context, $print),
            default => throw new UnsupportedRenderEngineException($engine),
        };
    }

    private function process_path_extension(string $path, ?string $engine = null): string
    {
        // Handle `.?` extension placeholder - replace with actual extension
        if (str_ends_with($path, '.?')) {
            $path = substr($path, 0, -2);
            $path .= match ($engine) {
                'twig' => '.twig',
                'blade' => '.blade.php',
                default => throw new UnsupportedRenderEngineException($engine),
            };
        }

        // Add missing extension based on engine
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ('' === $ext) {
            $path .= match ($engine) {
                'twig' => '.twig',
                'blade' => '.blade.php',
                default => throw new UnsupportedRenderEngineException($engine),
            };
        }

        return $path;
    }
}
