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

    /**
     * Define LiveCanvas editor configuration
     * @link https://github.com/livecanvas-team/picostrap5/blob/0b4e60e32664941261ff3b5be1ba29a7ce2be424/inc/livecanvas-config.php
     */
    public static function lc_define_editor_config($key)
    {
        $data = [
            'config_file_slug' => 'daisyui-5',
        ];

        return $data[$key];
    }
}
