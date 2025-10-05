<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Timber\Site;
use Timber\Timber as TimberTimber;

use function get_fields;

#[Service]
class Timber
{
    private ?Site $site = null;

    public function __construct()
    {
        TimberTimber::init();
    }

    public function setSite(Site $site): void
    {
        $this->site = $site;
    }

    #[Hook('timber/context', 'filter')]
    public function add_to_context(array $context): array
    {
        $context['site'] = $this->site;
        $context['menu'] = TimberTimber::get_menu();

        $context['primary_menu'] = TimberTimber::get_menu('primary');
        $context['footer_menu'] = TimberTimber::get_menu('footer');
        $context['options'] = function_exists('get_fields') ? get_fields('option') : [];

        return $context;
    }
}
