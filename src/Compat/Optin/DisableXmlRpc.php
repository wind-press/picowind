<?php

declare(strict_types=1);

namespace Picowind\Compat\Optin;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

/**
 * Opt-in Disable XML-RPC Feature
 *
 * This feature is disabled by default, but can be enabled by adding the following line to snippet or your theme's functions.php file:
 * ```php
 * add_filter('f!picowind/compat/optin:disable-xml-rpc', '__return_true');
 * ```
 *
 * @link https://github.com/livecanvas-team/picostrap5/blob/master/inc/opt-in/disable-xml-rpc.php
 */
#[Service]
class DisableXmlRpc
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = apply_filters('f!picowind/compat/optin:disable-xml-rpc', false);
    }

    #[Hook('xmlrpc_enabled', type: 'filter', priority: 10)]
    #[Hook('pings_open', type: 'filter', priority: 9999, accepted_args: 2)]
    public function disable_xmlrpc_and_pings(): bool
    {
        if (!$this->enabled) {
            return true;
        }
        return false;
    }

    #[Hook('wp_headers', type: 'filter', priority: 10)]
    public function remove_x_pingback(array $headers): array
    {
        if (!$this->enabled) {
            return $headers;
        }

        unset($headers['X-Pingback'], $headers['x-pingback']);
        return $headers;
    }
}
