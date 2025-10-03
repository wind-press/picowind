<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Core\Render\Twig as RenderTwig;
use Picowind\Core\Render\Twig\BladeTokenParser;
use Picowind\Core\Render\Twig\LatteTokenParser;
use Timber\Site;
use Timber\Timber as TimberTimber;
use Twig\Environment;
use Twig\TwigFunction;

use function get_fields;

#[Service]
class Timber
{
    private ?Site $site = null;

    public function __construct(
        private readonly RenderTwig $renderTwig
    ) {
        TimberTimber::init();
    }

    public function setSite(Site $site): void
    {
        $this->site = $site;
    }

    #[Hook('timber/context', 'filter')]
    public function add_to_context(array $context): array
    {
        $context['site'] = $this->site;
        $context['menu'] = TimberTimber::get_menu();

        $context['primary_menu'] = TimberTimber::get_menu('primary');
        $context['footer_menu'] = TimberTimber::get_menu('footer');
        $context['options'] = function_exists('get_fields') ? get_fields('option') : [];

        return $context;
    }

    #[Hook('timber/twig', 'filter')]
    public function setup_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment = $this->add_to_twig($twigEnvironment);
        $twigEnvironment = $this->add_inline_svg_to_twig($twigEnvironment);
        $twigEnvironment = $this->add_blade_function_to_twig($twigEnvironment);
        $twigEnvironment = $this->add_blade_tag_to_twig($twigEnvironment);
        $twigEnvironment = $this->add_latte_function_to_twig($twigEnvironment);
        $twigEnvironment = $this->add_latte_tag_to_twig($twigEnvironment);
        return $twigEnvironment;
    }

    public function add_to_twig(Environment $twigEnvironment): Environment
    {
        return $twigEnvironment;
    }

    public function add_blade_function_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addFunction(
            new TwigFunction(
                'blade',
                $this->renderTwig->renderBladeTemplate(...),
                [
                    'is_safe' => ['html'],
                    'needs_context' => true,
                ]
            )
        );
        return $twigEnvironment;
    }

    public function add_blade_tag_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addTokenParser(new BladeTokenParser());
        return $twigEnvironment;
    }

    public function add_latte_function_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addFunction(
            new TwigFunction(
                'latte',
                $this->renderTwig->renderLatteTemplate(...),
                [
                    'is_safe' => ['html'],
                    'needs_context' => true,
                ]
            )
        );
        return $twigEnvironment;
    }

    public function add_latte_tag_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addTokenParser(new LatteTokenParser());
        return $twigEnvironment;
    }

    public function add_inline_svg_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addFunction(new TwigFunction('inline_svg', $this->inline_svg(...), ['is_safe' => ['html']]));
        return $twigEnvironment;
    }

    /**
     * Sanitize + inline an SVG attachment, let Twig print SVG as HTML.
     *
     * @param mixed $attachment Attachment ID, array with ID key, or full path to SVG file.
     * @mago-expect lint:parameter-type
     */
    public function inline_svg($attachment, array $opts = []): string
    {
        $id = is_array($attachment) ? $attachment['ID'] ?? null : (int) $attachment;

        if ($id) {
            $path = get_attached_file($id);
        } elseif (is_string($attachment)) {
            $path = $attachment;
        } else {
            $path = null;
        }

        if (! $path || ! file_exists($path)) {
            return '';
        }

        $svg = file_get_contents($path);

        // basic hardening (use a real sanitizer in production: enshrined/svg-sanitizer or Safe SVG plugin).
        $svg = preg_replace('/<\?xml.*?\?>/i', '', $svg);
        $svg = preg_replace('#<!DOCTYPE.*?>#i', '', (string) $svg);
        $svg = preg_replace('#<(script|foreignObject)\b[^>]*>.*?</\1>#is', '', (string) $svg);

        // force monochrome if requested (replace fills/strokes with currentColor)
        if (isset($opts['monochrome']) && $opts['monochrome']) {
            $svg = preg_replace('/\sfill="(?!none)[^"]*"/i', ' fill="currentColor"', (string) $svg);
            $svg = preg_replace('/\sstroke="(?!none)[^"]*"/i', ' stroke="currentColor"', (string) $svg);
        }

        // add class/title on root <svg>
        if (isset($opts['class']) && '' !== $opts['class']) {
            $svg = preg_replace('/<svg\b/i', '<svg class="' . esc_attr($opts['class']) . '"', (string) $svg, 1);
        }

        if (isset($opts['title']) && '' !== $opts['title']) {
            $svg = preg_replace('/<svg\b/i', '<svg role="img" aria-label="' . esc_attr($opts['title']) . '"', (string) $svg, 1);
        }

        return $svg;
    }
}
