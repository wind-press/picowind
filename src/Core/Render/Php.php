<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render;

use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Exceptions\TemplateNotExistException;

#[Service]
class Php
{
    public function __construct() {}

    public function render_template($paths, array $context = [], bool $print = true)
    {
        // Find first existing template from array or use single path
        if (is_array($paths)) {
            $template_path = null;
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $template_path = $path;
                    break;
                }
            }

            if (! $template_path) {
                throw new TemplateNotExistException(implode(', ', $paths));
            }
        } else {
            $template_path = $paths;
        }

        extract($context);

        if ($print) {
            include $template_path;
        } else {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        return null;
    }
}
