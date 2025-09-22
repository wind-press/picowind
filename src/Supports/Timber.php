<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Supports;

use Exception;
use Picowind\Core\Render\Twig;
use Timber\Site;
use Timber\Timber as TimberTimber;
use Twig\Environment;
use Twig\TwigFunction;

use function get_fields;

class Timber
{
    private Site $site;

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
        Twig::get_instance();
        add_filter('timber/context', [$this, 'add_to_context']);
        add_filter('timber/twig', [$this, 'add_to_twig']);
        add_filter('timber/twig', [$this, 'add_inline_svg_to_twig']);

        TimberTimber::init();
    }

    public static function init(Site $site)
    {
        self::get_instance()->site = $site;
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

    public function add_to_twig(Environment $twigEnvironment): Environment
    {
        return $twigEnvironment;
    }

    public function add_inline_svg_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addFunction(new TwigFunction('inline_svg', [$this, 'inline_svg'], ['is_safe' => ['html']]));
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
        $svg = preg_replace('#<!DOCTYPE.*?>#i', '', $svg);
        $svg = preg_replace('#<(script|foreignObject)\b[^>]*>.*?</\1>#is', '', $svg);

        // force monochrome if requested (replace fills/strokes with currentColor)
        if (isset($opts['monochrome']) && $opts['monochrome']) {
            $svg = preg_replace('/\sfill="(?!none)[^"]*"/i', ' fill="currentColor"', $svg);
            $svg = preg_replace('/\sstroke="(?!none)[^"]*"/i', ' stroke="currentColor"', $svg);
        }

        // add class/title on root <svg>
        if (isset($opts['class']) && $opts['class'] !== '') {
            $svg = preg_replace('/<svg\b/i', '<svg class="' . esc_attr($opts['class']) . '"', $svg, 1);
        }

        if (isset($opts['title']) && $opts['title'] !== '') {
            $svg = preg_replace('/<svg\b/i', '<svg role="img" aria-label="' . esc_attr($opts['title']) . '"', $svg, 1);
        }

        return $svg;
    }
}
