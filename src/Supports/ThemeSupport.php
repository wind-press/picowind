<?php

declare(strict_types=1);

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

#[Service]
class ThemeSupport
{
    #[Hook('after_setup_theme', 'action')]
    public function setup_theme_supports(): void
    {
        add_theme_support('automatic-feed-links');
        add_theme_support(
            'html5',
            [
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            ],
        );
        add_theme_support('menus');
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
        add_theme_support('editor-styles');

        register_nav_menus([
            'primary' => 'Primary Menu',
            'footer' => 'Footer Menu',
        ]);
    }
}
