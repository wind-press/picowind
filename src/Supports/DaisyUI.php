<?php

declare(strict_types=1);

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Config;

/**
 * DaisyUI Support
 *
 * Provides DaisyUI configuration and template context support.
 * DaisyUI can be enabled/disabled via the theme configuration.
 */
#[Service]
class DaisyUI
{
    /**
     * Add DaisyUI configuration to Timber context
     */
    #[Hook('timber/context', 'filter')]
    public function add_to_context(array $context): array
    {
        $context['daisyui_enabled'] = Config::get('features.daisyui', false);

        return $context;
    }

    /**
     * Add DaisyUI template locations to Timber
     * When DaisyUI is enabled, templates will be loaded from views/daisyui/ first
     */
    #[Hook('timber/locations', 'filter', priority: 10)]
    public function add_daisyui_locations(array $locations): array
    {
        // return $locations;
        if (! Config::get('features.daisyui', false)) {
            return $locations;
        }

        $daisyui_dir = [
            get_template_directory() . '/views/daisyui',
        ];

        // Add DaisyUI directory as the first location to check
        array_unshift($locations, $daisyui_dir);

        return $locations;
    }
}
