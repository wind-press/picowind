<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Service;

#[Service]
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
