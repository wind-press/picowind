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
        /*
         * Make theme available for translation.
         */
        load_theme_textdomain('picowind', get_template_directory() . '/languages');

        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
         * Let WordPress manage the document title.
         */
        add_theme_support('title-tag');

        /*
         * Enable support for Post Thumbnails on posts and pages.
         */
        add_theme_support('post-thumbnails');

        // This theme uses wp_nav_menu() in locations.
        register_nav_menus([
            'primary' => __('Primary Menu', 'picowind'),
            'secondary' => __('Secondary Menu', 'picowind'),
        ]);

        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'script',
            'style',
        ]);

        /*
         * Adding support for Widget edit icons in customizer
         */
        add_theme_support('customize-selective-refresh-widgets');

        /*
         * Enable support for Post Formats.
         */
        add_theme_support('post-formats', [
            'aside',
            'image',
            'video',
            'quote',
            'link',
        ]);

        // Set up the WordPress core custom background feature.
        add_theme_support('custom-background', apply_filters('f!picowind/theme/support:custom_background', [
            'default-color' => 'ffffff',
            'default-image' => '',
        ]));

        // Set up the WordPress Theme logo feature.
        add_theme_support('custom-logo');

        // Add support for responsive embedded content.
        add_theme_support('responsive-embeds');

        // Full width content support
        add_theme_support('align-wide');
    }

    /**
     * Set up the theme default settings upon activation.
     */
    #[Hook('after_switch_theme', type: 'action')]
    public function after_switch_theme(): void
    {
        error_log('Picowind: after_switch_theme called');
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $is_require_reload = false;
        // Require the WindPress plugin to be active
        if (! is_plugin_active('windpress/windpress.php')) {
            if (! file_exists(WP_PLUGIN_DIR . '/windpress/windpress.php')) {
                require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/misc.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                $api = plugins_api('plugin_information', array(
                    'slug' => 'windpress',
                    'fields' => array(
                        'short_description' => false,
                        'sections' => false,
                        'requires' => false,
                        'rating' => false,
                        'ratings' => false,
                        'downloaded' => false,
                        'last_updated' => false,
                        'added' => false,
                        'tags' => false,
                        'compatibility' => false,
                        'homepage' => false,
                        'donate_link' => false,
                    ),
                ));
                if (! is_wp_error($api)) {
                    $upgrader = new \Plugin_Upgrader();
                    $installed = $upgrader->install($api->download_link);
                }
            }
            activate_plugin('windpress/windpress.php');
            $is_require_reload = true;
        }
        // Import the Picowind css file into the WindPress `main.css` file
        $main_css_path = WP_CONTENT_DIR . '/uploads/windpress/data/main.css';
        if (! file_exists($main_css_path)) {
            // copy the default main.css file
            $default_main_css_path = WP_CONTENT_DIR . '/plugins/windpress/stubs/tailwindcss-v4/main.css';
            if (file_exists($default_main_css_path)) {
                $wp_filesystem->copy($default_main_css_path, $main_css_path);
            } else {
                return;
            }
        }
        $main_css_content = $wp_filesystem->get_contents($main_css_path);
        if ($main_css_content === false) {
            return;
        }
        $import_statement = '@import "./@picowind/tailwind.css";';
        if (strpos($main_css_content, $import_statement) === false) {
            $main_css_content .= "\n" . $import_statement . "\n";
        }
        $preflight_statement = '/* @import "tailwindcss/preflight.css" layer(base); */';
        if (strpos($main_css_content, $preflight_statement) !== false) {
            $main_css_content = str_replace(
                $preflight_statement,
                '@import "tailwindcss/preflight.css" layer(base);',
                $main_css_content,
            );
        }
        $wp_filesystem->put_contents($main_css_path, $main_css_content);
        if ($is_require_reload) {
            // reload with js to avoid "Headers already sent" error
            echo '<script>location.reload();</script>';
            exit();
        }
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
            $manifest->is_dev ? Vite\generate_development_asset_src($manifest, 'resources/wp-i18n.js') : Vite\prepare_asset_url($manifest->dir) . '/wp-i18n.js',
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
