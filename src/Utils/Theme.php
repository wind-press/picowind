<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Utils;

use function get_stylesheet_directory;
use function get_template_directory;

/**
 * Theme utility functions.
 *
 * @package Picowind
 */
class Theme
{
    public static function is_child_theme(): bool
    {
        return is_child_theme();
    }

    public static function current_dir(): string
    {
        return get_stylesheet_directory();
    }

    public static function parent_dir(): ?string
    {
        return is_child_theme() ? get_template_directory() : null;
    }

    public static function child_dir(): ?string
    {
        return is_child_theme() ? get_stylesheet_directory() : null;
    }
}
