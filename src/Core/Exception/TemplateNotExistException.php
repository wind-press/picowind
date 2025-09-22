<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Core\Exception;

use Exception;

class TemplateNotExistException extends Exception
{
    public function __construct($path)
    {
        parent::__construct("Template file does not exist: {$path}");
    }
}
