<?php

declare(strict_types=1);

/**
 * @package Picowind Child
 * @subpackage Picowind
 * @since 1.0.0
 */

function picowind_child_setup(): void
{
    add_action('wp_enqueue_scripts', 'picowind_child_enqueue_styles');
}

function picowind_child_enqueue_styles(): void
{
    wp_enqueue_style(
        'picowind-child-style',
        get_stylesheet_directory_uri() . '/public/styles/main.css',
        ['picowind-style'],
        wp_get_theme(get_template())->get('Version')
    );
}

add_action('after_setup_theme', 'picowind_child_setup');
