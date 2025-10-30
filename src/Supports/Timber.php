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
class Timber extends Site
{
    public function __construct()
    {
        TimberTimber::init();
        parent::__construct();
    }

    #[Hook('f!picowind/context', 'filter')]
    public function add_to_context(array $context): array
    {
        $context['site'] = $this;
        $context['options'] = function_exists('get_fields') ? get_fields('option') : [];

        return $context;
    }

    #[Hook('timber/twig/functions', 'filter')]
    public function add_timber_functions($functions)
    {
        $functions['function_exists'] = [
            'callable' => 'function_exists',
        ];

        return $functions;
    }
}
