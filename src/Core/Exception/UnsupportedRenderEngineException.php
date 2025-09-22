<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Core\Exception;

use Exception;

class UnsupportedRenderEngineException extends Exception
{
    public function __construct($engine)
    {
        parent::__construct("Unsupported render engine: {$engine}");
    }
}
