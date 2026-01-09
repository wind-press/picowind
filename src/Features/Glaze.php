<?php

declare(strict_types=1);

namespace Picowind\Features;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

/**
 * Glaze.js Integration
 *
 * This feature is disabled by default, but can be enabled by adding the following line to snippet or your theme's functions.php file:
 * ```php
 * add_filter('f!picowind/features:glaze', '__return_true');
 * ```
 *
 * Integrates Glaze.js - a utility-based animation framework built on GSAP with Tailwind-style syntax.
 *
 * @link https://glaze.dev/
 */
#[Service]
class Glaze
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = apply_filters('f!picowind/features:glaze', false);
    }

    #[Hook('wp_footer', type: 'action', priority: 20)]
    public function enqueue_glaze_script(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (isset($_GET['lc_page_editing_mode'])) {
            return;
        }

        \Picowind\render('features/glaze-script.twig');
    }
}
