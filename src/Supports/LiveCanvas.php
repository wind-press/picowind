<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Supports;

class LiveCanvas
{
    public function __construct() {}

    /**
     * Declare the theme as LiveCanvas friendly
     * @link https://livecanvas.com/faq/which-themes-with-livecanvas/
     */
    public static function lc_theme_is_livecanvas_friendly(): bool
    {
        return true;
    }
}
