<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Supports;

use Timber\Site;
use Timber\Timber as TimberTimber;
use Twig\Environment;
use Twig\TwigFunction;

use function get_fields;

class Timber
{
    private Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;

        add_filter('timber/context', [$this, 'add_to_context']);
        add_filter('timber/twig', [$this, 'add_to_twig']);
        add_filter('timber/twig', [$this, 'add_inline_svg_to_twig']);
    }

    public function add_to_context(array $context): array
    {
        $context['site'] = $this->site;
        $context['menu'] = TimberTimber::get_menu();

        $context['primary_menu'] = TimberTimber::get_menu('primary');
        $context['footer_menu'] = TimberTimber::get_menu('footer');
        $context['options'] = function_exists('get_fields') ? get_fields('option') : [];

        // Require block functions files
        foreach (glob(get_template_directory() . '/blocks/*/functions.php') as $file) {
            require_once $file;
        }

        return $context;
    }

    public function add_to_twig(Environment $twig): Environment
    {
        return $twig;
    }

    public function add_inline_svg_to_twig(Environment $twig): Environment
    {
        $twig->addFunction(new TwigFunction('inline_svg', [$this, 'inline_svg'], ['is_safe' => ['html']]));
        return $twig;
    }

    /**
     * Sanitize + inline an SVG attachment, let Twig print SVG as HTML.
     */
    public function inline_svg($attachment, array $opts = []): string
    {
        if (is_array($attachment)) {
            $id = $attachment['ID'] ?? null;
        } else {
            $id = (int) $attachment;
        }

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
        $svg = preg_replace('#<!DOCTYPE.*?>#i', '', $svg);
        $svg = preg_replace('#<(script|foreignObject)\b[^>]*>.*?</\1>#is', '', $svg);

        // force monochrome if requested (replace fills/strokes with currentColor)
        if (! empty($opts['monochrome'])) {
            $svg = preg_replace('/\sfill="(?!none)[^"]*"/i', ' fill="currentColor"', $svg);
            $svg = preg_replace('/\sstroke="(?!none)[^"]*"/i', ' stroke="currentColor"', $svg);
        }

        // add class/title on root <svg>
        if (! empty($opts['class'])) {
            $svg = preg_replace('/<svg\b/i', '<svg class="' . esc_attr($opts['class']) . '"', $svg, 1);
        }

        if (! empty($opts['title'])) {
            $svg = preg_replace('/<svg\b/i', '<svg role="img" aria-label="' . esc_attr($opts['title']) . '"', $svg, 1);
        }

        return $svg;
    }
}
