<?php
/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind;

use Picowind\Core\Template;
use Timber\Timber;

$context = Timber::context();
// Template::render('twig', '404.twig', $context);
Template::render('404', $context);
