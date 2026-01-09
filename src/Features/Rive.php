<?php

declare(strict_types=1);

namespace Picowind\Features;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

/**
 * Rive Web Runtime Integration
 *
 * This feature is disabled by default, but can be enabled by adding the following line to snippet or your theme's functions.php file:
 * ```php
 * add_filter('f!picowind/features:rive', '__return_true');
 * ```
 *
 * Integrates Rive web runtime for playing interactive animations.
 *
 * @link https://rive.app/docs/runtimes/web/web-js
 */
#[Service]
class Rive
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = apply_filters('f!picowind/features:rive', false);
    }

    #[Hook('wp_footer', type: 'action', priority: 20)]
    public function enqueue_rive_script(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (isset($_GET['lc_page_editing_mode'])) {
            return;
        }

        \Picowind\render('features/rive-script.twig');
    }

    /**
     * Register shortcode for Rive player
     *
     * Usage:
     * [rive src="https://example.com/animation.riv" autoplay="true"]
     * [rive src="https://example.com/animation.riv" autoplay="true" statemachines="State Machine 1" class="my-custom-class" style="width: 300px; height: 300px;"]
     *
     * Note: WordPress converts attribute names to lowercase, so use 'statemachines' not 'stateMachines'
     */
    #[Hook('init', type: 'action')]
    public function register_shortcode(): void
    {
        if (!$this->enabled) {
            return;
        }

        add_shortcode('rive', [$this, 'rive_shortcode']);
    }

    /**
     * Shortcode callback for Rive player
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function rive_shortcode($atts): string
    {
        static $rive_instance = 0;
        $rive_instance++;

        $atts = shortcode_atts(
            [
                'src' => '',
                'autoplay' => 'true',
                'artboard' => '',
                'statemachines' => '', // WordPress converts to lowercase
                'class' => '',
                'style' => '',
                'width' => '',
                'height' => '',
            ],
            $atts,
            'rive',
        );

        if (empty($atts['src'])) {
            return '<!-- Rive: src attribute is required -->';
        }

        $canvas_id = 'picowind-rive-canvas-' . $rive_instance;
        $classes = 'rive-canvas';
        if (!empty($atts['class'])) {
            $classes .= ' ' . esc_attr($atts['class']);
        }

        $styles = [];
        if (!empty($atts['width'])) {
            $styles[] = 'width: ' . esc_attr($atts['width']);
        }
        if (!empty($atts['height'])) {
            $styles[] = 'height: ' . esc_attr($atts['height']);
        }
        if (!empty($atts['style'])) {
            $styles[] = esc_attr($atts['style']);
        }
        $style_attr = !empty($styles) ? ' style="' . implode('; ', $styles) . '"' : '';

        // Build config object to store in data attribute
        $config = [
            'src' => esc_url($atts['src']),
            'autoplay' => filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN),
        ];

        if (!empty($atts['artboard'])) {
            $config['artboard'] = esc_attr($atts['artboard']);
        }

        if (!empty($atts['statemachines'])) {
            $config['stateMachines'] = esc_attr($atts['statemachines']);
        }

        $config_json = esc_attr(wp_json_encode($config));

        // Return canvas element with config stored in data attribute
        return sprintf(
            '<canvas id="%s" class="%s" data-rive-config=\'%s\'%s></canvas>',
            $canvas_id,
            $classes,
            $config_json,
            $style_attr,
        );
    }
}
