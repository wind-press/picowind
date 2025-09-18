<?php
/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind;

use Timber\Timber;

Timber::render(['sidebar.twig'], $data);
