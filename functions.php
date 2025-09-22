<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

use Picowind\Supports\LiveCanvas as LiveCanvasSupports;
use Picowind\Theme;
use Timber\Timber;

require_once __DIR__ . '/vendor/autoload.php';

new Theme();

if (! function_exists('lc_theme_is_livecanvas_friendly')) {
    function lc_theme_is_livecanvas_friendly(): bool
    {
        return LiveCanvasSupports::lc_theme_is_livecanvas_friendly();
    }
}
