<?php

declare(strict_types=1);

namespace Picowind\Compat\Optin;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

/**
 * Opt-in Lightbox Feature
 *
 * This feature is disabled by default, but can be enabled by adding the following line to snippet or your theme's functions.php file:
 * ```php
 * add_filter('f!picowind/compat/optin:lightbox', '__return_true');
 * ```
 *
 * @link https://github.com/livecanvas-team/picostrap5/blob/master/inc/opt-in/lightbox.php
 */
#[Service]
class Lightbox
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = apply_filters('f!picowind/compat/optin:lightbox', false);
    }

    #[Hook('wp_footer', type: 'action', priority: 20)]
    public function picostrap_optin_lightbox(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (isset($_GET['lc_page_editing_mode'])) {
            return;
        }

        \Picowind\render('compat/optin/lightbox.twig');
    }
}
