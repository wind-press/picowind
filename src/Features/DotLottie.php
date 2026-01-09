<?php

declare(strict_types=1);

namespace Picowind\Features;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

/**
 * DotLottie Web Component Integration
 *
 * This feature is disabled by default, but can be enabled by adding the following line to snippet or your theme's functions.php file:
 * ```php
 * add_filter('f!picowind/features:dotlottie', '__return_true');
 * ```
 *
 * Integrates dotLottie-wc web component for playing Lottie animations.
 *
 * @link https://developers.lottiefiles.com/docs/dotlottie-player/dotlottie-wc/
 */
#[Service]
class DotLottie
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = apply_filters('f!picowind/features:dotlottie', false);
    }

    #[Hook('wp_footer', type: 'action', priority: 20)]
    public function enqueue_dotlottie_script(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (isset($_GET['lc_page_editing_mode'])) {
            return;
        }

        \Picowind\render('features/dotlottie-script.twig');
    }

    /**
     * Register shortcode for Lottie player
     *
     * Usage:
     * [lottie src="https://example.com/animation.lottie" autoplay="true" loop="true"]
     * [lottie src="https://example.com/animation.lottie" autoplay="true" loop="true" speed="1.5" class="my-custom-class" style="width: 300px; height: 300px;"]
     */
    #[Hook('init', type: 'action')]
    public function register_shortcode(): void
    {
        if (!$this->enabled) {
            return;
        }

        add_shortcode('lottie', [$this, 'lottie_shortcode']);
    }

    /**
     * Shortcode callback for Lottie player
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function lottie_shortcode($atts): string
    {
        $atts = shortcode_atts([
            'src' => '',
            'autoplay' => 'false',
            'loop' => 'false',
            'speed' => '1',
            'class' => '',
            'style' => '',
            'width' => '',
            'height' => '',
        ], $atts, 'lottie');

        if (empty($atts['src'])) {
            return '<!-- Lottie: src attribute is required -->';
        }

        $classes = 'dotlottie-player';
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

        $autoplay_attr = filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN) ? ' autoplay' : '';
        $loop_attr = filter_var($atts['loop'], FILTER_VALIDATE_BOOLEAN) ? ' loop' : '';
        $speed_attr = ' speed="' . esc_attr($atts['speed']) . '"';

        return sprintf(
            '<dotlottie-wc src="%s"%s%s%s class="%s"%s></dotlottie-wc>',
            esc_url($atts['src']),
            $autoplay_attr,
            $loop_attr,
            $speed_attr,
            $classes,
            $style_attr
        );
    }
}
