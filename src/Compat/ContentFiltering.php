<?php

declare(strict_types=1);

namespace Picowind\Compat;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

/**
 * Allow LiveCanvas-powered sites to be rendered perfectly, even if LC plugin is deactivated.
 * Removes some WP default content filtering on LiveCanvas pages.
 *
 * This feature is enabled by default, but can be disabled by adding the following line to snippet or your theme's functions.php file:
 * ```php
 * add_filter('f!picowind/compat/content_filtering', '__return_false');
 * ```
 *
 * @link https://github.com/livecanvas-team/picostrap5/blob/master/inc/content-filtering.php
 */
#[Service]
class ContentFiltering
{
    private bool $enabled = true;

    public function __construct()
    {
        $this->enabled = apply_filters('f!picowind/compat/content_filtering', true);
    }

    #[Hook('wp', type: 'action', priority: PHP_INT_MAX)]
    public function alter_content_filters(): void
    {
        if (!$this->enabled) {
            return;
        }

        // If LC plugin is turned on, early exit
        if (function_exists('lc_post_is_using_livecanvas')) {
            return;
        }

        // Act only on single (posts/pages/CPT)
        if (!is_singular()) {
            return;
        }

        // If page is not using LiveCanvas, exit function
        $page_id = get_queried_object_id();
        if (!is_numeric($page_id) || get_post_meta($page_id, '_lc_livecanvas_enabled', true) != '1') {
            return;
        }

        // Got this list from core WP /wp-includes/default-filters.php
        remove_filter('the_content', 'do_blocks', 9);
        remove_filter('the_content', 'wptexturize');
        remove_filter('the_content', 'convert_smilies', 20);
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content', 'shortcode_unautop');
        remove_filter('the_content', 'prepend_attachment');
        remove_filter('the_content', 'wp_filter_content_tags');
        remove_filter('the_content', 'wp_replace_insecure_home_url');

        // More to remove, by inspection
        remove_filter('the_content', 'capital_P_dangit', 11);

        // Embeds, thank you rap1s
        remove_filter('the_content', [$GLOBALS['wp_embed'], 'run_shortcode'], 8);
        remove_filter('the_content', [$GLOBALS['wp_embed'], 'autoembed'], 8);

        // Add filter to remove useless LC attributes, necessary only when editing
        add_filter('the_content', [$this, 'strip_lc_attributes']);
    }

    public function strip_lc_attributes(string $html): string
    {
        $html = str_replace(' editable="inline"', '', $html);
        $html = str_replace(' editable="rich"', '', $html);
        $html = str_replace(' lc-helper="svg-icon"', '', $html);
        $html = str_replace(' lc-helper="background"', ' ', $html);
        $html = str_replace(' lc-helper="video-bg"', ' ', $html);
        $html = str_replace(' lc-helper="gmap-embed"', ' ', $html);
        $html = str_replace(' lc-helper="video-embed"', ' ', $html);
        $html = str_replace(' lc-helper="shortcode"', ' ', $html);
        $html = str_replace(' lc-helper="image"', ' ', $html);
        $html = str_replace(' lc-helper="icon"', ' ', $html);

        return $html;
    }
}
