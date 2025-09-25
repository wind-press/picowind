<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Theme as UtilsTheme;
use Timber\Timber;

#[Service]
class Twig
{
    public function __construct()
    {
        $cache_path = UtilsTheme::get_cache_path('twig');
        if (! file_exists($cache_path)) {
            wp_mkdir_p($cache_path);
        }

        Timber::$dirname = [
            UtilsTheme::get_template_directory_names(),
        ];
    }

    public function locations(array $locations): array
    {
        $locations = array_unique(array_merge($locations, UtilsTheme::get_template_directories()));

        return $locations;
    }

    #[Hook('timber/twig/environment/options', 'filter')]
    public function filter_env(array $options): array
    {
        $options['cache'] = UtilsTheme::get_cache_path('twig');
        return $options;
    }

    /**
     * Renders a Twig template with the given context.
     *
     * @param string|array $paths The path(s) to the Twig template file(s).
     * @param array $context The context data to pass to the template.
     * @param bool $print Whether to print the output (true) or return it (false). Default is true.
     * @return bool|string|null Returns the rendered output if $print is false, otherwise void.
     */
    public function render_template($paths, array $context = [], bool $print = true)
    {
        $output = Timber::compile($paths, $context);
        if ($print) {
            echo $output;
        } else {
            return $output;
        }
        return null;
    }
}
