<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

use Picowind\Supports\LiveCanvas as LiveCanvasSupports;
use Picowind\Theme;

require_once __DIR__ . '/vendor/autoload.php';

// Initialize the unified theme architecture
Theme::get_instance();

if (! function_exists('lc_theme_is_livecanvas_friendly')) {
    function lc_theme_is_livecanvas_friendly(): bool
    {
        return LiveCanvasSupports::lc_theme_is_livecanvas_friendly();
    }
}
