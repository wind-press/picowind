<?php

declare(strict_types=1);

namespace Picowind\Api;

use Exception;
use Picowind\Core\Discovery\Attributes\Controller;
use Picowind\Core\Discovery\Attributes\Route;
use Picowind\Utils\Config;
use WP_REST_Request;
use WP_REST_Response;

#[Controller(namespace: 'picowind/v1', prefix: '/onboarding')]
final class OnboardingController
{
    /**
     * Get onboarding status
     */
    #[Route(
        path: '/status',
        methods: 'GET',
        permission_callback: 'manage_options',
    )]
    public function getStatus(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $isCompleted = Config::get('onboarding.completed', false);
            $childTheme = $this->getActiveChildTheme();

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'completed' => $isCompleted,
                    'hasChildTheme' => ! empty($childTheme),
                    'childTheme' => $childTheme,
                ],
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available child themes from catalog
     */
    #[Route(
        path: '/themes',
        methods: 'GET',
        permission_callback: 'manage_options',
    )]
    public function getAvailableThemes(WP_REST_Request $request): WP_REST_Response
    {
        try {
            // Dummy data for now
            $themes = [
                [
                    'id' => 'tw',
                    'name' => 'TW',
                    'title' => 'Tailwind CSS Child Theme',
                    'description' => 'A modern child theme built with Tailwind CSS utility-first framework',
                    'version' => '1.0.0',
                    'author' => 'Picowind',
                    'thumbnail' => '',
                    'downloadUrl' => 'https://example.com/themes/picowind-tw.zip',
                    'features' => [
                        'Tailwind CSS integration',
                        'Responsive design',
                        'Dark mode support',
                    ],
                ],
                [
                    'id' => 'bs',
                    'name' => 'BS',
                    'title' => 'Bootstrap Child Theme',
                    'description' => 'A robust child theme powered by Bootstrap framework',
                    'version' => '1.0.0',
                    'author' => 'Picowind',
                    'thumbnail' => '',
                    'downloadUrl' => 'https://example.com/themes/picowind-bs.zip',
                    'features' => [
                        'Bootstrap 5 integration',
                        'Grid system',
                        'Component library',
                    ],
                ],
                [
                    'id' => 'pure',
                    'name' => 'Pure',
                    'title' => 'Pure CSS Child Theme',
                    'description' => 'A minimalist child theme',
                    'version' => '1.0.0',
                    'author' => 'Picowind',
                    'thumbnail' => '',
                    'downloadUrl' => 'https://example.com/themes/picowind-pure.zip',
                    'features' => [
                        'Minimal CSS footprint',
                        'Fast loading',
                        'Clean design',
                    ],
                ],
            ];

            return new WP_REST_Response([
                'success' => true,
                'data' => $themes,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Install and activate a child theme
     */
    #[Route(
        path: '/install-theme',
        methods: 'POST',
        permission_callback: 'manage_options',
        args: [
            'themeId' => [
                'required' => true,
                'type' => 'string',
                'description' => 'The ID of the theme to install',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    )]
    public function installTheme(WP_REST_Request $request): WP_REST_Response
    {
        $themeId = $request->get_param('themeId');

        try {
            // Get available themes to find download URL
            $availableThemes = $this->getThemesCatalog();
            $themeData = null;

            foreach ($availableThemes as $theme) {
                if ($theme['id'] === $themeId) {
                    $themeData = $theme;
                    break;
                }
            }

            if (! $themeData) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Theme not found in catalog',
                ], 404);
            }

            // Download and install the theme
            $downloadUrl = $themeData['downloadUrl'];
            $installResult = $this->downloadAndInstallTheme($downloadUrl);

            if (is_wp_error($installResult)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $installResult->get_error_message(),
                ], 500);
            }

            // Get the installed theme slug
            $themeSlug = $installResult['destination_name'];

            // Activate the theme
            $activationResult = $this->activateTheme($themeSlug);

            if (is_wp_error($activationResult)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $activationResult->get_error_message(),
                ], 500);
            }

            // Store selected theme in config
            Config::set('onboarding.selectedTheme', $themeId);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Theme installed and activated successfully',
                'data' => [
                    'downloaded' => true,
                    'installed' => true,
                    'activated' => true,
                    'themeSlug' => $themeSlug,
                    'theme' => $themeData,
                ],
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download and install a theme from URL
     */
    private function downloadAndInstallTheme(string $downloadUrl)
    {
        // Include required WordPress files
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        // Create a custom skin to suppress output
        $skin = new \WP_Ajax_Upgrader_Skin();

        // Create upgrader instance
        $upgrader = new \Theme_Upgrader($skin);

        // Download and install
        $result = $upgrader->install($downloadUrl);

        if (is_wp_error($result)) {
            return $result;
        }

        if (! $result) {
            return new \WP_Error('installation_failed', 'Theme installation failed');
        }

        // Get the theme destination
        $theme_info = $upgrader->theme_info();

        if (! $theme_info) {
            return new \WP_Error('theme_info_failed', 'Unable to get theme information');
        }

        return [
            'success' => true,
            'destination_name' => $theme_info->get_stylesheet(),
        ];
    }

    /**
     * Activate a theme by slug
     */
    private function activateTheme(string $themeSlug)
    {
        // Verify theme exists
        $theme = wp_get_theme($themeSlug);

        if (! $theme->exists()) {
            return new \WP_Error('theme_not_found', 'Theme does not exist');
        }

        // Switch to the new theme
        switch_theme($themeSlug);

        // Verify activation
        if (get_stylesheet() !== $themeSlug) {
            return new \WP_Error('activation_failed', 'Theme activation failed');
        }

        return [
            'success' => true,
            'theme' => $themeSlug,
        ];
    }

    /**
     * Mark onboarding as completed
     */
    #[Route(
        path: '/complete',
        methods: 'POST',
        permission_callback: 'manage_options',
    )]
    public function complete(): WP_REST_Response
    {
        try {
            Config::set('onboarding.completed', true);
            Config::set('onboarding.completedAt', current_time('mysql'));

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Onboarding completed successfully',
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset onboarding status (for testing)
     */
    #[Route(
        path: '/reset',
        methods: 'POST',
        permission_callback: 'manage_options',
    )]
    public function reset(): WP_REST_Response
    {
        try {
            Config::set('onboarding.completed', false);
            Config::set('onboarding.selectedTheme', null);
            Config::set('onboarding.completedAt', null);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Onboarding reset successfully',
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active child theme info
     */
    private function getActiveChildTheme(): ?array
    {
        $currentTheme = wp_get_theme();

        // Check if current theme is a child theme
        if ($currentTheme->parent()) {
            return [
                'name' => $currentTheme->get('Name'),
                'slug' => $currentTheme->get_stylesheet(),
                'version' => $currentTheme->get('Version'),
                'parent' => $currentTheme->parent()->get('Name'),
            ];
        }

        return null;
    }

    /**
     * Get themes catalog (same as getAvailableThemes but returns array)
     */
    private function getThemesCatalog(): array
    {
        return [
            [
                'id' => 'tw',
                'name' => 'TW',
                'title' => 'Tailwind CSS Child Theme',
                'description' => 'A modern child theme built with Tailwind CSS utility-first framework',
                'version' => '1.0.0',
                'author' => 'Picowind',
                'thumbnail' => '',
                'downloadUrl' => 'https://example.com/themes/picowind-tw.zip',
                'features' => [
                    'Tailwind CSS integration',
                    'Responsive design',
                    'Dark mode support',
                ],
            ],
            [
                'id' => 'bs',
                'name' => 'BS',
                'title' => 'Bootstrap Child Theme',
                'description' => 'A robust child theme powered by Bootstrap framework',
                'version' => '1.0.0',
                'author' => 'Picowind',
                'thumbnail' => '',
                'downloadUrl' => 'https://example.com/themes/picowind-bs.zip',
                'features' => [
                    'Bootstrap 5 integration',
                    'Grid system',
                    'Component library',
                ],
            ],
            [
                'id' => 'pure',
                'name' => 'Pure',
                'title' => 'Pure CSS Child Theme',
                'description' => 'A lightweight child theme using Pure CSS',
                'version' => '1.0.0',
                'author' => 'Picowind',
                'thumbnail' => '',
                'downloadUrl' => 'https://example.com/themes/picowind-pure.zip',
                'features' => [
                    'Minimal CSS footprint',
                    'Fast loading',
                    'Clean design',
                ],
            ],
        ];
    }
}
