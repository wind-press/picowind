<?php

declare(strict_types=1);

namespace Picowind\Compat;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

/**
 * The backward compatibility of Picostrap 5 Cleanup features.
 *
 * This feature is enabled by default, but can be disabled by adding the following line to snippet or your theme's functions.php file:
 * ```php
 * add_filter('f!picowind/compat/cleanup:head_cleanup', '__return_false');
 * ```
 *
 * @link https://github.com/livecanvas-team/picostrap5/blob/master/inc/clean-head.php
 */
#[Service]
class Cleanup
{
    private bool $enabled = true;

    public function __construct()
    {
        $this->enabled = apply_filters('f!picowind/compat/cleanup:head_cleanup', true);
    }

    #[Hook('init', type: 'action')]
    public function picostrap_head_cleanup(): void
    {
        if (!$this->enabled) {
            return;
        }

        /* CLEANUP THE HEAD */
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'index_rel_link');
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
        remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);

        /* DISABLE EMOJIS */
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        add_filter('tiny_mce_plugins', [$this, 'picostrap_disable_emojis_tinymce']);
        add_filter('wp_resource_hints', [$this, 'picostrap_disable_emojis_remove_dns_prefetch'], 10, 2);
        add_filter('emoji_svg_url', '__return_false');

        //REMOVE REST API Link – api.w.org
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);

        // Remove the REST API endpoint.
        if (!is_user_logged_in()) {
            remove_action('rest_api_init', 'wp_oembed_register_route');
        }
    }

    public function picostrap_disable_emojis_tinymce($plugins)
    {
        return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
    }

    public function picostrap_disable_emojis_remove_dns_prefetch($urls, $relation_type)
    {
        if ('dns-prefetch' == $relation_type) {
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
            $urls = array_diff($urls, [$emoji_svg_url]);
        }
        return $urls;
    }

    #[Hook('pre_ping', type: 'action')]
    public function disable_self_pingbacks(&$links): void
    {
        if (!$this->enabled) {
            return;
        }

        $home = get_option('home');
        foreach ($links as $l => $link) {
            if (strpos($link, $home) === 0) {
                unset($links[$l]);
            }
        }
    }

    #[Hook('login_errors', type: 'filter')]
    public function picostrap_show_less_login_info($message)
    {
        if (!$this->enabled) {
            return $message;
        }

        return '<strong>ERROR</strong>: Stop guessing!';
    }

    #[Hook('the_generator', type: 'filter')]
    public function picostrap_no_generator($generator)
    {
        if (!$this->enabled) {
            return $generator;
        }

        return '';
    }

    /**
     * Filter to remove CSS classes and IDs from menu items
     * @link http://stackoverflow.com/questions/5222140/remove-li-class-id-for-menu-items-and-pages-list
     */
    #[Hook('nav_menu_item_id', type: 'filter', priority: 100, accepted_args: 1)]
    public function my_css_attributes_filter($var)
    {
        if (!$this->enabled) {
            return $var;
        }
        return is_array($var) ? [] : '';
    }

    /**
     * Disable WP-Embed
     * @link https://kinsta.com/knowledgebase/disable-embeds-wordpress/#disable-embeds-code
     */
    #[Hook('wp_footer', type: 'action')]
    public function deregister_wp_embed(): void
    {
        if (!$this->enabled) {
            return;
        }
        wp_deregister_script('wp-embed');
    }

    /**
     * Disable WP-Embed rewrites
     * @link https://kinsta.com/knowledgebase/disable-embeds-wordpress/#disable-embeds-code
     */
    #[Hook('init', type: 'action', priority: 9999)]
    public function picostrap_disable_embeds_code_init(): void
    {
        if (!$this->enabled) {
            return;
        }

        // Turn off oEmbed auto discovery.
        add_filter('embed_oembed_discover', '__return_false');

        // Don't filter oEmbed results.
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

        // Remove oEmbed discovery links.
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        // Remove oEmbed-specific JavaScript from the front-end and back-end.
        remove_action('wp_head', 'wp_oembed_add_host_js');
        add_filter('tiny_mce_plugins', [$this, 'picostrap_disable_embeds_tiny_mce_plugin']);

        // Remove all embeds rewrite rules.
        add_filter('rewrite_rules_array', [$this, 'picostrap_disable_embeds_rewrites']);

        // Remove filter of the oEmbed result before any HTTP requests are made.
        remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
    }

    public function picostrap_disable_embeds_tiny_mce_plugin($plugins)
    {
        return array_diff($plugins, ['wpembed']);
    }

    public function picostrap_disable_embeds_rewrites($rules)
    {
        foreach ($rules as $rule => $rewrite) {
            if (false !== strpos($rewrite, 'embed=true')) {
                unset($rules[$rule]);
            }
        }
        return $rules;
    }

    /**
     * Remove default WP inline style: `<style id='global-styles-inline-css'>` and SVG filters on body open
     * @link https://github.com/WordPress/gutenberg/issues/36834
     */
    #[Hook('init', type: 'action')]
    public function picostrap_wp_remove_global_css(): void
    {
        if (!get_theme_mod('disable_gutenberg')) {
            return;
        }

        remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
    }

    /**
     * Disable Contact Form 7 CSS - optional.
     * Can be enabled by adding the following line to snippet or your theme's functions.php file:
     * ```php
     * add_filter('f!picowind/compat/cleanup:disable_cf7_css', '__return_true');
     * ```
     */
    #[Hook('wp_print_styles', type: 'action', priority: 100)]
    public function disable_cf7_css(): void
    {
        if (!apply_filters('f!picowind/compat/cleanup:disable_cf7_css', false)) {
            return;
        }

        wp_deregister_style('contact-form-7');
    }
}
