<?php

declare(strict_types=1);

namespace Picowind\Supports;

use Kucrut\Vite;
use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Theme as UtilsTheme;

#[Service]
class Theme
{
    #[Hook('after_setup_theme', 'action')]
    public function setup_theme_supports(): void
    {
        // add_theme_support('');
    }

    #[Hook('admin_menu', 'action')]
    public function admin_page(): void
    {
        // Hook = appearance_page_{menu_slug}
        $hook = add_theme_page(
            __('Picowind', 'picowind'),
            __('Picowind', 'picowind'),
            'manage_options',
            'picowind',
            fn () => $this->render_admin_page(),
            1_000_001,
        );
    }

    private function render_admin_page(): void
    {
        do_action('a!picowind/supports/theme_support:render_admin_page.before');
        echo '<div id="picowind-app" class=""></div>';
        do_action('a!picowind/supports/theme_support:render_admin_page.after');
    }

    /**
     * This method will be called when the admin page is loaded.
     * The hook name is appearance_page_{menu_slug}, in this case, appearance_page_picowind
     */
    #[Hook('load-appearance_page_picowind', 'action')]
    public function load_admin_page(): void
    {
        add_action('admin_head', static fn () => remove_action('admin_notices', 'update_nag', 3), 1);
        add_action('admin_enqueue_scripts', fn () => $this->admin_page_metadata(), 1_000_001);
        add_action('admin_enqueue_scripts', fn () => $this->admin_page_scripts(), 1_000_001);
    }

    private function admin_page_scripts()
    {
        $handle = 'picowind:admin';

        $theme_dir = UtilsTheme::parent_dir() ?? UtilsTheme::current_dir();
        $manifest = Vite\get_manifest($theme_dir . '/public/build');

        wp_enqueue_script(
            $handle . '-i18n',
            $manifest->is_dev ? Vite\generate_development_asset_src($manifest, 'resources/admin/wp-i18n.js') : Vite\prepare_asset_url($manifest->dir) . '/wp-i18n.js',
            ['wp-i18n'],
            null,
        );
        wp_set_script_translations($handle . '-i18n', 'picowind');

        Vite\enqueue_asset(
            (UtilsTheme::parent_dir() ?? UtilsTheme::current_dir()) . '/public/build',
            'resources/admin/main.ts',
            [
                'handle' => $handle,
                'in_footer' => true,
                'dependencies' => ['wp-hooks', 'wp-i18n'],
            ],
        );
    }

    public function admin_page_metadata()
    {
        $theme = UtilsTheme::is_child_theme() ? wp_get_theme(basename(UtilsTheme::parent_dir())) : wp_get_theme();

        $metadata = [
            '_version' => $theme->get('Version'),
            '_wp_version' => get_bloginfo('version'),
            'assets' => [
                'url' => $theme->get_template_directory_uri() . '/public/build',
            ],
        ];

        if (current_user_can('manage_options')) {
            $metadata['_wpnonce'] = wp_create_nonce('picowind');

            $metadata['rest_api'] = [
                'nonce' => wp_create_nonce('wp_rest'),
                'root' => esc_url_raw(rest_url()),
                'namespace' => 'picowind/v1',
                'url' => esc_url_raw(rest_url('picowind/v1')),
            ];

            $metadata['site_meta'] = [
                'name' => get_bloginfo('name'),
                'site_url' => get_site_url(),
                'web_history' => admin_url(add_query_arg([
                    'page' => 'picowind',
                ], 'themes.php')),
            ];

            $metadata['is_debug'] = defined('WP_DEBUG') && WP_DEBUG;
        }

        $metadata = apply_filters('a!picowind/supports/theme_support:admin_page_metadata', $metadata);

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo sprintf('<script id="picowind:metadata">var picowind = %s;</script>', wp_json_encode($metadata));
    }
}
