<?php

declare(strict_types=1);

namespace Picowind\Features;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

/**
 * Alpine.js Integration
 *
 * This feature is disabled by default, but can be enabled by adding the following line to snippet or your theme's functions.php file:
 * ```php
 * add_filter('f!picowind/features:alpine', '__return_true');
 * ```
 *
 * Integrates Alpine.js for adding reactive and declarative behavior to your markup.
 *
 * @link https://alpinejs.dev/
 */
#[Service]
class Alpine
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = apply_filters('f!picowind/features:alpine', false);
    }

    #[Hook('wp_head', type: 'action', priority: 10)]
    public function enqueue_alpine_script(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (isset($_GET['lc_page_editing_mode'])) {
            return;
        }

        \Picowind\render('features/alpine-script.twig');
    }
}
