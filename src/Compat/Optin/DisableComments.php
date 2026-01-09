<?php

declare(strict_types=1);

namespace Picowind\Compat\Optin;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

/**
 * Opt-in Disable Comments Feature
 *
 * This feature is disabled by default, but can be enabled by adding the following line to snippet or your theme's functions.php file:
 * ```php
 * add_filter('f!picowind/compat/optin:disable-comments', '__return_true');
 * ```
 *
 * @link https://github.com/livecanvas-team/picostrap5/blob/master/inc/opt-in/disable-comments.php
 */
#[Service]
class DisableComments
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = apply_filters('f!picowind/compat/optin:disable-comments', false);
    }

    #[Hook('admin_init', type: 'action', priority: 10)]
    public function picostrap_optin_disable_comments_admin_init(): void
    {
        if (!$this->enabled) {
            return;
        }

        // Redirect any user trying to access comments page
        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_redirect(admin_url());
            exit();
        }

        // Remove comments metabox from dashboard
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

        // Disable support for comments and trackbacks in post types
        foreach (get_post_types() as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }

    #[Hook('comments_open', type: 'filter', priority: 20, accepted_args: 2)]
    #[Hook('pings_open', type: 'filter', priority: 20, accepted_args: 2)]
    public function picostrap_optin_disable_comments_close_comments(bool $data): bool
    {
        if (!$this->enabled) {
            return $data;
        }
        return false;
    }

    #[Hook('comments_array', type: 'filter', priority: 10, accepted_args: 2)]
    public function picostrap_optin_disable_comments_hide_comments(array $data): array
    {
        if (!$this->enabled) {
            return $data;
        }
        return [];
    }

    #[Hook('admin_menu', type: 'action', priority: 10)]
    public function picostrap_optin_disable_comments_admin_menu(): void
    {
        if (!$this->enabled) {
            return;
        }

        remove_menu_page('edit-comments.php');
    }

    #[Hook('init', type: 'action', priority: 10)]
    public function picostrap_optin_disable_comments_init(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
        }
    }

    #[Hook('wp_before_admin_bar_render', type: 'action', priority: 10)]
    public function picostrap_optin_disable_comments_wp_before_admin_bar_render(): void
    {
        if (!$this->enabled) {
            return;
        }
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('comments');
    }
}
